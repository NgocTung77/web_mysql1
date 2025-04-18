<?php
$conn = new mysqli("localhost", "root", "", "quan_ly_kho");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$id = $_GET['id'];

$sql_don = mysqli_query($conn, "
    SELECT o.*, u.ten_user 
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.id_user
    WHERE o.id = $id
");
$don = mysqli_fetch_assoc($sql_don);

$sql_chitiet = mysqli_query($conn, "
    SELECT od.*, sp.ten_san_pham, sp.hinh_anh 
    FROM order_details od
    JOIN san_pham sp ON od.product_id = sp.id
    WHERE od.order_id = $id
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn đơn hàng #<?= $don['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
        .invoice-box {
            max-width: 900px;
            margin: auto;
            padding: 20px;
            border: 1px solid #eee;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <h2 class="text-center mb-4">HÓA ĐƠN BÁN HÀNG</h2>
        <p><strong>Mã đơn hàng:</strong> #<?= $don['id'] ?></p>
        <p><strong>Khách hàng:</strong> <?= $don['ten_user'] ?? 'N/A' ?></p>
        <p><strong>Ngày mua:</strong> <?= date("d/m/Y H:i", strtotime($don['created_at'])) ?></p>
        <p><strong>Ghi chú:</strong> <?= $don['notes'] ?></p>

        <hr>

        <table class="table table-bordered mt-4">
            <thead class="table-secondary text-center">
                <tr>
                    <th>STT</th>
                    <th>Sản phẩm</th>
                    <th>Hình ảnh</th>
                    <th>Số lượng</th>
                    <th>Đơn giá (đ)</th>
                    <th>Thành tiền (đ)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                $tong = 0;
                while($row = mysqli_fetch_assoc($sql_chitiet)) {
                    $thanhtien = $row['quantity'] * $row['price'];
                    $tong += $thanhtien;
                ?>
                <tr class="text-center">
                    <td><?= $stt++ ?></td>
                    <td><?= $row['ten_san_pham'] ?></td>
                    <td><img src="modules/quanlychitietsp/uploads/<?= htmlspecialchars($row['hinh_anh']) ?>" style="max-width: 80px; max-height: 80px;"></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= number_format($row['price']) ?></td>
                    <td><?= number_format($thanhtien) ?></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold text-end">
                    <td colspan="5" class="text-end">Tổng cộng:</td>
                    <td class="text-center"><?= number_format($tong) ?> đ</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-5 d-flex justify-content-between">
            <div><strong>Người lập hóa đơn</strong><br><br> </div>
            <div><strong>Khách hàng</strong><br><br>.....<?php echo $don['ten_user'] ?>.......</div>
        </div>

        <div class="mt-4 text-center no-print">
            <a href="index.php?quanly=danhsachdonhang" class="btn btn-secondary">← Quay lại</a>
            <a href="modules/quanlydonhang/xuat_hoadon.php?id=<?= $don['id'] ?>" class="btn btn-success">Xuất Excel</a>
            <button onclick="window.print()" class="btn btn-primary">In hóa đơn</button>
        </div>
    </div>
</body>
</html>
