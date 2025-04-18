<?php
// session_start();
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phiếu xuất kho</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5e81ac;
            --secondary-color: #d8dee9;
            --success-color: #88c0d0;
            --danger-color: #bf616a;
            --info-color: #81a1c1;
            --light-color: #eceff4;
            --dark-color: #2e3440;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, var(--light-color) 0%, #e5e9f0 100%);
            min-height: 100vh;
            padding-top: 20px;
        }

        .container {
            max-width: 1400px;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(46, 52, 64, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid rgba(255, 255, 255, 0.3);
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.6rem;
            letter-spacing: 0.5px;
        }

        .phieu-info {
            padding: 1.5rem;
            background: var(--light-color);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .phieu-info p {
            margin: 0.5rem 0;
            font-size: 1rem;
            color: var(--dark-color);
        }

        .phieu-info p strong {
            color: var(--primary-color);
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: var(--secondary-color);
            color: var(--dark-color);
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all var(--transition-speed);
        }

        .btn-detail {
            background: var(--info-color);
            color: white;
            border: none;
        }

        .btn-detail:hover {
            background: var(--primary-color);
            color: white;
        }

        .tooltip-text {
            font-size: 0.9rem;
            color: var(--danger-color);
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-file-alt me-2"></i>CHI TIẾT PHIẾU XUẤT KHO</h4>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['id'])) {
                    $phieu_id = (int)$_GET['id'];
                    
                    $query = "SELECT px.*, k1.ten_kho AS ten_kho_xuat, k2.ten_kho AS ten_kho_dich,
                              u1.ho_ten AS nguoi_tao_ten
                              FROM phieu_xuat px
                              JOIN kho k1 ON px.kho_id = k1.id
                              LEFT JOIN kho k2 ON px.kho_dich_id = k2.id
                              JOIN nguoi_dung u1 ON px.nguoi_xuat = u1.id
                              WHERE px.id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $phieu_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $phieu = $result->fetch_assoc();
                    
                    // Lấy chi tiết sản phẩm
                    $query_ct = "SELECT ct.*, sp.ma_san_pham, sp.ten_san_pham
                                FROM chi_tiet_phieu_xuat ct
                                JOIN san_pham sp ON ct.san_pham_id = sp.id
                                WHERE ct.phieu_xuat_id = ?";
                    $stmt = $conn->prepare($query_ct);
                    $stmt->bind_param("i", $phieu_id);
                    $stmt->execute();
                    $chi_tiet = $stmt->get_result();
                ?>

                <?php if ($phieu): ?>
                    <div class="phieu-info">
                        <p><strong>Mã phiếu:</strong> <?php echo htmlspecialchars($phieu['ma_phieu']); ?></p>
                        <p><strong>Kho xuất:</strong> <?php echo htmlspecialchars($phieu['ten_kho_xuat']); ?></p>
                        <p><strong>Kho đích:</strong> <?php echo htmlspecialchars($phieu['ten_kho_dich'] ?? 'Không có kho đích'); ?></p>
                        <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars($phieu['ngay_xuat']); ?></p>
                        <p><strong>Người tạo:</strong> <?php echo htmlspecialchars($phieu['nguoi_tao_ten']); ?></p>
                        <p><strong>Lý do:</strong> <?php echo htmlspecialchars($phieu['ghi_chu'] ?? 'Không có'); ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <?php
                            $trang_thai = $phieu['trang_thai'];
                            $badge_class = '';
                            $trang_thai_text = '';
                            switch ($trang_thai) {
                                case 'draft':
                                    $badge_class = 'bg-secondary';
                                    $trang_thai_text = 'Nháp';
                                    break;
                                case 'pending':
                                    $badge_class = 'bg-warning';
                                    $trang_thai_text = 'Chờ duyệt';
                                    break;
                                case 'approved':
                                    $badge_class = 'bg-success';
                                    $trang_thai_text = 'Đã duyệt';
                                    break;
                                case 'completed':
                                    $badge_class = 'bg-info';
                                    $trang_thai_text = 'Hoàn thành';
                                    break;
                                case 'rejected':
                                    $badge_class = 'bg-danger';
                                    $trang_thai_text = 'Từ chối';
                                    break;
                                default:
                                    $badge_class = 'bg-secondary';
                                    $trang_thai_text = 'Không xác định';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($trang_thai_text); ?>
                            </span>
                        </p>
                    </div>
                    
                    <h5 class="mb-3"><i class="fas fa-list-ol me-2"></i>Danh sách sản phẩm</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên SP</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tong_tien = 0;
                                while ($row = $chi_tiet->fetch_assoc()): 
                                    $thanh_tien = $row['so_luong'] * $row['gia_ban'];
                                    $tong_tien += $thanh_tien;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['ma_san_pham']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ten_san_pham']); ?></td>
                                        <td><?php echo htmlspecialchars($row['so_luong']); ?></td>
                                        <td><?php echo number_format($row['gia_ban']) . ' đ'; ?></td>
                                        <td><?php echo number_format($thanh_tien) . ' đ'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Tổng cộng</strong></td>
                                    <td><strong><?php echo number_format($tong_tien) . ' đ'; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php?quanly=quanlyxuatkho" class="btn btn-action btn-detail">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Không tìm thấy thông tin phiếu xuất</p>
                    </div>
                <?php endif; ?>
                <?php } else { ?>
                    <div class="no-data">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Không có ID phiếu xuất được cung cấp</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
