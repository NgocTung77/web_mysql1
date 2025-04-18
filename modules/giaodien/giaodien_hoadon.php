<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để xem đơn hàng.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng của người dùng
$sql_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Danh sách đơn hàng</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if ($result_orders->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Mã đơn hàng</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($order = $result_orders->fetch_assoc()): ?>
                <tr>
                    <td><?= $order['order_code'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</td>
                    <td><?= ucfirst($order['status']) ?></td>
                    <td>
                        <a href="index.php?xem=chitiethoadon&order_id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                            Xem hóa đơn
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bạn chưa có đơn hàng nào.</p>
    <?php endif; ?>
</div>
</body>
</html>