<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order_id = intval($_POST['order_id']);
$new_status = $conn->real_escape_string($_POST['status']);
$notes = $conn->real_escape_string($_POST['notes'] ?? '');
$user_id = $_SESSION['user_id'];

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Cập nhật trạng thái đơn hàng
    $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $new_status, $order_id);
    $stmt_update->execute();
    
    // Thêm vào lịch sử trạng thái
    $sql_history = "INSERT INTO order_status_history (order_id, status, notes, created_by)
                    VALUES (?, ?, ?, ?)";
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param("issi", $order_id, $new_status, $notes, $user_id);
    $stmt_history->execute();
    
    // Nếu trạng thái là 'delivered' (đã giao hàng), trừ số lượng trong kho
    if ($new_status == 'delivered') {
        // Lấy kho của đơn hàng
        $sql_order = "SELECT kho_id FROM orders WHERE id = ?";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("i", $order_id);
        $stmt_order->execute();
        $order_result = $stmt_order->get_result();
        $order = $order_result->fetch_assoc();
        $kho_id = $order['kho_id'];
        
        // Lấy chi tiết đơn hàng
        $sql_details = "SELECT product_id, quantity FROM order_details WHERE order_id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $order_id);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();
        
        // Trừ số lượng trong bảng ton_kho
        while ($detail = $details_result->fetch_assoc()) {
            $product_id = $detail['product_id'];
            $quantity = $detail['quantity'];
            
            // Cập nhật tồn kho cho kho được chọn
            $sql_update_stock = "UPDATE ton_kho 
                                SET so_luong = so_luong - ? 
                                WHERE san_pham_id = ? AND kho_id = ? AND so_luong >= ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("iiii", $quantity, $product_id, $kho_id, $quantity);
            $stmt_update_stock->execute();
            
            // Kiểm tra xem có cập nhật thành công không
            if ($stmt_update_stock->affected_rows == 0) {
                throw new Exception("Không đủ số lượng tồn kho cho sản phẩm ID: $product_id trong kho ID: $kho_id");
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công";
    header("Location: admin_orders.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    $_SESSION['error'] = "Lỗi khi cập nhật đơn hàng: " . $e->getMessage();
    header("Location: admin_orders.php");
    exit();
}
?>