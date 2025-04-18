<?php
session_start();

// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để thực hiện thao tác này.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kiểm tra xem có ID sản phẩm cần xóa không
if (isset($_GET['id'])) {
    $cart_detail_id = intval($_GET['id']);

    // Kiểm tra xem sản phẩm có thuộc giỏ hàng của người dùng không
    $sql_check = "SELECT cd.id 
                  FROM cart_details cd
                  JOIN cart c ON cd.cart_id = c.id
                  WHERE cd.id = ? AND c.user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $cart_detail_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Xóa sản phẩm khỏi giỏ hàng
        $sql_delete = "DELETE FROM cart_details WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $cart_detail_id);
        if ($stmt_delete->execute()) {
            $_SESSION['success'] = "Sản phẩm đã được xóa khỏi giỏ hàng.";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi xóa sản phẩm.";
        }
    } else {
        $_SESSION['error'] = "Sản phẩm không tồn tại trong giỏ hàng của bạn.";
    }
} else {
    $_SESSION['error'] = "Không tìm thấy sản phẩm cần xóa.";
}

// Quay lại trang giỏ hàng
header("Location: index.php?quanly=giohang");
exit();
?>