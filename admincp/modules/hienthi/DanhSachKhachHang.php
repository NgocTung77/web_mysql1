<?php 
include("./modules/config.php");

if(!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'admin'){
    echo "Vui lòng đăng nhập";
    exit();
}

$sql_kh = "SELECT * FROM user";
$result_dskh = mysqli_query($conn, $sql_kh); 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
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

        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--secondary-color);
            color: var(--dark-color);
            border-bottom: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--light-color);
        }

        .table tbody tr:hover {
            background-color: rgba(94, 129, 172, 0.05);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all var(--transition-speed);
        }

        .btn-delete:hover {
            background: #a84a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(191, 97, 106, 0.2);
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--dark-color);
        }

        .no-data i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-users me-2"></i>DANH SÁCH KHÁCH HÀNG</h4>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên người dùng</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Ngày tạo</th>
                            <th>Cập nhật lần cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_dskh) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_dskh)): ?>
                            <tr>
                                <td><?php echo $row['id_user']; ?></td>
                                <td><?php echo $row['ten_user']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['sodienthoai']; ?></td>
                                <td><?php echo $row['dia_chi']; ?></td>
                                <td><?php echo $row['ngay_tao']; ?></td>
                                <td><?php echo $row['cap_nhat_tai']; ?></td>
                                <td>
                                    <a class="btn btn-delete" href="modules/quanlykhachhang/xoa.php?action=delete&id=<?php echo $row['id_user']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa?');">
                                        <i class="fas fa-trash-alt"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>Không có dữ liệu khách hàng</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
