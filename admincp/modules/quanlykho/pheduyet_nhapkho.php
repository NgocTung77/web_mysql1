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



// Lấy danh sách phiếu nhập đang chờ duyệt kèm tên kho và người tạo
$sql = "
    SELECT pn.*, k.ten_kho, nv.ho_ten AS ho_ten, 
           ctpn.san_pham_id, ctpn.so_luong, sp.ten_san_pham
    FROM phieu_nhap pn
    JOIN kho k ON pn.kho_id = k.id 
    JOIN nguoi_dung nv ON pn.nguoi_nhap = nv.id
    JOIN chi_tiet_phieu_nhap ctpn ON pn.id = ctpn.phieu_nhap_id
    JOIN san_pham sp ON ctpn.san_pham_id = sp.id
    WHERE pn.trang_thai = 'pending'
    ORDER BY pn.id DESC
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

// Lấy danh sách phiếu nhập đã phê duyệt và từ chối
$sql_approved = "
    SELECT pn.*, k.ten_kho, nv.ho_ten AS ho_ten,
           ctpn.san_pham_id, ctpn.so_luong, sp.ten_san_pham
    FROM phieu_nhap pn
    JOIN kho k ON pn.kho_id = k.id
    JOIN nguoi_dung nv ON pn.nguoi_nhap = nv.id
    JOIN chi_tiet_phieu_nhap ctpn ON pn.id = ctpn.phieu_nhap_id
    JOIN san_pham sp ON ctpn.san_pham_id = sp.id
    WHERE pn.trang_thai IN ('approved', 'rejected')
    ORDER BY pn.id DESC
";

$result_approved = mysqli_query($conn, $sql_approved);
if (!$result_approved) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

// Xử lý thông báo phê duyệt
if(isset($_SESSION['message'])) {
    echo "<div class='alert alert-success'>".$_SESSION['message']."</div>";
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phê duyệt nhập kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--dark-color);
        }

        .no-data i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .no-data p {
            font-size: 1.2rem;
            margin: 0;
            color: var(--info-color);
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

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all var(--transition-speed);
        }

        .btn-approve {
            background: var(--success-color);
            color: white;
            border: none;
        }

        .btn-reject {
            background: var(--danger-color);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Phiếu chờ duyệt -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-clipboard-list me-2"></i>DANH SÁCH PHIẾU NHẬP CHỜ DUYỆT</h4>
            </div>
            
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Kho</th>
                                <th>Người tạo</th>
                                <th>Ngày tạo</th>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['ngay_tao'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_san_pham']); ?></td>
                                    <td><?php echo htmlspecialchars($row['so_luong']); ?></td>
                                    <td>
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                    </td>
                                    <td>
                                        <form method="POST" action="modules/quanlykho/xuly_pheduyetnhap.php" style="display: inline;">
                                            <input type="hidden" name="phieu_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-action btn-approve me-2">
                                                <i class="fas fa-check"></i> Duyệt
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-action btn-reject">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Không có phiếu nhập nào đang chờ phê duyệt</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Phiếu đã xử lý -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-history me-2"></i>LỊCH SỬ PHIẾU NHẬP ĐÃ XỬ LÝ</h4>
            </div>
            
            <?php if(mysqli_num_rows($result_approved) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Kho</th>
                                <th>Người tạo</th>
                                <th>Ngày tạo</th>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_approved)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['ngay_tao'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_san_pham']); ?></td>
                                    <td><?php echo htmlspecialchars($row['so_luong']); ?></td>
                                    <td>
                                        <?php if($row['trang_thai'] == 'approved'): ?>
                                            <span class="badge bg-success">Đã duyệt</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Đã từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['ghi_chu'] ?? ''); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Chưa có phiếu nhập nào được xử lý</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
