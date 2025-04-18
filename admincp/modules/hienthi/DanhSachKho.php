<?php 
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quan_ly_kho";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$sql_dskho = "SELECT * FROM kho";
$result_dskho = mysqli_query($conn, $sql_dskho);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách kho</title>
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
            padding: 20px;
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
            color: white;
            border: none;
        }

        .btn-success {
            background: var(--success-color);
        }

        .btn-info {
            background: var(--info-color);
        }

        .btn-danger {
            background: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-warehouse me-2"></i>DANH SÁCH KHO</h4>
                <div>
                    <a href="index.php?quanly=admin&ac=them" class="btn btn-action btn-success">
                        <i class="fas fa-plus"></i> Thêm mới
                    </a>
                </div>
            </div>

            <?php if ($result_dskho && mysqli_num_rows($result_dskho) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Kho</th>
                                <th>Tên Kho</th>
                                <th>Địa chỉ</th>
                                <th>Mã vùng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_dskho)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dia_chi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vung_id']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="index.php?quanly=admin&ac=sua&id=<?php echo $row['id']; ?>" class="btn btn-action btn-info me-2">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <a href="index.php?quanly=admin&ac=xoa&id=<?php echo $row['id']; ?>" class="btn btn-action btn-danger" onclick="return confirm('Bạn có chắc muốn xóa kho này?')">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-warehouse"></i>
                    <p>Không có dữ liệu kho</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>