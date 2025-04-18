<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'quan_ly_kho') {
    die("Bạn không có quyền truy cập!");
}

// Hàm thống kê tổng quan cho kho
function getKhoStats($conn, $kho_id) {
    $stats = [];
    
    $queries = [
        'total_products' => "SELECT COUNT(DISTINCT sp.id) AS total 
                           FROM san_pham sp 
                           JOIN ton_kho tk ON sp.id = tk.san_pham_id 
                           WHERE tk.kho_id = $kho_id",
                           
        'total_stock' => "SELECT COALESCE(SUM(so_luong), 0) AS total 
                         FROM ton_kho 
                         WHERE kho_id = $kho_id",
                         
        'low_stock' => "SELECT COUNT(DISTINCT sp.id) AS total 
                       FROM san_pham sp 
                       JOIN ton_kho tk ON sp.id = tk.san_pham_id 
                       WHERE tk.kho_id = $kho_id AND tk.so_luong <= 5",
                       
        'total_value' => "SELECT COALESCE(SUM(tk.so_luong * sp.gia_ban), 0) AS total 
                         FROM ton_kho tk 
                         JOIN san_pham sp ON tk.san_pham_id = sp.id 
                         WHERE tk.kho_id = $kho_id"
    ];
    
    foreach ($queries as $key => $sql) {
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats[$key] = $row['total'];
        } else {
            $stats[$key] = 0;
        }
    }
    
    return $stats;
}

// Hàm lấy thống kê tồn kho theo danh mục
function getKhoInventoryByCategory($conn, $kho_id) {
    $sql = "SELECT 
                lsp.ten_loai AS category,
                COUNT(DISTINCT sp.id) AS product_count,
                COALESCE(SUM(tk.so_luong), 0) AS total_quantity,
                COALESCE(SUM(tk.so_luong * sp.gia_ban), 0) AS total_value
            FROM loai_san_pham lsp
            LEFT JOIN san_pham sp ON sp.loai_id = lsp.id
            LEFT JOIN ton_kho tk ON sp.id = tk.san_pham_id AND tk.kho_id = $kho_id
            GROUP BY lsp.id, lsp.ten_loai
            ORDER BY total_quantity DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Hàm lấy thống kê đơn hàng cho kho
function getKhoOrderStats($conn, $kho_id) {
    $stats = [];
    
    $queries = [
        'total_orders' => "SELECT COUNT(DISTINCT o.id) AS total 
                          FROM orders o 
                          JOIN order_details od ON o.id = od.order_id 
                          JOIN ton_kho tk ON od.product_id = tk.san_pham_id 
                          WHERE tk.kho_id = $kho_id",
                          
        'pending_orders' => "SELECT COUNT(DISTINCT o.id) AS total 
                            FROM orders o 
                            JOIN order_details od ON o.id = od.order_id 
                            JOIN ton_kho tk ON od.product_id = tk.san_pham_id 
                            WHERE tk.kho_id = $kho_id AND o.status = 'pending'",
                            
        'shipping_orders' => "SELECT COUNT(DISTINCT o.id) AS total 
                             FROM orders o 
                             JOIN order_details od ON o.id = od.order_id 
                             JOIN ton_kho tk ON od.product_id = tk.san_pham_id 
                             WHERE tk.kho_id = $kho_id 
                             AND (o.status = 'shipping' OR o.status = 'shipped' 
                                  OR o.status = 'delivering' OR o.status = 'in_transit')",
                             
        'completed_orders' => "SELECT COUNT(DISTINCT o.id) AS total 
                              FROM orders o 
                              JOIN order_details od ON o.id = od.order_id 
                              JOIN ton_kho tk ON od.product_id = tk.san_pham_id 
                              WHERE tk.kho_id = $kho_id 
                              AND (o.status = 'delivered' OR o.status = 'completed')",
                              
        'total_revenue' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                           FROM orders o 
                           JOIN order_details od ON o.id = od.order_id 
                           JOIN san_pham sp ON od.product_id = sp.id 
                           JOIN ton_kho tk ON sp.id = tk.san_pham_id 
                           WHERE tk.kho_id = $kho_id 
                           AND (o.status = 'delivered' OR o.status = 'completed')",
                           
        'today_revenue' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                           FROM orders o 
                           JOIN order_details od ON o.id = od.order_id 
                           JOIN san_pham sp ON od.product_id = sp.id 
                           JOIN ton_kho tk ON sp.id = tk.san_pham_id 
                           WHERE tk.kho_id = $kho_id 
                           AND (o.status = 'delivered' OR o.status = 'completed')
                           AND DATE(o.created_at) = CURRENT_DATE()",
                           
        'month_revenue' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                           FROM orders o 
                           JOIN order_details od ON o.id = od.order_id 
                           JOIN san_pham sp ON od.product_id = sp.id 
                           JOIN ton_kho tk ON sp.id = tk.san_pham_id 
                           WHERE tk.kho_id = $kho_id 
                           AND (o.status = 'delivered' OR o.status = 'completed')
                           AND MONTH(o.created_at) = MONTH(CURRENT_DATE()) 
                           AND YEAR(o.created_at) = YEAR(CURRENT_DATE())"
    ];
    
    foreach ($queries as $key => $sql) {
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats[$key] = $row['total'];
        } else {
            $stats[$key] = 0;
        }
    }
    
    return $stats;
}

// Hàm lấy đơn hàng gần đây của kho
function getKhoRecentOrders($conn, $kho_id, $limit = 5) {
    $sql = "SELECT 
                o.id, 
                o.fullname, 
                o.total_amount, 
                o.status, 
                o.created_at,
                o.phone,
                o.address,
                COUNT(od.id) AS item_count,
                GROUP_CONCAT(CONCAT(sp.ten_san_pham, ' (x', od.quantity, ')') SEPARATOR ', ') as products
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            JOIN san_pham sp ON od.product_id = sp.id
            JOIN ton_kho tk ON sp.id = tk.san_pham_id
            WHERE tk.kho_id = $kho_id
            GROUP BY o.id, o.fullname, o.total_amount, o.status, o.created_at, o.phone, o.address
            ORDER BY o.created_at DESC 
            LIMIT $limit";
    
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Hàm lấy doanh thu theo sản phẩm
function getKhoProductRevenue($conn, $kho_id) {
    $sql = "SELECT 
                sp.ten_san_pham,
                sp.ma_san_pham,
                sp.gia_ban AS unit_price,
                COALESCE(SUM(od.quantity), 0) AS total_quantity,
                COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total_revenue
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            JOIN san_pham sp ON od.product_id = sp.id
            JOIN ton_kho tk ON sp.id = tk.san_pham_id
            WHERE tk.kho_id = $kho_id
            AND (o.status = 'delivered' OR o.status = 'completed')
            GROUP BY sp.id, sp.ten_san_pham, sp.ma_san_pham, sp.gia_ban
            ORDER BY total_revenue DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Lấy thông tin kho
$kho_id = $_SESSION['kho_id']; 
$sql_kho = "SELECT id AS kho_id, ten_kho, dia_chi FROM kho WHERE id = $kho_id";
$result_kho = mysqli_query($conn, $sql_kho);
$kho = mysqli_fetch_assoc($result_kho);

// Lấy thống kê tồn kho
$kho_stats = getKhoStats($conn, $kho_id);
$category_stats = getKhoInventoryByCategory($conn, $kho_id);
$order_stats = getKhoOrderStats($conn, $kho_id);
$recent_orders = getKhoRecentOrders($conn, $kho_id);
$product_revenue = getKhoProductRevenue($conn, $kho_id);

// Lấy dữ liệu tồn kho chi tiết
$sql_tonkho = "SELECT tk.san_pham_id, sp.ten_san_pham, 
                      COALESCE(SUM(tk.so_luong), 0) AS tong_hang,
                      sp.don_vi_tinh,
                      sp.gia_ban,
                      sp.mo_ta,
                      lsp.ten_loai
               FROM ton_kho tk
               JOIN san_pham sp ON tk.san_pham_id = sp.id
               LEFT JOIN loai_san_pham lsp ON sp.loai_id = lsp.id
               WHERE tk.kho_id = $kho_id
               GROUP BY tk.san_pham_id, sp.ten_san_pham, sp.don_vi_tinh, sp.gia_ban, sp.mo_ta, lsp.ten_loai";
$result_tonkho = mysqli_query($conn, $sql_tonkho);

$chart_labels = [];
$chart_data = [];
$product_details = [];

while ($row = mysqli_fetch_assoc($result_tonkho)) {
    $chart_labels[] = $row['ten_san_pham'];
    $chart_data[] = $row['tong_hang'];
    
    $product_details[$row['san_pham_id']] = [
        'ten_san_pham' => $row['ten_san_pham'],
        'tong_hang' => $row['tong_hang'],
        'don_vi_tinh' => $row['don_vi_tinh'],
        'gia_ban' => number_format($row['gia_ban'], 0, ',', '.') . ' VNĐ',
        'mo_ta' => $row['mo_ta'],
        'ten_loai' => $row['ten_loai']
    ];
}

// Lấy thông tin sản phẩm chi tiết nếu có product_id
$selected_product = null;
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    if (isset($product_details[$product_id])) {
        $selected_product = $product_details[$product_id];
    }
}

// Lấy dữ liệu cho biểu đồ doanh thu
$chartData = [];
$timeSql = "SELECT 
                DATE(o.created_at) AS date,
                WEEK(o.created_at) AS week,
                DATE_FORMAT(o.created_at, '%m/%Y') AS month,
                COALESCE(SUM(od.quantity), 0) AS total_quantity,
                COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total_revenue
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            JOIN san_pham sp ON od.product_id = sp.id
            JOIN ton_kho tk ON sp.id = tk.san_pham_id
            WHERE tk.kho_id = $kho_id
            AND (o.status = 'delivered' OR o.status = 'completed')
            GROUP BY date, week, month
            ORDER BY date ASC";
$timeResult = $conn->query($timeSql);
if ($timeResult) {
    while ($row = $timeResult->fetch_assoc()) {
        $chartData[] = [
            'date' => $row['date'],
            'time' => date('d/m/Y', strtotime($row['date'])),
            'week' => $row['week'],
            'month' => $row['month'],
            'quantity' => (int)$row['total_quantity'],
            'revenue' => (float)$row['total_revenue']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }

        .container-fluid {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
        }

        .stat-card {
            border-left: 4px solid;
        }

        .stat-card.primary {
            border-left-color: var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-icon {
            color: #dddfeb;
        }

        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: #f6c23e;
        }

        .badge-shipping {
            background-color: rgba(54, 185, 204, 0.1);
            color: #36b9cc;
        }

        .badge-completed {
            background-color: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }

        .badge-cancelled {
            background-color: rgba(231, 74, 59, 0.1);
            color: #e74a3b;
        }

        .table th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #5a5c69;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .table td {
            vertical-align: middle;
        }

        .progress {
            height: 1rem;
            border-radius: 0.35rem;
            background-color: #eaecf4;
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }

        .btn {
            border-radius: 0.35rem;
            padding: 0.375rem 0.75rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2) !important;
        }

        /* Custom scrollbar styles */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .modal-content {
            background: #fff;
            color: #5a5c69;
            border: 1px solid #e3e6f0;
        }

        .modal-header {
            background: #fff;
            border-bottom: 1px solid #e3e6f0;
        }

        .modal-footer {
            border-top: 1px solid #e3e6f0;
        }

        .modal-body p {
            margin-bottom: 0.5rem;
        }

        .modal-body .table {
            background: #fff;
        }

        .modal-body .table thead th {
            background: #f8f9fc;
        }

        .btn-close {
            filter: brightness(50%);
        }

        .btn-secondary {
            background: #858796;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #6c757d;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Quản lý kho: <?php echo htmlspecialchars($kho['ten_kho']); ?></h1>
        <div class="d-flex gap-2">
            <a href="#" class="btn btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Xuất báo cáo
            </a>
        </div>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng số sản phẩm</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($kho_stats['total_products']); ?></div>
                            <div class="mt-2 text-xs text-muted">
                                <i class="fas fa-exclamation-triangle text-warning"></i> 
                                <?php echo number_format($kho_stats['low_stock']); ?> sản phẩm sắp hết
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box-open fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng số lượng tồn kho</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($kho_stats['total_stock']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-warehouse fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Sản phẩm sắp hết</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($kho_stats['low_stock']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng giá trị tồn kho</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($kho_stats['total_value'], 0, ',', '.'); ?> ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê đơn hàng -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng số đơn hàng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['total_orders']); ?></div>
                            <div class="mt-2 text-xs text-muted">
                                <i class="fas fa-clock text-warning"></i> 
                                <?php echo number_format($order_stats['pending_orders']); ?> đơn chờ xử lý
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Đơn hàng chờ xử lý</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['pending_orders']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đơn hàng đang giao</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['shipping_orders']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Đơn hàng hoàn thành</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['completed_orders']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doanh thu -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng doanh thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['total_revenue'], 0, ',', '.'); ?> ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Doanh thu hôm nay</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['today_revenue'], 0, ',', '.'); ?> ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sun fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card stat-card info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Doanh thu tháng này</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($order_stats['month_revenue'], 0, ',', '.'); ?> ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300 stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê doanh thu từ sản phẩm đã bán -->
    <div class="row mb-4">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Thống kê doanh thu từ sản phẩm đã bán</h6>
                    <div class="d-flex gap-2">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-secondary active" onclick="filterByPeriod('day')">Theo ngày</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="filterByPeriod('week')">Theo tuần</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="filterByPeriod('month')">Theo tháng</button>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary active" onclick="switchChart('revenue')">Biểu đồ doanh thu</button>
                            <button class="btn btn-sm btn-outline-success" onclick="switchChart('quantity')">Biểu đồ số lượng</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container mb-4" style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                        <canvas id="quantityChart" style="display: none;"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tên sản phẩm</th>
                                    <th>Mã sản phẩm</th>
                                    <th>Số lượng đã bán</th>
                                    <th>Đơn giá</th>
                                    <th>Tổng doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($product_revenue)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Chưa có dữ liệu doanh thu</td>
                                    </tr>
                                <?php else: 
                                    $totalRevenue = 0;
                                    $totalQuantity = 0;
                                    foreach ($product_revenue as $product): 
                                        $totalRevenue += $product['total_revenue'];
                                        $totalQuantity += $product['total_quantity'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['ten_san_pham']); ?></td>
                                        <td><?php echo htmlspecialchars($product['ma_san_pham']); ?></td>
                                        <td><?php echo number_format($product['total_quantity']); ?></td>
                                        <td><?php echo number_format($product['unit_price'], 0, ',', '.'); ?> ₫</td>
                                        <td><?php echo number_format($product['total_revenue'], 0, ',', '.'); ?> ₫</td>
                                    </tr>
                                <?php endforeach; ?>
                                    <tr class="table-info">
                                        <td colspan="2" class="text-right font-weight-bold">Tổng cộng:</td>
                                        <td class="font-weight-bold"><?php echo number_format($totalQuantity); ?></td>
                                        <td></td>
                                        <td class="font-weight-bold"><?php echo number_format($totalRevenue, 0, ',', '.'); ?> ₫</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ và Thống kê theo danh mục -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Biểu đồ tồn kho theo sản phẩm</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Thống kê theo danh mục</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Danh mục</th>
                                    <th>Số sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Giá trị</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($category_stats)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Chưa có dữ liệu tồn kho</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($category_stats as $cat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                                        <td><?php echo number_format($cat['product_count']); ?></td>
                                        <td><?php echo number_format($cat['total_quantity']); ?></td>
                                        <td><?php echo number_format($cat['total_value'], 0, ',', '.'); ?> ₫</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Đơn hàng gần đây -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Đơn hàng gần đây</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary active" onclick="filterOrders('all')">Tất cả</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterOrders('pending')">Chờ duyệt</button>
                        <button class="btn btn-sm btn-outline-info" onclick="filterOrders('shipping')">Đang giao</button>
                        <button class="btn btn-sm btn-outline-success" onclick="filterOrders('delivered')">Hoàn thành</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="filterOrders('cancelled')">Đã hủy</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="recentOrdersTable">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Thời gian</th>
                                    <th>Khách hàng</th>
                                    <th>Thông tin liên hệ</th>
                                    <th>Sản phẩm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Chưa có đơn hàng nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr data-status="<?php echo $order['status']; ?>">
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                            <td>
                                                <strong>SĐT:</strong> <?php echo htmlspecialchars($order['phone']); ?><br>
                                                <small><?php echo htmlspecialchars($order['address']); ?></small>
                                            </td>
                                            <td>
                                                <span class="text-wrap">
                                                    <?php echo htmlspecialchars($order['products']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫</td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                $statusText = '';
                                                switch(strtolower(trim($order['status']))) {
                                                    case 'pending':
                                                        $badgeClass = 'badge-pending';
                                                        $statusText = 'Chờ xử lý';
                                                        break;
                                                    case 'confirmed':
                                                        $badgeClass = 'badge-shipping';
                                                        $statusText = 'Đã xác nhận';
                                                        break;
                                                    case 'shipping':
                                                    case 'delivering':
                                                    case 'in_transit':
                                                    case 'shipped':
                                                        $badgeClass = 'badge-shipping';
                                                        $statusText = 'Đang giao';
                                                        break;
                                                    case 'delivered':
                                                    case 'completed':
                                                        $badgeClass = 'badge-completed';
                                                        $statusText = 'Đã giao';
                                                        break;
                                                    case 'cancelled':
                                                        $badgeClass = 'badge-cancelled';
                                                        $statusText = 'Đã hủy';
                                                        break;
                                                    default:
                                                        $badgeClass = 'badge-secondary';
                                                        $statusText = 'Không xác định (' . $order['status'] . ')';
                                                }
                                                ?>
                                                <span class="badge status-badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Modal chi tiết đơn hàng -->
                                                <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Chi tiết đơn hàng #<?php echo $order['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['fullname']); ?></p>
                                                                        <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                                                        <p><strong>Trạng thái:</strong> <span class="badge status-badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                                                                        <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                                                        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫</p>
                                                                    </div>
                                                                </div>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Sản phẩm</th>
                                                                                <th>Số lượng</th>
                                                                                <th>Đơn giá</th>
                                                                                <th>Thành tiền</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php
                                                                            $products = explode(', ', $order['products']);
                                                                            foreach ($products as $product) {
                                                                                preg_match('/^(.*?)\s*\(x(\d+)\)$/', $product, $matches);
                                                                                if (count($matches) == 3) {
                                                                                    $product_name = $matches[1];
                                                                                    $quantity = $matches[2];
                                                                                    echo "<tr>";
                                                                                    echo "<td>" . htmlspecialchars($product_name) . "</td>";
                                                                                    echo "<td>" . $quantity . "</td>";
                                                                                    echo "<td>---</td>";
                                                                                    echo "<td>---</td>";
                                                                                    echo "</tr>";
                                                                                }
                                                                            }
                                                                            ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chi tiết sản phẩm -->
    <?php if (isset($selected_product)): ?>
    <div class="row mb-4" id="productDetail">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Chi tiết sản phẩm: <?php echo htmlspecialchars($selected_product['ten_san_pham']); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Danh mục:</strong> <?php echo htmlspecialchars($selected_product['ten_loai']); ?></p>
                            <p><strong>Số lượng tồn:</strong> <?php echo number_format($selected_product['tong_hang']) . ' ' . htmlspecialchars($selected_product['don_vi_tinh']); ?></p>
                            <p><strong>Giá bán:</strong> <?php echo htmlspecialchars($selected_product['gia_ban']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Mô tả:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($selected_product['mo_ta'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bảng danh sách sản phẩm -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Danh sách tồn kho</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Số lượng tồn</th>
                                    <th>Đơn vị tính</th>
                                    <th>Giá bán</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_details as $id => $product): ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo htmlspecialchars($product['ten_san_pham']); ?></td>
                                    <td><?php echo htmlspecialchars($product['ten_loai']); ?></td>
                                    <td><?php echo number_format($product['tong_hang']); ?></td>
                                    <td><?php echo htmlspecialchars($product['don_vi_tinh']); ?></td>
                                    <td><?php echo htmlspecialchars($product['gia_ban']); ?></td>
                                    <td>
                                        <a href="?product_id=<?php echo $id; ?>#productDetail" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ tồn kho
    const inventoryCtx = document.getElementById('inventoryChart');
    if (inventoryCtx) {
        const inventoryChart = new Chart(inventoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Số lượng tồn kho',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.8)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Số lượng: ${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                onClick: (e, activeEls) => {
                    if (activeEls.length > 0) {
                        const index = activeEls[0].index;
                        const productId = <?php echo json_encode(array_keys($product_details)); ?>[index];
                        window.location.href = `?product_id=${productId}#productDetail`;
                    }
                }
            }
        });
    }

    // Dữ liệu cho biểu đồ doanh thu
    const chartData = <?php echo json_encode($chartData); ?>;
    let revenueChart, quantityChart;
    let currentPeriod = 'day';

    function updateChartData(period) {
        currentPeriod = period;
        let labels = [];
        let revenueData = [];
        let quantityData = [];

        if (period === 'day') {
            chartData.forEach(item => {
                labels.push(item.time);
                revenueData.push(item.revenue);
                quantityData.push(item.quantity);
            });
        } else if (period === 'week') {
            const groupedByWeek = {};
            chartData.forEach(item => {
                if (!groupedByWeek[item.week]) {
                    groupedByWeek[item.week] = { revenue: 0, quantity: 0 };
                }
                groupedByWeek[item.week].revenue += item.revenue;
                groupedByWeek[item.week].quantity += item.quantity;
            });
            Object.keys(groupedByWeek).forEach(week => {
                labels.push(`Tuần ${week}`);
                revenueData.push(groupedByWeek[week].revenue);
                quantityData.push(groupedByWeek[week].quantity);
            });
        } else if (period === 'month') {
            const groupedByMonth = {};
            chartData.forEach(item => {
                if (!groupedByMonth[item.month]) {
                    groupedByMonth[item.month] = { revenue: 0, quantity: 0 };
                }
                groupedByMonth[item.month].revenue += item.revenue;
                groupedByMonth[item.month].quantity += item.quantity;
            });
            labels = Object.keys(groupedByMonth);
            revenueData = Object.values(groupedByMonth).map(item => item.revenue);
            quantityData = Object.values(groupedByMonth).map(item => item.quantity);
        }

        return { labels, revenueData, quantityData };
    }

    function initCharts() {
        const { labels, revenueData, quantityData } = updateChartData(currentPeriod);

        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu',
                        data: revenueData,
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' ₫';
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Doanh thu: ${context.parsed.y.toLocaleString()} ₫`;
                                }
                            }
                        }
                    }
                }
            });
        }

        const quantityCtx = document.getElementById('quantityChart');
        if (quantityCtx) {
            quantityChart = new Chart(quantityCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số lượng',
                        data: quantityData,
                        backgroundColor: 'rgba(28, 200, 138, 0.8)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Số lượng: ${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function filterByPeriod(period) {
        const { labels, revenueData, quantityData } = updateChartData(period);
        if (revenueChart) {
            revenueChart.data.labels = labels;
            revenueChart.data.datasets[0].data = revenueData;
            revenueChart.update();
        }
        if (quantityChart) {
            quantityChart.data.labels = labels;
            quantityChart.data.datasets[0].data = quantityData;
            quantityChart.update();
        }

        document.querySelectorAll('.btn-group.me-2 .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.btn[onclick="filterByPeriod('${period}')"]`).classList.add('active');
    }

    function switchChart(type) {
        if (type === 'revenue') {
            document.getElementById('revenueChart').style.display = 'block';
            document.getElementById('quantityChart').style.display = 'none';
        } else {
            document.getElementById('revenueChart').style.display = 'none';
            document.getElementById('quantityChart').style.display = 'block';
        }

        document.querySelectorAll('.btn-group:not(.me-2) .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.btn[onclick="switchChart('${type}')"]`).classList.add('active');
    }

    initCharts();

    // Hàm lọc đơn hàng theo trạng thái
    function filterOrders(status) {
        const rows = document.querySelectorAll('#recentOrdersTable tbody tr[data-status]');
        rows.forEach(row => {
            const rowStatus = row.dataset.status.toLowerCase();
            const isShipping = ['shipping', 'delivering', 'in_transit', 'shipped'].includes(rowStatus);
            const isDelivered = ['delivered', 'completed'].includes(rowStatus);
            
            if (status === 'all' || 
                (status === rowStatus) || 
                (status === 'shipping' && isShipping) || 
                (status === 'delivered' && isDelivered)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        document.querySelectorAll('.card-header .btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.btn[onclick="filterOrders('${status}')"]`).classList.add('active');
    }
});
</script>
</body>
</html>
<?php
$conn->close();
?>