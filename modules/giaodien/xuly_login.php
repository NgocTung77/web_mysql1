<?php
session_start();
include("../../admincp/modules/config.php"); // Kết nối CSDL

// Nhận dữ liệu từ form
$email = $_POST['email'];
$password = $_POST['password'];

// Chuẩn bị truy vấn (Prepared Statement)
$sql = "SELECT * FROM user WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Lấy thông tin người dùng
    $user = $result->fetch_assoc();

    // Kiểm tra mật khẩu (nếu trong DB lưu hash)
    if (password_verify($password, $user['password'])) {
        // Lưu thông tin vào session
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['ten_user'];
        

        header("location: ../../index.php");
    } else {
        echo "Sai mật khẩu!";
    }
} else {
    echo "Email không tồn tại!";
}

// Đóng kết nối
$stmt->close();
$conn->close();
?>
