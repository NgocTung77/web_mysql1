<?php
session_start();
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['order_id'])) {
    die("Thiếu thông tin đơn hàng.");
}

$order_id = (int)$_GET['order_id'];
$admin_id = $_SESSION['user_id'] ?? 1; // hoặc session admin

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Cập nhật trạng thái đơn
    $sql_update_order = "UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?";
    $stmt_update_order = $conn->prepare($sql_update_order);
    $stmt_update_order->bind_param("i", $order_id);
    $stmt_update_order->execute();

    if ($stmt_update_order->affected_rows === 0) {
        throw new Exception("Không tìm thấy đơn hàng hoặc không có thay đổi");
    }

    // Ghi lịch sử trạng thái
    $note = "Admin duyệt đơn hàng";
    $sql_log = "INSERT INTO order_status_history (order_id, status, notes, created_by) 
                VALUES (?, 'delivered', ?, ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("isi", $order_id, $note, $admin_id);
    $stmt_log->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Đã duyệt đơn hàng thành công!";
    header("Location: index.php?quanly=pheduyetdonhang");
    
    exit();

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['error'] = "Lỗi khi duyệt đơn: " . $e->getMessage();
    header("Location: duyet_don.php");
    exit();
} finally {
    // Đóng kết nối
    $conn->close();
}
?>