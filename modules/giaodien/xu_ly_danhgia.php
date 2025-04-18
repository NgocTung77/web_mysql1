<?php
session_start();

// Kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'quan_ly_kho';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để đánh giá sản phẩm.";
    header("Location: login.php");
    exit;
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['id']) || !isset($_POST['rating']) || !isset($_POST['comment'])) {
    $_SESSION['error'] = "Thiếu thông tin đánh giá.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

$id_user = $_SESSION['user_id'];
$id_sp = $_POST['id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

// Kiểm tra rating hợp lệ
if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Số sao đánh giá phải từ 1 đến 5.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Kiểm tra sản phẩm có tồn tại không
try {
    $stmt = $conn->prepare("SELECT id FROM san_pham WHERE id = :id_sp");
    $stmt->execute(['id_sp' => $id_sp]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Sản phẩm không tồn tại.";
        header("Location: index.php");
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Lỗi kiểm tra sản phẩm: " . $e->getMessage();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Kiểm tra nếu người dùng đã đánh giá sản phẩm này trước đó
try {
    $stmt = $conn->prepare("SELECT Review_id FROM reviews WHERE id_user = :id_user AND id_sp = :id_sp");
    $stmt->execute(['id_user' => $id_user, 'id_sp' => $id_sp]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Bạn đã đánh giá sản phẩm này trước đó!";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Lỗi kiểm tra đánh giá: " . $e->getMessage();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Chèn đánh giá vào cơ sở dữ liệu
try {
    $sql = "INSERT INTO reviews (id_user, id_sp, rating, comment, created_at) 
            VALUES (:id_user, :id_sp, :rating, :comment, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'id_user' => $id_user,
        'id_sp' => $id_sp,
        'rating' => $rating,
        'comment' => htmlspecialchars($comment)
    ]);
    
    $_SESSION['success'] = "Cảm ơn bạn đã đánh giá sản phẩm!";
    header("Location: ../../index.php?xem=chitietsanpham&id=" . $id_sp);
    exit;
} catch(PDOException $e) {
    $_SESSION['error'] = "Lỗi khi lưu đánh giá: " . $e->getMessage();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
