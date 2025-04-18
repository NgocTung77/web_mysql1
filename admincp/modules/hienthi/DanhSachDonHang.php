<?php
$conn = new mysqli("localhost", "root", "", "quan_ly_kho");
if ($conn->connect_error) die("Kết nối thất bại: " . $conn->connect_error);

$search = $_GET['search'] ?? '';
$sql = "
    SELECT o.id, o.created_at, o.status, u.ten_user, SUM(od.quantity * od.price) AS tong_tien
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.id_user
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.id LIKE '%$search%' OR u.ten_user LIKE '%$search%' OR o.created_at LIKE '%$search%'
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

    <h2>Quản lý đơn hàng</h2>

    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="<?= $search ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Tìm</button>
        </div>
        <div class="col-auto">
            <a href="them_donhang.php" class="btn btn-success">+ Thêm đơn hàng</a>
        </div>
        <div class="col-auto">
            <a href="xuat_excel_donhang.php?search=<?= $search ?>" class="btn btn-outline-success">Xuất Excel</a>
        </div>
        <div class="col-auto">
            <a href="xuat_pdf_donhang.php?search=<?= $search ?>" class="btn btn-outline-danger">Xuất PDF</a>
        </div>
    </form>

    <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Tổng tiền</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = 1; while ($row = $result->fetch_assoc()) { ?>
                <tr class="text-center">
                    <td><?= $stt++ ?></td>
                    <td>#<?= isset($row['id']) ? $row['id'] : 'N/A' ?></td>
                    <td><?= $row['ten_user'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= number_format($row['tong_tien']) ?> đ</td>
                    <td>
                        <a href="index.php?quanly=chitietdonhang&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Chi tiết</a>
                    </td>
                    
                </tr>
            <?php } ?>
        </tbody>
    </table>

</body>
</html>
