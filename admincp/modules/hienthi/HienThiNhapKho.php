<?php
include('./modules/config.php');

$sql = "SELECT * FROM phieu_nhap";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách phiếu nhập</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.2rem;
        }
        
        .table-responsive {
            border-radius: 0 0 10px 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        
        .btn-detail {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
        }
        
        .btn-detail:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .total-amount {
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="fas fa-list-alt me-2"></i>DANH SÁCH PHIẾU NHẬP</h3>
                            <a href="them_phieunhap.php" class="btn btn-light">
                                <i class="fas fa-plus-circle me-2"></i>Thêm phiếu nhập
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <?php if ($result->num_rows > 0) { ?>
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Mã phiếu</th>
                                        <th>Kho nhập</th>
                                        <th>Người nhập</th>
                                        <th>Ngày nhập</th>
                                        <th>Trạng thái</th>
                                        <th>Tổng tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()) { 
                                        $kho_id = $row['kho_id'];
                                        $nguoi_nhap = $row['nguoi_nhap'];
                                        $trang_thai = $row['trang_thai'];
                                        
                                        // Lấy tên kho và người nhập từ các bảng tương ứng
                                        $sql_kho = "SELECT ten_kho FROM kho WHERE id = $kho_id";
                                        $result_kho = $conn->query($sql_kho);
                                        $kho_name = $result_kho->fetch_assoc()['ten_kho'];

                                        $sql_nguoi_nhap = "SELECT ho_ten FROM nguoi_dung WHERE id = $nguoi_nhap";
                                        $result_nguoi_nhap = $conn->query($sql_nguoi_nhap);
                                        $nguoi_nhap_name = $result_nguoi_nhap->fetch_assoc()['ho_ten'];
                                        
                                        // Xác định class trạng thái
                                        $status_class = '';
                                        if ($trang_thai == 'Đã duyệt') {
                                            $status_class = 'status-approved';
                                        } elseif ($trang_thai == 'Từ chối') {
                                            $status_class = 'status-rejected';
                                        } else {
                                            $status_class = 'status-pending';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $row['ma_phieu']; ?></td>
                                        <td><?php echo $kho_name; ?></td>
                                        <td><?php echo $nguoi_nhap_name; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['ngay_nhap'])); ?></td>
                                        <td><span class="<?php echo $status_class; ?>"><?php echo $trang_thai; ?></span></td>
                                        <td class="total-amount text-end"><?php echo number_format($row['tong_tien'], 0, ',', '.'); ?> VND</td>
                                        <td>
                                            <a href="index.php?quanly=quanlykho&ac=chitietnhapkho&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-detail">
                                                <i class="fas fa-eye me-1"></i>Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <div class="p-4 text-center">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>Không có phiếu nhập nào trong hệ thống.
                                </div>
                                <a href="them_phieunhap.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Tạo phiếu nhập mới
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php $conn->close(); ?>
</body>
</html>