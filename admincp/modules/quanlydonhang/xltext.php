<?php
session_start();

// Kết nối database
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// Kiểm tra tham số
if (!isset($_GET['order_id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Thiếu thông tin đơn hàng hoặc trạng thái";
    header("Location: danhsach_donhang.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$status = $_GET['status'];
$admin_id = $_SESSION['user_id'] ?? 0; // ID người quản trị

// Validate trạng thái
$allowed_statuses = ['approved', 'delivered', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['error'] = "Trạng thái không hợp lệ";
    header("Location: danhsach_donhang.php");
    exit();
}

// Bắt đầu transaction
$conn->begin_transaction();
// 1. Lấy trạng thái cũ trước khi cập nhật
$get_old_status = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$get_old_status->bind_param("i", $order_id);
$get_old_status->execute();
$old_status_result = $get_old_status->get_result();
$old_status = $old_status_result->fetch_assoc()['status'];

if (!$old_status) {
    throw new Exception("Không tìm thấy đơn hàng");
}

// 2. Cập nhật trạng thái đơn hàng
$update_order = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
$update_order->bind_param("si", $status, $order_id);
$update_order->execute();

if ($update_order->affected_rows === 0) {
    throw new Exception("Không tìm thấy đơn hàng hoặc không có thay đổi");
}

// Ghi log lịch sử trạng thái
$notes = [
    'approved' => "Đơn hàng đã được xác nhận và đang giao",
    'delivered' => "Đơn hàng đã giao thành công",
    'cancelled' => "Đơn hàng đã bị hủy"
];

$insert_log = $conn->prepare("INSERT INTO order_status_history 
                            (order_id, status, notes, created_by) 
                            VALUES (?, ?, ?, ?)");
$insert_log->bind_param("issi", $order_id, $status, $notes[$status], $admin_id);
$insert_log->execute();

// 3. Xử lý tồn kho
if ($status === 'approved') {
    // Trừ kho khi xác nhận
    $sql_details = "SELECT product_id, kho_id, quantity FROM order_details WHERE order_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param("i", $order_id);
    $stmt_details->execute();
    $result = $stmt_details->get_result();

    while ($item = $result->fetch_assoc()) {
        $sp_id = $item['product_id'];
        $kho_id = $item['kho_id'];
        $so_luong = $item['quantity'];

        $update_stock = $conn->prepare("UPDATE ton_kho SET so_luong = so_luong - ? 
                                      WHERE san_pham_id = ? AND kho_id = ? AND so_luong >= ?");
        $update_stock->bind_param("iiii", $so_luong, $sp_id, $kho_id, $so_luong);
        $update_stock->execute();

        if ($update_stock->affected_rows === 0) {
            throw new Exception("Sản phẩm ID $sp_id không đủ hàng trong kho $kho_id");
        }
    }
} elseif ($status === 'cancelled' && $old_status === 'approved') {
    // Nếu trước đó đã approved → thì cộng lại kho
    $sql_details = "SELECT product_id, kho_id, quantity FROM order_details WHERE order_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param("i", $order_id);
    $stmt_details->execute();
    $result = $stmt_details->get_result();

    while ($item = $result->fetch_assoc()) {
        $sp_id = $item['product_id'];
        $kho_id = $item['kho_id'];
        $so_luong = $item['quantity'];

        $update_stock = $conn->prepare("UPDATE ton_kho SET so_luong = so_luong + ? 
                                      WHERE san_pham_id = ? AND kho_id = ?");
        $update_stock->bind_param("iii", $so_luong, $sp_id, $kho_id);
        $update_stock->execute();
    }
}

// delivered: không làm gì vì đã trừ kho từ lúc approved

?>