<?php
session_start();

// Kết nối database
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $_SESSION['error'] = "Kết nối database thất bại: " . $conn->connect_error;
    header("Location: ../../index.php?quanly=quanlydonhang");
    exit();
}

// Kiểm tra phương thức gửi dữ liệu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Phương thức không hợp lệ";
    header("Location: ../../index.php?quanly=quanlydonhang");
    exit();
}

// Kiểm tra tham số
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    $_SESSION['error'] = "Thiếu thông tin đơn hàng hoặc trạng thái";
    header("Location: ../../index.php?quanly=quanlydonhang");
    exit();
}

$order_id = (int)$_POST['order_id'];
$new_status = $_POST['status'];
$admin_id = $_SESSION['admin_id'] ?? 0;

// Validate trạng thái
$allowed_statuses = ['pending', 'shipped', 'delivered', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    $_SESSION['error'] = "Trạng thái không hợp lệ";
    header("Location: ../../index.php?quanly=quanlydonhang");
    exit();
}

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // 1. Lấy thông tin đơn hàng và trạng thái hiện tại
    $sql_order = "SELECT o.*, c.id as cart_id FROM orders o 
                  LEFT JOIN cart c ON o.user_id = c.user_id 
                  WHERE o.id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $order_result = $stmt_order->get_result();
    
    if ($order_result->num_rows === 0) {
        throw new Exception("Không tìm thấy đơn hàng");
    }
    
    $order = $order_result->fetch_assoc();
    $current_status = $order['status'];
    $cart_id = $order['cart_id'];

    // 2. Kiểm tra trình tự trạng thái
    $valid_transitions = [
        'pending' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => []
    ];

    if (!in_array($new_status, $valid_transitions[$current_status])) {
        throw new Exception("Không thể chuyển trạng thái từ '$current_status' sang '$new_status'. Vui lòng thực hiện theo đúng trình tự: Chờ duyệt -> Đang giao -> Hoàn thành");
    }

    // 3. Xử lý khi duyệt đơn hàng
    if ($new_status === 'shipped' && $current_status === 'pending') {
        // Lấy chi tiết đơn hàng để biết số lượng sản phẩm
        $sql_details = "SELECT od.product_id, od.quantity, sp.ten_san_pham 
                        FROM order_details od 
                        JOIN san_pham sp ON od.product_id = sp.id 
                        WHERE od.order_id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $order_id);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();

        // Kiểm tra và cập nhật tồn kho
        while ($item = $details_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            // Kiểm tra số lượng tồn kho trong tất cả các kho
            $sql_stock = "SELECT tk.so_luong, tk.kho_id, k.ten_kho 
                         FROM ton_kho tk 
                         JOIN kho k ON tk.kho_id = k.id 
                         WHERE tk.san_pham_id = ? 
                         AND tk.so_luong >= ?
                         ORDER BY tk.so_luong DESC 
                         LIMIT 1";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $product_id, $quantity);
            $stmt_stock->execute();
            $stock_result = $stmt_stock->get_result();
            $stock = $stock_result->fetch_assoc();

            if (!$stock) {
                // Kiểm tra xem sản phẩm có trong kho nào không
                $sql_check = "SELECT SUM(so_luong) as total_stock 
                            FROM ton_kho 
                            WHERE san_pham_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $product_id);
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();
                $check = $check_result->fetch_assoc();
                
                if ($check['total_stock'] === null) {
                    throw new Exception("Sản phẩm {$item['ten_san_pham']} chưa được nhập vào kho nào. Vui lòng kiểm tra lại thông tin kho!");
                } else {
                    throw new Exception("Sản phẩm {$item['ten_san_pham']} không đủ số lượng trong kho (Tổng tồn kho: {$check['total_stock']}, cần {$quantity})");
                }
            }

            // Cập nhật order_details với kho_id đã chọn
            $sql_update_order_detail = "UPDATE order_details 
                                      SET kho_id = ? 
                                      WHERE order_id = ? AND product_id = ?";
            $stmt_update_order_detail = $conn->prepare($sql_update_order_detail);
            $stmt_update_order_detail->bind_param("iii", $stock['kho_id'], $order_id, $product_id);
            $stmt_update_order_detail->execute();

            // Trừ số lượng tồn kho
            $sql_update_stock = "UPDATE ton_kho 
                                SET so_luong = so_luong - ? 
                                WHERE san_pham_id = ? AND kho_id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("iii", $quantity, $product_id, $stock['kho_id']);
            $stmt_update_stock->execute();

            if ($stmt_update_stock->affected_rows === 0) {
                throw new Exception("Không thể cập nhật tồn kho cho sản phẩm {$item['ten_san_pham']} trong kho {$stock['ten_kho']}");
            }
        }
    } elseif ($new_status === 'cancelled' && $current_status === 'shipped') {
        // Cộng lại số lượng khi hủy đơn hàng đã duyệt
        $sql_details = "SELECT od.product_id, od.kho_id, od.quantity, sp.ten_san_pham 
                        FROM order_details od 
                        JOIN san_pham sp ON od.product_id = sp.id 
                        WHERE od.order_id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $order_id);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();

        while ($item = $details_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $kho_id = $item['kho_id'];
            $quantity = $item['quantity'];

            // Cộng lại số lượng tồn kho
            $sql_update_stock = "UPDATE ton_kho 
                                SET so_luong = so_luong + ? 
                                WHERE san_pham_id = ? AND kho_id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("iii", $quantity, $product_id, $kho_id);
            $stmt_update_stock->execute();
        }
    }

    // 4. Cập nhật trạng thái đơn hàng
    $sql_update_order = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt_update_order = $conn->prepare($sql_update_order);
    $stmt_update_order->bind_param("si", $new_status, $order_id);
    $stmt_update_order->execute();

    // 5. Thêm lịch sử trạng thái
    $status_messages = [
        'pending' => 'Chờ duyệt',
        'shipped' => 'Đang giao',
        'delivered' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    $notes = "Đơn hàng chuyển từ " . $status_messages[$current_status] . " sang " . $status_messages[$new_status];
    $sql_history = "INSERT INTO order_status_history (order_id, status, notes, created_by) 
                   VALUES (?, ?, ?, ?)";
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param("issi", $order_id, $new_status, $notes, $admin_id);
    $stmt_history->execute();

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['error'] = "Lỗi: " . $e->getMessage();
} finally {
    // Đóng kết nối
    $conn->close();
}

// Chuyển hướng về trang quản lý đơn hàng
header("Location: ../../index.php?quanly=quanlydonhang");
exit();
?>