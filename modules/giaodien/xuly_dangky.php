<?php
include('../../admincp/modules/config.php'); // Kết nối đến MySQL

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_user = trim($_POST['ten_user']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $dia_chi = trim($_POST['dia_chi']);
    $sodienthoai = trim($_POST['sodienthoai']);

    // Kiểm tra dữ liệu đầu vào
    if (empty($ten_user) || empty($email) || empty($password)) {
        echo "Vui lòng nhập đầy đủ thông tin!";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email không hợp lệ!";
        exit();
    }

    if (strlen($password) < 6) {
        echo "Mật khẩu phải có ít nhất 6 ký tự!";
        exit();
    }

    // Kiểm tra email đã tồn tại chưa
    $check_email_query = "SELECT * FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "Email đã tồn tại!";
        exit();
    }

    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_query = "INSERT INTO user (ten_user, email, password, dia_chi, sodienthoai) 
                 VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($stmt, "sssss", $ten_user, $email, $hashed_password, $dia_chi, $sodienthoai);

    if (mysqli_stmt_execute($stmt)) {
        header("location: ../../index.php");
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
}
?>
