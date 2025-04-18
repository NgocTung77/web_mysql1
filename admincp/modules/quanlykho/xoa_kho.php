<?php
include __DIR__ . '/../auth/auth.php';
include __DIR__ . '/../config/db_connect.php';

// Chỉ cho phép admin xóa kho
if ($_SESSION['vai_tro'] !== 'admin') {
    die("Bạn không có quyền xóa kho.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM kho WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Xóa kho thành công!";
    } else {
        echo "Lỗi khi xóa kho.";
    }
}
?>
<p><a href="danhsach_kho.php">Quay lại danh sách kho</a></p>
