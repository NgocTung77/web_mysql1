<?php
include('./modules/config.php');

if (isset($_GET['id'])) {
    $phieu_nhap_id = $_GET['id'];

    // Lấy thông tin phiếu nhập
    $sql_phieu = "SELECT * FROM phieu_nhap WHERE id = $phieu_nhap_id";
    $result_phieu = $conn->query($sql_phieu);
    $phieu = $result_phieu->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phiếu nhập</title>
   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
        
        .info-card {
            border-left: 4px solid var(--secondary-color);
            margin-bottom: 20px;
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
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .status-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>CHI TIẾT PHIẾU NHẬP</h3>
                            <a href="javascript:window.print()" class="btn btn-light">
                                <i class="fas fa-print me-2"></i>In phiếu
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($phieu) { 
                            $ma_phieu = $phieu['ma_phieu'];
                            $kho_id = $phieu['kho_id'];
                            $nguoi_nhap = $phieu['nguoi_nhap'];
                            $tong_tien = number_format($phieu['tong_tien'], 0, ',', '.');
                            $ngay_nhap = date('d/m/Y', strtotime($phieu['ngay_nhap']));
                            $trang_thai = $phieu['trang_thai'];
                            
                            // Lấy tên kho và người nhập
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
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-barcode me-2"></i>Mã phiếu</h6>
                                    <h4><?php echo $ma_phieu; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-warehouse me-2"></i>Kho nhập</h6>
                                    <h4><?php echo $kho_name; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-user me-2"></i>Người nhập</h6>
                                    <h4><?php echo $nguoi_nhap_name; ?></h4>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-calendar-day me-2"></i>Ngày nhập</h6>
                                    <h4><?php echo $ngay_nhap; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Trạng thái</h6>
                                    <h4><span class="status-badge <?php echo $status_class; ?>"><?php echo $trang_thai; ?></span></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card p-3 bg-white rounded">
                                    <h6 class="text-muted mb-3"><i class="fas fa-money-bill-wave me-2"></i>Tổng tiền</h6>
                                    <h4 class="total-amount"><?php echo $tong_tien; ?> VND</h4>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3"><i class="fas fa-boxes me-2"></i>DANH SÁCH SẢN PHẨM</h5>
                        
                        <div class="table-responsive">
                            <?php 
                            // Lấy chi tiết phiếu nhập
                            $sql_ct = "SELECT * FROM chi_tiet_phieu_nhap WHERE phieu_nhap_id = $phieu_nhap_id";
                            $result_ct = $conn->query($sql_ct);

                            if ($result_ct->num_rows > 0) { ?>
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>STT</th>
                                        <th>Sản phẩm</th>
                                        <th class="text-end">Số lượng</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th>Đơn vị</th>
                                        <th class="text-end">Độ lệch</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stt = 1;
                                    while ($row = $result_ct->fetch_assoc()) { 
                                        $san_pham_id = $row['san_pham_id'];
                                        $so_luong = $row['so_luong'];
                                        $gia_nhap = number_format($row['gia_nhap'], 0, ',', '.');
                                        $don_vi_tinh = $row['don_vi_tinh'];
                                        $do_lech = number_format($row['do_lech'], 0, ',', '.');
                                        $thanh_tien = number_format($so_luong * $row['gia_nhap'], 0, ',', '.');

                                        // Lấy thông tin sản phẩm
                                        $sql_sp = "SELECT ten_san_pham, hinh_anh FROM san_pham WHERE id = $san_pham_id";
                                        $result_sp = $conn->query($sql_sp);
                                        $san_pham = $result_sp->fetch_assoc();
                                        $san_pham_name = $san_pham['ten_san_pham'];
                                        $hinh_anh = $san_pham['hinh_anh'] ? 'modules/quanlychitietsp/uploads/'.$san_pham['hinh_anh'] : 'https://via.placeholder.com/60';
                                    ?>
                                    <tr>
                                        <td><?php echo $stt++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $hinh_anh; ?>" alt="<?php echo $san_pham_name; ?>" class="product-image me-3">
                                                <span><?php echo $san_pham_name; ?></span>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo $so_luong; ?></td>
                                        <td class="text-end"><?php echo $gia_nhap; ?> VND</td>
                                        <td><?php echo $don_vi_tinh; ?></td>
                                        <td class="text-end"><?php echo $do_lech; ?> VND</td>
                                        <td class="text-end fw-bold"><?php echo $thanh_tien; ?> VND</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end fw-bold">Tổng cộng:</td>
                                        <td class="text-end fw-bold total-amount"><?php echo $tong_tien; ?> VND</td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php } else { ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Không có sản phẩm nào trong phiếu nhập này.
                            </div>
                            <?php } ?>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="index.php?quanly=quanlynhapkho" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <div>
                                <?php if ($phieu['trang_thai'] == 'Chờ duyệt') { ?>
                                <button class="btn btn-success me-2">
                                    <i class="fas fa-check-circle me-2"></i>Duyệt phiếu
                                </button>
                                <button class="btn btn-danger">
                                    <i class="fas fa-times-circle me-2"></i>Từ chối
                                </button>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <?php } else { ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>Phiếu nhập không tồn tại.
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php } else { ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Không có mã phiếu nhập.
                </div>
                <a href="danhsach_phieunhap.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php $conn->close(); ?>
</body>
</html>