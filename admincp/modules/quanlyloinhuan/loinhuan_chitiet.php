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

$id_sp = $_GET['id_sanpham'];
$thang = $_GET['thang'];

// Kiểm tra vai trò người dùng
$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? '';

// Xây dựng điều kiện WHERE cho kho
$where_condition = "";
if ($vai_tro === 'vung' && !empty($vung_id)) {
    $where_condition = "AND k.vung_id = ?";
} elseif ($vai_tro === 'admin') {
    $where_condition = ""; // Admin xem tất cả kho
}

// Lấy tên sản phẩm
$q1 = $conn->query("SELECT ten_san_pham FROM san_pham WHERE id = $id_sp");
$ten_sp = $q1->fetch_assoc()['ten_san_pham'];

// Lấy dữ liệu đơn hàng, nhóm theo kho
$sql = "SELECT 
    k.ten_kho,
    k.id AS kho_id,
    o.id AS don_id,
    o.created_at,
    od.quantity,
    od.price,
    o.notes AS ghichu,
    kh.ten_user AS ten_khachhang,
    AVG(ctpn.gia_nhap) AS gia_nhap_tb
FROM kho k
LEFT JOIN order_details od ON od.kho_id = k.id AND od.product_id = ?
LEFT JOIN orders o ON od.order_id = o.id 
    AND o.status = 'delivered' 
    AND DATE_FORMAT(o.created_at, '%Y-m') = ?
LEFT JOIN user kh ON o.user_id = kh.id_user
LEFT JOIN chi_tiet_phieu_nhap ctpn ON od.product_id = ctpn.san_pham_id
$where_condition
GROUP BY k.id, k.ten_kho, o.id, od.quantity, od.price, o.notes, kh.ten_user
ORDER BY k.ten_kho, o.created_at DESC";

$stmt = $conn->prepare($sql);
if ($vai_tro === 'vung' && !empty($vung_id)) {
    $stmt->bind_param("isi", $id_sp, $thang, $vung_id);
} else {
    $stmt->bind_param("is", $id_sp, $thang);
}
$stmt->execute();
$result = $stmt->get_result();

// Tổ chức dữ liệu theo kho
$data_by_kho = [];
while ($row = $result->fetch_assoc()) {
    $ten_kho = $row['ten_kho'];
    $data_by_kho[$ten_kho][] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lợi nhuận - <?= htmlspecialchars($ten_sp) ?></title>
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
        .grand-total {
            background: var(--success-color);
            color: white;
            font-size: 1.2rem;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .btn-export {
            background: var(--success-color);
            border: none;
        }
        .btn-back {
            background: var(--info-color);
            border: none;
        }
        .kho-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #5a5c69;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Chi tiết lợi nhuận: <span class="fw-normal"><?= htmlspecialchars($ten_sp) ?></span></h3>
                <p class="mb-0 mt-2"><strong>Tháng:</strong> <?= htmlspecialchars($thang) ?></p>
            </div>
            <div class="card-body">
                <a href="modules/quanlyloinhuan/xuat_excel_loinhuan.php?id_sp=<?= $id_sp ?>&thang=<?= $thang ?>" class="btn btn-export mb-3">
                    <i class="fas fa-file-excel me-2"></i>Xuất Excel
                </a>

                <?php if (empty($data_by_kho)): ?>
                    <div class="alert alert-info">Không có dữ liệu bán hàng trong tháng này.</div>
                <?php else: ?>
                    <?php 
                    $tong_doanhthu_all = 0;
                    $tong_loinhuan_all = 0;
                    ?>
                    
                    <!-- Bảng chi tiết đơn hàng -->
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Kho</th>
                                <th>Mã đơn</th>
                                <th>Ngày bán</th>
                                <th>Khách hàng</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <th>Lợi nhuận</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            foreach ($data_by_kho as $ten_kho => $orders): 
                                foreach ($orders as $row): 
                                    $thanhtien = $row['quantity'] ? $row['quantity'] * $row['price'] : 0;
                                    $loinhuan = $thanhtien - ($row['quantity'] ? $row['quantity'] * ($row['gia_nhap_tb'] ?? 0) : 0);
                                    $tong_doanhthu_all += $thanhtien;
                                    $tong_loinhuan_all += $loinhuan;
                            ?>
                                <tr>
                                    <td><?= $stt++ ?></td>
                                    <td><?= htmlspecialchars($ten_kho) ?></td>
                                    <td>#<?= $row['don_id'] ?? '-' ?></td>
                                    <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                    <td><?= htmlspecialchars($row['ten_khachhang'] ?? 'Không rõ') ?></td>
                                    <td><?= $row['quantity'] ?? 0 ?></td>
                                    <td><?= $row['price'] ? number_format($row['price'], 0, ',', '.') : '-' ?></td>
                                    <td><?= $thanhtien ? number_format($thanhtien, 0, ',', '.') : 0 ?></td>
                                    <td class="<?= $loinhuan >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $loinhuan ? number_format($loinhuan, 0, ',', '.') : 0 ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['ghichu'] ?? '-') ?></td>
                                </tr>
                            <?php 
                                endforeach;
                            endforeach; 
                            ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="7" class="text-end">Tổng cộng:</td>
                                <td><?= number_format($tong_doanhthu_all, 0, ',', '.') ?> đ</td>
                                <td><?= number_format($tong_loinhuan_all, 0, ',', '.') ?> đ</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Tổng kết lợi nhuận -->
                    <div class="grand-total mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Tổng doanh thu:</strong> <?= number_format($tong_doanhthu_all, 0, ',', '.') ?> đ
                            </div>
                            <div class="col-md-6">
                                <strong>Tổng lợi nhuận:</strong> <?= number_format($tong_loinhuan_all, 0, ',', '.') ?> đ
                            </div>
                        </div>
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