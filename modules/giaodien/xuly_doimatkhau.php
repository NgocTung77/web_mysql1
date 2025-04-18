<?php
session_start();
include("../../config/config.php"); // Kết nối database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra mật khẩu mới nhập lại có khớp không
    if ($new_password !== $confirm_password) {
        echo "<script>alert('Mật khẩu mới không khớp!'); window.history.back();</script>";
        exit();
    }

    // Lấy mật khẩu cũ từ database
    $sql = "SELECT password FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    
    if (!$row || !password_verify($old_password, $row['password'])) {
        echo "<script>alert('Mật khẩu cũ không đúng!'); window.history.back();</script>";
        exit();
    }

    // Mã hóa mật khẩu mới
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Cập nhật mật khẩu mới
    $update_sql = "UPDATE users SET password = '$new_hashed_password' WHERE id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('Đổi mật khẩu thành công!'); window.location.href = '../../index.php?xem=trangchu';</script>";
    } else {
        echo "<script>alert('Có lỗi xảy ra, vui lòng thử lại!'); window.history.back();</script>";
    }

    mysqli_close($conn);
}
?>
