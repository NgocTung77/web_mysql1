<?php

// Kết nối database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để xem đơn hàng.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng của user
$sql_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// Khởi tạo biến để lưu thông tin chi tiết đơn hàng nếu có
$order = null;
$order_details = null;

// Nếu có order_id trong URL thì lấy chi tiết đơn hàng
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    // Kiểm tra đơn hàng này có thuộc về user hay không
    $sql_order = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("ii", $order_id, $user_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    
    if ($result_order->num_rows > 0) {
        $order = $result_order->fetch_assoc();
        
        // Lấy chi tiết đơn hàng
        $sql_details = "SELECT * FROM order_details WHERE order_id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $order_id);
        $stmt_details->execute();
        $order_details = $stmt_details->get_result();
    } else {
        $_SESSION['error'] = "Không tìm thấy đơn hàng của bạn.";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng của tôi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --spacing-unit: 1.5rem;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-unit);
        }

        .main-content {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
        }

        .section-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: calc(var(--spacing-unit) * 1.5);
            margin-bottom: var(--spacing-unit);
        }

        .section-body {
            padding: var(--spacing-unit);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0;
        }

        .table-container {
            margin: var(--spacing-unit) 0;
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fc;
            color: var(--secondary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            padding: 1rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: 0.5em 1em;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tooltip-custom {
            position: relative;
            display: inline-block;
        }

        .tooltip-custom .tooltip-text {
            visibility: hidden;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.875rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip-custom:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .table tr {
            transition: all 0.3s ease;
        }

        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(78, 115, 223, 0.2);
        }

        .status-icon {
            font-size: 1rem;
        }

        .order-summary {
            background-color: #f8f9fc;
            border-radius: 8px;
            padding: var(--spacing-unit);
            margin: var(--spacing-unit) 0;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-unit);
        }

        .info-group {
            background-color: #fff;
            padding: var(--spacing-unit);
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .info-group p {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        .info-group p:last-child {
            margin-bottom: 0;
        }

        .info-group i {
            width: 24px;
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: var(--spacing-unit);
            padding: 0.5rem 1rem;
            background-color: #f8f9fc;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .back-link:hover {
            color: #2e59d9;
            background-color: #e3e6f0;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: var(--spacing-unit);
            padding: 1rem;
        }

        .highlight {
            background-color: rgba(78, 115, 223, 0.05);
        }

        @media (max-width: 768px) {
            .container {
                padding: calc(var(--spacing-unit) / 2);
            }
            
            .section-header {
                padding: var(--spacing-unit);
            }
            
            .section-body {
                padding: calc(var(--spacing-unit) / 2);
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="container">
    <div class="main-content">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-shopping-bag me-2"></i>Danh sách đơn hàng của bạn
            </h2>
        </div>
        <div class="section-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $orders_result->fetch_assoc()): ?>
                            <tr class="<?= (isset($_GET['order_id']) && $_GET['order_id'] == $row['id']) ? 'highlight' : '' ?>">
                                <td><?= htmlspecialchars($row['order_code']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($row['status']) ?> tooltip-custom">
                                        <?php
                                        $statusIcon = '';
                                        switch(strtolower($row['status'])) {
                                            case 'pending':
                                                $statusIcon = '<i class="fas fa-clock status-icon"></i>';
                                                break;
                                            case 'processing':
                                                $statusIcon = '<i class="fas fa-cog status-icon"></i>';
                                                break;
                                            case 'completed':
                                                $statusIcon = '<i class="fas fa-check-circle status-icon"></i>';
                                                break;
                                            case 'cancelled':
                                                $statusIcon = '<i class="fas fa-times-circle status-icon"></i>';
                                                break;
                                        }
                                        echo $statusIcon . ucfirst($row['status']);
                                        ?>
                                        <span class="tooltip-text"><?= ucfirst($row['status']) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?xem=donhangnguoidung&order_id=<?= $row['id'] ?>" 
                                       class="btn btn-primary tooltip-custom">
                                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                                        <span class="tooltip-text">Xem thông tin chi tiết đơn hàng</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($order && $order_details): ?>
                <a href="http://localhost/web_mysql1/index.php?xem=donhangnguoidung" class="back-link">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách đơn hàng
                </a>

                <div class="order-summary">
                    <h3 class="section-title mb-4">Chi tiết đơn hàng: <?= htmlspecialchars($order['order_code']) ?></h3>
                    <div class="order-info">
                        <div class="info-group">
                            <p><i class="fas fa-calendar"></i><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p><i class="fas fa-user"></i><strong>Họ tên:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                            <p><i class="fas fa-map-marker-alt"></i><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
                        </div>
                        <div class="info-group">
                            <p><i class="fas fa-credit-card"></i><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                            <p><i class="fas fa-money-bill-wave"></i><strong>Tổng tiền:</strong> <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</p>
                            <p><i class="fas fa-info-circle"></i><strong>Trạng thái:</strong> 
                                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <h4 class="section-title mb-4"><i class="fas fa-boxes me-2"></i>Danh sách sản phẩm</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Ảnh</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($item = $order_details->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>
                                        <img src="admincp/modules/quanlychitietsp/uploads/<?= htmlspecialchars($item['product_image']) ?>" 
                                            class="product-image" 
                                            alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                    <td><?= number_format($item['subtotal'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Loading animation
    window.addEventListener('load', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.opacity = '0';
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
        }, 300);
    });

    // Add hover effect to table rows
    document.querySelectorAll('.table tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
</script>
</body>
</html>
