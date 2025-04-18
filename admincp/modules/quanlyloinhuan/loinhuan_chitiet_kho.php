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
$thang = $_GET['thang'];

// Kiểm tra vai trò người dùng
$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? '';

// Debug thông tin session
error_log("Vai trò: " . $vai_tro);
error_log("Vùng ID: " . $vung_id);

// Kiểm tra quyền truy cập kho
$check_kho = $conn->prepare("SELECT vung_id FROM kho WHERE id = ?");
$check_kho->bind_param("i", $kho_id);
$check_kho->execute();
$kho_data = $check_kho->get_result()->fetch_assoc();

// Debug thông tin kho
error_log("Kho vung_id: " . ($kho_data['vung_id'] ?? 'null'));

if ($vai_tro === 'vung' && !empty($vung_id) && $kho_data['vung_id'] != $vung_id) {
    die("Bạn không có quyền truy cập kho này");
}

// Lấy tên kho
$q1 = $conn->prepare("SELECT ten_kho FROM kho WHERE id = ?");
$q1->bind_param("i", $kho_id);
$q1->execute();
$ten_kho = $q1->get_result()->fetch_assoc()['ten_kho'];

// Lấy dữ liệu sản phẩm trong kho
$sql = "
    SELECT 
        sp.id AS san_pham_id,
        sp.ten_san_pham,
        sp.don_vi_tinh,
        COUNT(DISTINCT o.id) AS so_don,
        SUM(od.quantity) AS tong_so_luong,
        SUM(od.quantity * od.price) AS tong_doanh_thu,
        SUM(od.quantity * (od.price - COALESCE(ctpn.gia_nhap_tb, 0))) AS tong_loi_nhuan
    FROM san_pham sp
    JOIN order_details od ON sp.id = od.product_id
    JOIN orders o ON od.order_id = o.id
    LEFT JOIN (
        SELECT 
            san_pham_id,
            AVG(gia_nhap) AS gia_nhap_tb
        FROM chi_tiet_phieu_nhap
        GROUP BY san_pham_id
    ) ctpn ON sp.id = ctpn.san_pham_id
    WHERE od.kho_id = $kho_id
        AND o.status = 'delivered'
        AND DATE_FORMAT(o.created_at, '%Y-%m') = '$thang'
    GROUP BY sp.id, sp.ten_san_pham, sp.don_vi_tinh
    ORDER BY tong_loi_nhuan DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lợi nhuận kho <?= htmlspecialchars($ten_kho) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-bg: #f8f9fc;
        }
        body {
            background: var(--light-bg);
            font-family: 'Nunito', sans-serif;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
        }
        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .table th {
            background: #f8f9fc;
            color: #5a5c69;
            font-weight: 600;
            text-transform: uppercase;
        }
        .table td {
            vertical-align: middle;
        }
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        .btn-export {
            background: var(--success-color);
            border: none;
        }
        .btn-back {
            background: var(--info-color);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Chi tiết lợi nhuận kho: <span class="fw-normal"><?= htmlspecialchars($ten_kho) ?></span></h3>
                <p class="mb-0 mt-2"><strong>Tháng:</strong> <?= htmlspecialchars($thang) ?></p>
            </div>
            <div class="card-body">
                <a href="modules/quanlyloinhuan/xuat_excel_loinhuan_kho.php?kho_id=<?= $kho_id ?>&thang=<?= $thang ?>" class="btn btn-export mb-3">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </a>

                <?php if ($result->num_rows == 0): ?>
                    <div class="alert alert-info">Không có dữ liệu bán hàng trong tháng này.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Số đơn</th>
                                    <th>Tổng số lượng</th>
                                    <th>Tổng doanh thu</th>
                                    <th>Tổng lợi nhuận</th>
                                    <th>Tỷ suất</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stt = 1;
                                $tong_doanhthu = 0;
                                $tong_loinhuan = 0;
                                while ($row = $result->fetch_assoc()): 
                                    $tong_doanhthu += $row['tong_doanh_thu'];
                                    $tong_loinhuan += $row['tong_loi_nhuan'];
                                    $ty_suat = $row['tong_doanh_thu'] > 0 ? ($row['tong_loi_nhuan'] / $row['tong_doanh_thu']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?= $stt++ ?></td>
                                        <td><?= htmlspecialchars($row['ten_san_pham']) ?></td>
                                        <td><?= $row['so_don'] ?></td>
                                        <td><?= number_format($row['tong_so_luong']) ?> <?= htmlspecialchars($row['don_vi_tinh']) ?></td>
                                        <td><?= number_format($row['tong_doanh_thu'], 0, ',', '.') ?> đ</td>
                                        <td class="<?= $row['tong_loi_nhuan'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($row['tong_loi_nhuan'], 0, ',', '.') ?> đ
                                        </td>
                                        <td><?= number_format($ty_suat, 2) ?>%</td>
                                        <td>
                                            <a href="index.php?quanly=chitietloinhuan_sanpham&kho_id=<?= $kho_id ?>&san_pham_id=<?= $row['san_pham_id'] ?>&thang=<?= $thang ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>Xem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="4" class="text-end">Tổng cộng:</td>
                                    <td><?= number_format($tong_doanhthu, 0, ',', '.') ?> đ</td>
                                    <td class="<?= $tong_loinhuan >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($tong_loinhuan, 0, ',', '.') ?> đ
                                    </td>
                                    <td><?= number_format(($tong_doanhthu > 0 ? ($tong_loinhuan / $tong_doanhthu) * 100 : 0), 2) ?>%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

                <a href="loinhuan.php" class="btn btn-back mt-3">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?> 