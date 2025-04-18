<?php
session_start();
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quan_ly_kho";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_dang_nhap = $_POST['ten_dang_nhap'];
    $mat_khau = $_POST['mat_khau'];

    $stmt = $conn->prepare("SELECT id, mat_khau, vai_tro, vung_id, kho_id, ho_ten FROM nguoi_dung WHERE ten_dang_nhap = ?");
    $stmt->bind_param("s", $ten_dang_nhap);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_mat_khau, $vai_tro, $vung_id, $kho_id, $ho_ten);
        $stmt->fetch();
        
        if ($mat_khau === $db_mat_khau) {
            $_SESSION['id'] = $id;
            $_SESSION['vai_tro'] = $vai_tro;
            $_SESSION['vung_id'] = $vung_id;
            $_SESSION['kho_id'] = $kho_id;
            $_SESSION['ho_ten'] = $ho_ten;
            
           
            header('Location: http://localhost/web_mysql1/admincp/index.php');
            exit(); // Luôn thoát sau khi chuyển hướng
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Tài khoản không tồn tại!";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">ĐĂNG NHẬP HỆ THỐNG</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="ten_dang_nhap" name="ten_dang_nhap" required>
                </div>
                <div class="mb-4">
                    <label for="mat_khau" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="mat_khau" name="mat_khau" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
                
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>