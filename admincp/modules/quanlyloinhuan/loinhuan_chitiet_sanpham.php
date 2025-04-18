<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$kho_id = $_GET['kho_id'];
$san_pham_id = $_GET['san_pham_id'];
$thang = $_GET['thang'];

// Lấy thông tin kho và sản phẩm
$q1 = $conn->query("SELECT ten_kho FROM kho WHERE id = $kho_id");
$ten_kho = $q1->fetch_assoc()['ten_kho'];

$q2 = $conn->query("SELECT ten_san_pham, don_vi_tinh FROM san_pham WHERE id = $san_pham_id");
$sp = $q2->fetch_assoc();
$ten_san_pham = $sp['ten_san_pham'];
$don_vi_tinh = $sp['don_vi_tinh'];

// Lấy giá nhập trung bình của sản phẩm
$q3 = $conn->query("
    SELECT AVG(gia_nhap) AS gia_nhap_tb 
    FROM chi_tiet_phieu_nhap 
    WHERE san_pham_id = $san_pham_id
");
$gia_nhap_tb = $q3->fetch_assoc()['gia_nhap_tb'] ?? 0;

// Lấy chi tiết các đơn hàng của sản phẩm
$sql = "
    SELECT 
        o.id AS order_id,
        o.created_at,
        od.quantity,
        od.price,
        (od.quantity * od.price) AS doanh_thu,
        (od.quantity * (od.price - $gia_nhap_tb)) AS loi_nhuan
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    WHERE od.kho_id = $kho_id
        AND od.product_id = $san_pham_id
        AND o.status = 'delivered'
        AND DATE_FORMAT(o.created_at, '%Y-%m') = '$thang'
    ORDER BY o.created_at DESC
";
$result = $conn->query($sql);

// Tính tổng
$tong_so_luong = 0;
$tong_doanh_thu = 0;
$tong_loi_nhuan = 0;

while ($row = $result->fetch_assoc()) {
    $tong_so_luong += $row['quantity'];
    $tong_doanh_thu += $row['doanh_thu'];
    $tong_loi_nhuan += $row['loi_nhuan'];
}

$ty_suat = $tong_doanh_thu > 0 ? ($tong_loi_nhuan / $tong_doanh_thu) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lợi nhuận sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table th { background-color: #f8f9fa; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .profit-positive { color: #28a745; }
        .profit-negative { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Chi tiết lợi nhuận sản phẩm</h2>
                <h4 class="text-muted">Kho: <?php echo $ten_kho; ?></h4>
                <h4 class="text-muted">Sản phẩm: <?php echo $ten_san_pham; ?></h4>
                <h4 class="text-muted">Tháng: <?php echo $thang; ?></h4>
            </div>
            <div>
                <a href="loinhuan_chitiet_kho.php?kho_id=<?php echo $kho_id; ?>&thang=<?php echo $thang; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="modules/quanlyloinhuan/xuat_excel_loinhuan_sanpham.php?kho_id=<?php echo $kho_id; ?>&san_pham_id=<?php echo $san_pham_id; ?>&thang=<?php echo $thang; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h5>Tổng số lượng</h5>
                        <p class="h3"><?php echo number_format($tong_so_luong); ?> <?php echo $don_vi_tinh; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>Tổng doanh thu</h5>
                        <p class="h3"><?php echo number_format($tong_doanh_thu, 0, ',', '.'); ?> đ</p>
                    </div>
                    <div class="col-md-3">
                        <h5>Tổng lợi nhuận</h5>
                        <p class="h3 <?php echo $tong_loi_nhuan >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($tong_loi_nhuan, 0, ',', '.'); ?> đ
                        </p>
                    </div>
                    <div class="col-md-3">
                        <h5>Tỷ suất lợi nhuận</h5>
                        <p class="h3 <?php echo $ty_suat >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($ty_suat, 2); ?>%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Chi tiết các đơn hàng</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Doanh thu</th>
                                <th>Lợi nhuận</th>
                                <th>Tỷ suất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result->data_seek(0); // Reset result pointer
                            while ($row = $result->fetch_assoc()):
                                $ty_suat_don = $row['doanh_thu'] > 0 ? ($row['loi_nhuan'] / $row['doanh_thu']) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $row['order_id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td><?php echo number_format($row['quantity']); ?> <?php echo $don_vi_tinh; ?></td>
                                <td><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</td>
                                <td><?php echo number_format($row['doanh_thu'], 0, ',', '.'); ?> đ</td>
                                <td class="<?php echo $row['loi_nhuan'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                    <?php echo number_format($row['loi_nhuan'], 0, ',', '.'); ?> đ
                                </td>
                                <td class="<?php echo $ty_suat_don >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                    <?php echo number_format($ty_suat_don, 2); ?>%
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?> 