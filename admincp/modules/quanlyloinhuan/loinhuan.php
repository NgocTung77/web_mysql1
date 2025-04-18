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

// Lấy tháng hiện tại nếu không có tháng được chọn
$thang = isset($_GET['thang']) ? $_GET['thang'] : date('Y-m');

// Kiểm tra session và vai trò người dùng
// session_start();
if (!isset($_SESSION['vai_tro'])) {
    die("Vui lòng đăng nhập để tiếp tục");
}

$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? '';

// Debug thông tin session
echo "<!-- Debug: Vai trò = " . $vai_tro . " -->";
echo "<!-- Debug: Vùng ID = " . $vung_id . " -->";

// Lấy dữ liệu lợi nhuận theo kho
$sql = "
    SELECT 
        k.id AS kho_id,
        k.ten_kho,
        k.vung_id,
        v.ten_vung,
        COALESCE(COUNT(DISTINCT o.id), 0) AS so_don,
        COALESCE(SUM(od.quantity), 0) AS tong_so_luong,
        COALESCE(SUM(od.quantity * od.price), 0) AS tong_doanh_thu,
        COALESCE(SUM(od.quantity * (od.price - COALESCE(ctpn.gia_nhap_tb, 0))), 0) AS tong_loi_nhuan
    FROM kho k
    LEFT JOIN vung_mien v ON k.vung_id = v.id
    LEFT JOIN order_details od ON k.id = od.kho_id
    LEFT JOIN orders o ON od.order_id = o.id 
        AND o.status = 'delivered' 
        AND DATE_FORMAT(o.created_at, '%Y-m') = ?
    LEFT JOIN (
        SELECT 
            san_pham_id,
            AVG(gia_nhap) AS gia_nhap_tb
        FROM chi_tiet_phieu_nhap
        GROUP BY san_pham_id
    ) ctpn ON od.product_id = ctpn.san_pham_id
    WHERE 1=1
";

// Thêm điều kiện lọc theo vùng nếu là quản lý vùng
if ($vai_tro === 'quan_ly_vung' && !empty($vung_id)) {
    $sql .= " AND k.vung_id = ?";
}

$sql .= " GROUP BY k.id, k.ten_kho, k.vung_id, v.ten_vung ORDER BY k.ten_kho";

$stmt = $conn->prepare($sql);
if ($vai_tro === 'quan_ly_vung' && !empty($vung_id)) {
    $stmt->bind_param("si", $thang, $vung_id);
} else {
    $stmt->bind_param("s", $thang);
}
$stmt->execute();
$result = $stmt->get_result();

// Debug kết quả truy vấn
echo "<!-- Debug: Số kho tìm thấy = " . $result->num_rows . " -->";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lợi nhuận</title>
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
            정신: 1.5rem;
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
        .kho-card {
            transition: transform 0.2s;
        }
        .kho-card:hover {
            transform: translateY(-5px);
        }
        .vung-info {
            background: var(--info-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Quản lý lợi nhuận</h3>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <form method="get" action="" class="d-flex">
                            <input type="hidden" name="quanly" value="loinhuan">
                            <input type="month" name="thang" value="<?= $thang ?>" class="form-control me-2">
                            <button type="submit" class="btn btn-light">Xem</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($vai_tro === 'quan_ly_vung' && !empty($vung_id)): ?>
                            <div class="vung-info">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Đang xem dữ liệu của vùng <?= htmlspecialchars($row['ten_vung'] ?? 'Không xác định') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows == 0): ?>
                    <div class="alert alert-info">Không có dữ liệu bán hàng trong tháng này.</div>
                <?php else: ?>
                    <div class="row">
                        <?php 
                        $tong_doanhthu_all = 0;
                        $tong_loinhuan_all = 0;
                        while ($row = $result->fetch_assoc()): 
                            $tong_doanhthu_all += $row['tong_doanh_thu'];
                            $tong_loinhuan_all += $row['tong_loi_nhuan'];
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card kho-card">
                                    <div class="card-header bg-primary text-white">
                                        <h4 class="mb-0">
                                            <i class="fas fa-warehouse me-2"></i><?= htmlspecialchars($row['ten_kho']) ?>
                                            <?php if ($vai_tro === 'admin'): ?>
                                                <small class="float-end"><?= htmlspecialchars($row['ten_vung'] ?? 'Không xác định') ?></small>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Số đơn:</strong> <?= $row['so_don'] ?></p>
                                                <p><strong>Tổng số lượng:</strong> <?= $row['tong_so_luong'] ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Doanh thu:</strong> <?= number_format($row['tong_doanh_thu'], 0, ',', '.') ?> đ</p>
                                                <p><strong>Lợi nhuận:</strong> 
                                                    <span class="<?= $row['tong_loi_nhuan'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($row['tong_loi_nhuan'], 0, ',', '.') ?> đ
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="index.php?quanly=chitietloinhuan&kho_id=<?= $row['kho_id'] ?>&thang=<?= $thang ?>" class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Tổng kết -->
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Tổng kết tháng <?= date('m/Y', strtotime($thang)) ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-center">Tổng doanh thu</h5>
                                    <h3 class="text-center text-primary"><?= number_format($tong_doanhthu_all, 0, ',', '.') ?> đ</h3>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-center">Tổng lợi nhuận</h5>
                                    <h3 class="text-center <?= $tong_loinhuan_all >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($tong_loinhuan_all, 0, ',', '.') ?> đ
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>