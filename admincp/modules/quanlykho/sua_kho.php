<?php
include('./modules/config.php');

if ($_SESSION['vai_tro'] !== 'admin') {
    die("Bạn không có quyền chỉnh sửa kho.");
}

// Lấy thông tin kho
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM kho WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kho = $result->fetch_assoc();
}

// Cập nhật kho
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_kho = $_POST['ten_kho'];
    $dia_chi = $_POST['dia_chi'];

    $sql = "UPDATE kho SET ten_kho = ?, dia_chi = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $ten_kho, $dia_chi, $id);

    if ($stmt->execute()) {
        $thong_bao = '<div class="alert alert-success">Cập nhật kho thành công!</div>';
    } else {
        $thong_bao = '<div class="alert alert-danger">Lỗi khi cập nhật kho.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Kho</title>
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
            padding: 2rem;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.6rem;
            letter-spacing: 0.5px;
        }

        .form-label {
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid var(--secondary-color);
            padding: 0.75rem;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            transition: all var(--transition-speed);
            color: white;
            border: none;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary-color);
        }

        .btn-outline-secondary {
            border-color: var(--info-color);
            color: var(--info-color);
            padding: 0.5rem 1rem;
        }

        .btn-outline-secondary:hover {
            background: var(--info-color);
            color: white;
        }

        .alert {
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-warehouse me-2"></i>SỬA KHO</h2>
            </div>

            <?php if (isset($thong_bao)) echo $thong_bao; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Tên kho:</label>
                    <input type="text" name="ten_kho" value="<?php echo htmlspecialchars($kho['ten_kho']); ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ:</label>
                    <input type="text" name="dia_chi" value="<?php echo htmlspecialchars($kho['dia_chi']); ?>" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-action btn-primary w-100">Cập nhật</button>
            </form>
            <div class="text-center mt-3">
                <a href="http://localhost/web_mysql1/admincp/index.php?quanly=admin" class="btn btn-outline-secondary">Quay lại danh sách kho</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>