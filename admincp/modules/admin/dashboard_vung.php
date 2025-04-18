<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id']) || $_SESSION['vai_tro'] !== 'quan_ly_vung') {
    header('Location: login.php');
    exit();
}

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Kết nối database thất bại: " . $e->getMessage());
}

$vung_id = $_SESSION['vung_id'];

// Hàm kiểm tra bảng tồn tại
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Lấy thông tin vùng
$sql_vung = "SELECT ten_vung FROM vung_mien WHERE id = ?";
$stmt = $conn->prepare($sql_vung);
$stmt->bind_param("i", $vung_id);
$stmt->execute();
$result_vung = $stmt->get_result();
$vung_info = $result_vung->fetch_assoc();

if (!$vung_info) {
    die("Không tìm thấy thông tin vùng");
}

// Hàm thống kê tổng quan
function getSummaryStats($conn, $vung_id) {
    $stats = [];
    
    $queries = [
        'products' => "SELECT COUNT(DISTINCT sp.id) AS total 
                       FROM san_pham sp 
                       LEFT JOIN kho k ON sp.kho_id = k.id 
                       WHERE k.vung_id = ? OR k.vung_id IS NULL",
        'categories' => "SELECT COUNT(DISTINCT lsp.id) AS total 
                         FROM loai_san_pham lsp 
                         JOIN san_pham sp ON lsp.id = sp.loai_id 
                         LEFT JOIN kho k ON sp.kho_id = k.id 
                         WHERE k.vung_id = ? OR k.vung_id IS NULL",
        'warehouses' => "SELECT COUNT(*) AS total FROM kho WHERE vung_id = ?",
        'orders' => "SELECT COUNT(*) AS total 
                     FROM orders o 
                     LEFT JOIN kho k ON o.kho_id = k.id 
                     WHERE k.vung_id = ? OR k.vung_id IS NULL",
        'users' => "SELECT COUNT(*) AS total FROM nguoi_dung WHERE vung_id = ?",
        'revenue' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                      FROM orders o 
                      JOIN order_details od ON o.id = od.order_id 
                      JOIN san_pham sp ON od.product_id = sp.id 
                      LEFT JOIN kho k ON o.kho_id = k.id 
                      WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'delivered'",
        'inventory' => "SELECT COALESCE(SUM(tk.so_luong), 0) AS total 
                        FROM ton_kho tk 
                        JOIN kho k ON tk.kho_id = k.id 
                        WHERE k.vung_id = ?",
        'low_stock' => "SELECT COUNT(*) AS total 
                        FROM ton_kho tk 
                        JOIN kho k ON tk.kho_id = k.id 
                        WHERE k.vung_id = ? AND tk.so_luong < 10",
        'pending_orders' => "SELECT COUNT(*) AS total 
                             FROM orders o 
                             LEFT JOIN kho k ON o.kho_id = k.id 
                             WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'pending'",
        'shipping_orders' => "SELECT COUNT(*) AS total 
                              FROM orders o 
                              LEFT JOIN kho k ON o.kho_id = k.id 
                              WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status IN ('approved', 'shipped')",
        'completed_orders' => "SELECT COUNT(*) AS total 
                               FROM orders o 
                               LEFT JOIN kho k ON o.kho_id = k.id 
                               WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'delivered'",
        'cancelled_orders' => "SELECT COUNT(*) AS total 
                               FROM orders o 
                               LEFT JOIN kho k ON o.kho_id = k.id 
                               WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'cancelled'",
        'total_products_sold' => "SELECT COALESCE(SUM(od.quantity), 0) AS total 
                                  FROM order_details od 
                                  JOIN orders o ON od.order_id = o.id 
                                  LEFT JOIN kho k ON o.kho_id = k.id 
                                  WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'delivered'",
        'total_revenue_today' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                                  FROM orders o 
                                  JOIN order_details od ON o.id = od.order_id 
                                  JOIN san_pham sp ON od.product_id = sp.id 
                                  LEFT JOIN kho k ON o.kho_id = k.id 
                                  WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND DATE(o.created_at) = CURDATE() AND o.status = 'delivered'",
        'total_revenue_week' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                                 FROM orders o 
                                 JOIN order_details od ON o.id = od.order_id 
                                 JOIN san_pham sp ON od.product_id = sp.id 
                                 LEFT JOIN kho k ON o.kho_id = k.id 
                                 WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE()) AND o.status = 'delivered'",
        'total_revenue_month' => "SELECT COALESCE(SUM(od.quantity * sp.gia_ban), 0) AS total 
                                  FROM orders o 
                                  JOIN order_details od ON o.id = od.order_id 
                                  JOIN san_pham sp ON od.product_id = sp.id 
                                  LEFT JOIN kho k ON o.kho_id = k.id 
                                  WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE()) AND o.status = 'delivered'"
    ];
    
    foreach ($queries as $key => $sql) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $vung_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats[$key] = $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getSummaryStats - $key: " . $e->getMessage());
            $stats[$key] = 0;
        }
    }
    
    return $stats;
}

// Hàm lấy top sản phẩm
function getTopProducts($conn, $vung_id, $limit = 5) {
    try {
        $sql = "
            WITH inventory_summary AS (
                SELECT 
                    san_pham_id,
                    SUM(so_luong) as total_stock
                FROM ton_kho tk
                JOIN kho k ON tk.kho_id = k.id
                WHERE k.vung_id = ?
                GROUP BY san_pham_id
            )
            SELECT 
                sp.id, 
                sp.ten_san_pham, 
                sp.ma_san_pham, 
                lsp.ten_loai,
                GROUP_CONCAT(DISTINCT k.ten_kho SEPARATOR ', ') AS warehouses,
                COALESCE(SUM(od.quantity), 0) AS total_sold,
                COALESCE(SUM(od.quantity * od.price), 0) AS total_revenue,
                COALESCE(isum.total_stock, 0) AS current_stock
            FROM san_pham sp
            LEFT JOIN loai_san_pham lsp ON sp.loai_id = lsp.id
            LEFT JOIN order_details od ON sp.id = od.product_id
            LEFT JOIN orders o ON od.order_id = o.id
            LEFT JOIN ton_kho tk ON sp.id = tk.san_pham_id
            LEFT JOIN kho k ON tk.kho_id = k.id
            LEFT JOIN inventory_summary isum ON sp.id = isum.san_pham_id
            WHERE (k.vung_id = ? OR k.vung_id IS NULL)
            GROUP BY sp.id, sp.ten_san_pham, sp.ma_san_pham, lsp.ten_loai, isum.total_stock
            ORDER BY total_sold DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iii', $vung_id, $vung_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error in getTopProducts: " . $e->getMessage());
    }
    return [];
}

// Hàm lấy thống kê trạng thái đơn hàng
function getOrderStatusStats($conn, $vung_id) {
    try {
        $sql = "
            SELECT 
                SUM(status = 'pending') AS pending,
                SUM(status IN ('approved', 'shipped')) AS shipping,
                SUM(status = 'delivered') AS delivered,
                SUM(status = 'cancelled') AS cancelled
            FROM orders o
            LEFT JOIN kho k ON o.kho_id = k.id
            WHERE (k.vung_id = ? OR k.vung_id IS NULL)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vung_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getOrderStatusStats: " . $e->getMessage());
        return [
            'pending' => 0,
            'shipping' => 0,
            'delivered' => 0,
            'cancelled' => 0
        ];
    }
}

// Hàm lấy doanh thu theo tháng
function getMonthlyRevenue($conn, $vung_id) {
    try {
        $sql = "
            SELECT 
                sp.ten_san_pham,
                sp.ma_san_pham,
                sp.gia_ban as unit_price,
                SUM(od.quantity) as total_quantity,
                SUM(od.quantity * sp.gia_ban) as total_revenue
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            JOIN san_pham sp ON od.product_id = sp.id
            LEFT JOIN kho k ON o.kho_id = k.id
            WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'delivered'
            GROUP BY sp.ten_san_pham, sp.ma_san_pham, sp.gia_ban
            ORDER BY total_revenue DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vung_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getMonthlyRevenue: " . $e->getMessage());
        return [];
    }
}

// Hàm lấy thống kê tồn kho theo danh mục
function getInventoryByCategory($conn, $vung_id) {
    try {
        $sql = "
            SELECT 
                lsp.ten_loai AS category,
                COALESCE(SUM(tk.so_luong), 0) AS total_quantity,
                COUNT(DISTINCT sp.id) AS product_count,
                COALESCE(SUM(tk.so_luong * sp.gia_ban), 0) AS total_value
            FROM loai_san_pham lsp
            LEFT JOIN san_pham sp ON sp.loai_id = lsp.id
            LEFT JOIN ton_kho tk ON sp.id = tk.san_pham_id
            LEFT JOIN kho k ON tk.kho_id = k.id
            WHERE k.vung_id = ?
            GROUP BY lsp.id, lsp.ten_loai
            ORDER BY total_quantity DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vung_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getInventoryByCategory: " . $e->getMessage());
        return [];
    }
}

// Hàm lấy đơn hàng gần đây
function getRecentOrders($conn, $vung_id, $limit = 10) {
    try {
        $sql = "
            SELECT 
                o.id, 
                o.fullname, 
                o.total_amount, 
                o.status, 
                o.created_at,
                o.phone,
                o.email,
                COUNT(od.id) AS item_count,
                GROUP_CONCAT(CONCAT(sp.ten_san_pham, ' (x', od.quantity, ')') SEPARATOR ', ') as products
            FROM orders o
            LEFT JOIN order_details od ON o.id = od.order_id
            LEFT JOIN san_pham sp ON od.product_id = sp.id
            LEFT JOIN kho k ON o.kho_id = k.id
            WHERE (k.vung_id = ? OR k.vung_id IS NULL)
            GROUP BY o.id, o.fullname, o.total_amount, o.status, o.created_at, o.phone, o.email
            ORDER BY o.created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $vung_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getRecentOrders: " . $e->getMessage());
        return [];
    }
}

// Lấy tất cả dữ liệu
$stats = getSummaryStats($conn, $vung_id);
$topProducts = getTopProducts($conn, $vung_id);
$orderStatus = getOrderStatusStats($conn, $vung_id);
$monthlyRevenue = getMonthlyRevenue($conn, $vung_id);
$warehouseStats = [];
try {
    $sql = "
        SELECT k.ten_kho, 
               COUNT(tk.id) AS product_count, 
               SUM(tk.so_luong) AS total_quantity,
               SUM(tk.so_luong * sp.gia_ban) AS total_value
        FROM kho k
        LEFT JOIN ton_kho tk ON k.id = tk.kho_id
        LEFT JOIN san_pham sp ON tk.san_pham_id = sp.id
        WHERE k.vung_id = ?
        GROUP BY k.id, k.ten_kho
        ORDER BY total_quantity DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vung_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $warehouseStats = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $warehouseStats = [];
}

$inventoryByCategory = getInventoryByCategory($conn, $vung_id);
$recentOrders = getRecentOrders($conn, $vung_id);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý vùng - <?= htmlspecialchars($vung_info['ten_vung']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.35rem;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fc;
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

        .product-name {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 2px;
        }

        .product-code {
            font-family: 'Nunito', sans-serif;
            font-size: 0.8rem;
            color: #858796;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Quản lý vùng: <?= htmlspecialchars($vung_info['ten_vung']) ?></h1>
            <div class="d-flex gap-2">
                <a href="./export.php" class="btn btn-primary shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Xuất báo cáo
                </a>
                <a href="#" class="btn btn-success shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Thêm đơn hàng
                </a>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Sản phẩm -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Sản phẩm</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['products']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-exclamation-triangle text-warning"></i> 
                                    <?= number_format($stats['low_stock']) ?> sản phẩm sắp hết hàng
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box-open fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh mục -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Danh mục</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['categories']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-chart-pie text-info"></i> 
                                    <?= number_format($stats['total_products_sold']) ?> sản phẩm đã bán
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tags fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kho hàng -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Kho hàng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['warehouses']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-boxes text-primary"></i> 
                                    <?= number_format($stats['inventory']) ?> sản phẩm tồn kho
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-warehouse fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tồn kho -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Tổng tồn kho</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['inventory']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-chart-line text-success"></i> 
                                    <?= number_format($stats['total_products_sold']) ?> sản phẩm đã bán
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-boxes fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thêm hàng thống kê mới -->
        <div class="row">
            <!-- Đơn hàng mới -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Đơn hàng mới</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending_orders']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-clock text-warning"></i> 
                                    <?= number_format($stats['shipping_orders']) ?> đang giao
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Đơn hàng hoàn thành -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Đơn hàng hoàn thành</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['completed_orders']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-check-circle text-success"></i> 
                                    <?= number_format($stats['cancelled_orders']) ?> đơn đã hủy
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm đã bán -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Sản phẩm đã bán</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_products_sold']) ?></div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-box text-warning"></i> 
                                    <?= number_format($stats['low_stock']) ?> sản phẩm sắp hết
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box-open fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doanh thu hôm nay -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Doanh thu hôm nay</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_revenue_today'], 0, ',', '.') ?>₫</div>
                                <div class="mt-2 text-xs text-muted">
                                    <i class="fas fa-calendar-week text-primary"></i> 
                                    <?= number_format($stats['total_revenue_week'], 0, ',', '.') ?>₫ tuần này
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Đơn hàng -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Đơn hàng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['orders']) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Người dùng -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Người dùng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['users']) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doanh thu -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Doanh thu</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['revenue'], 0, ',', '.') ?>₫</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tỷ lệ hoàn thành -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Tỷ lệ hoàn thành</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php 
                                    $total = $stats['orders'];
                                    $completed = $orderStatus['delivered'];
                                    $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
                                    echo $rate.'%';
                                    ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-percent fa-2x text-gray-300 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê doanh thu -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Thống kê doanh thu từ sản phẩm đã bán</h6>
                        <div class="d-flex gap-2">
                            <div class="btn-group me-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="filterByPeriod('day')">Theo ngày</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="filterByPeriod('week')">Theo tuần</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="filterByPeriod('month')">Theo tháng</button>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="switchChart('revenue')">Biểu đồ doanh thu</button>
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
                                    <?php if (empty($monthlyRevenue)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Chưa có dữ liệu doanh thu</td>
                                        </tr>
                                    <?php else: 
                                        $totalRevenue = 0;
                                        $totalQuantity = 0;
                                        foreach ($monthlyRevenue as $product): 
                                            $totalRevenue += $product['total_revenue'];
                                            $totalQuantity += $product['total_quantity'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['ten_san_pham']) ?></td>
                                            <td><?= htmlspecialchars($product['ma_san_pham']) ?></td>
                                            <td><?= number_format($product['total_quantity']) ?></td>
                                            <td><?= number_format($product['unit_price'], 0, ',', '.') ?>₫</td>
                                            <td><?= number_format($product['total_revenue'], 0, ',', '.') ?>₫</td>
                                        </tr>
                                    <?php endforeach; ?>
                                        <tr class="table-info">
                                            <td colspan="2" class="text-right font-weight-bold">Tổng cộng:</td>
                                            <td class="font-weight-bold"><?= number_format($totalQuantity) ?></td>
                                            <td></td>
                                            <td class="font-weight-bold"><?= number_format($totalRevenue, 0, ',', '.') ?>₫</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê sản phẩm -->
        <div class="row">
            <!-- Sản phẩm bán chạy -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Sản phẩm bán chạy</h6>
                        <div class="d-flex gap-2">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="switchTopProductsChart('quantity')">Biểu đồ số lượng</button>
                                <button class="btn btn-sm btn-outline-success" onclick="switchTopProductsChart('revenue')">Biểu đồ doanh thu</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container mb-4" style="height: 300px;">
                                    <canvas id="topProductsQuantityChart"></canvas>
                                    <canvas id="topProductsRevenueChart" style="display: none;"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-hover table-bordered mb-0" width="100%" cellspacing="0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 5%">#</th>
                                                <th style="width: 25%">Sản phẩm</th>
                                                <th class="text-center" style="width: 15%">Đã bán</th>
                                                <th class="text-center" style="width: 15%">Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($topProducts)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">
                                                        <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">Chưa có dữ liệu sản phẩm bán chạy</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($topProducts as $i => $product): ?>
                                                    <tr>
                                                        <td class="text-center align-middle"><?= $i + 1 ?></td>
                                                        <td class="align-middle">
                                                            <div class="d-flex flex-column">
                                                                <div class="product-name"><?= htmlspecialchars($product['ten_san_pham']) ?></div>
                                                                <small class="product-code"><?= htmlspecialchars($product['ma_san_pham']) ?></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-success"><?= number_format($product['total_sold']) ?></span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="fw-bold text-primary"><?= number_format($product['total_revenue'], 0, ',', '.') ?>₫</span>
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
            </div>

            <!-- Thống kê tồn kho theo danh mục -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Tồn kho theo danh mục</h6>
                        <div class="d-flex gap-2">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="switchInventoryChart('quantity')">Biểu đồ số lượng</button>
                                <button class="btn btn-sm btn-outline-success" onclick="switchInventoryChart('value')">Biểu đồ giá trị</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container mb-4" style="height: 300px;">
                                    <canvas id="inventoryQuantityChart"></canvas>
                                    <canvas id="inventoryValueChart" style="display: none;"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Danh mục</th>
                                                <th>Số lượng</th>
                                                <th>Số sản phẩm</th>
                                                <th>Giá trị</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($inventoryByCategory)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">Chưa có dữ liệu tồn kho</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($inventoryByCategory as $category): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($category['category']) ?></td>
                                                    <td><?= number_format($category['total_quantity']) ?></td>
                                                    <td><?= number_format($category['product_count']) ?></td>
                                                    <td><?= number_format($category['total_value'], 0, ',', '.') ?>₫</td>
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
            </div>
        </div>

        <!-- Thống kê kho hàng -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Thống kê kho hàng</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Kho hàng</th>
                                        <th>Số lượng sản phẩm</th>
                                        <th>Tổng tồn kho</th>
                                        <th>Giá trị tồn kho</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalValue = array_sum(array_column($warehouseStats, 'total_value'));
                                    foreach ($warehouseStats as $warehouse): 
                                        $percentage = $totalValue > 0 ? round(($warehouse['total_value'] / $totalValue) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($warehouse['ten_kho']) ?></td>
                                        <td><?= number_format($warehouse['product_count']) ?></td>
                                        <td><?= number_format($warehouse['total_quantity']) ?></td>
                                        <td><?= number_format($warehouse['total_value'], 0, ',', '.') ?>₫</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= $percentage ?>%</div>
                                            </div>
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

        <!-- Đơn hàng gần đây -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Đơn hàng gần đây</h6>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary active" onclick="filterOrders('all')">Tất cả</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="filterOrders('pending')">Chờ duyệt</button>
                            <button class="btn btn-sm btn-outline-info" onclick="filterOrders('approved')">Đã duyệt</button>
                            <button class="btn btn-sm btn-outline-info" onclick="filterOrders('shipped')">Đang giao</button>
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
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Chưa có đơn hàng nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr data-status="<?= $order['status'] ?>">
                                                <td>#<?= $order['id'] ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($order['fullname']) ?></td>
                                                <td>
                                                    <strong>SĐT:</strong> <?= htmlspecialchars($order['phone']) ?><br>
                                                    <small><?= htmlspecialchars($order['email']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="text-wrap">
                                                        <?= htmlspecialchars($order['products']) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</td>
                                                <td>
                                                    <?php 
                                                    $badgeClass = '';
                                                    $statusText = '';
                                                    switch(strtolower($order['status'])) {
                                                        case 'pending':
                                                            $badgeClass = 'badge-pending';
                                                            $statusText = 'Chờ duyệt';
                                                            break;
                                                        case 'approved':
                                                            $badgeClass = 'badge-shipping';
                                                            $statusText = 'Đã duyệt';
                                                            break;
                                                        case 'shipped':
                                                            $badgeClass = 'badge-shipping';
                                                            $statusText = 'Đang giao';
                                                            break;
                                                        case 'delivered':
                                                            $badgeClass = 'badge-completed';
                                                            $statusText = 'Hoàn thành';
                                                            break;
                                                        case 'cancelled':
                                                            $badgeClass = 'badge-cancelled';
                                                            $statusText = 'Đã hủy';
                                                            break;
                                                        default:
                                                            $badgeClass = 'badge-secondary';
                                                            $statusText = 'Không xác định';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="?action=view&id=<?= $order['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <a href="?action=edit&id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Chuẩn bị dữ liệu cho biểu đồ
    const rawData = <?php 
        $chartData = [];
        if (!empty($monthlyRevenue)) {
            $timeSql = "
                SELECT 
                    DATE(o.created_at) as date,
                    WEEK(o.created_at) as week,
                    DATE_FORMAT(o.created_at, '%m/%Y') as month,
                    SUM(od.quantity) as total_quantity,
                    SUM(od.quantity * sp.gia_ban) as total_revenue
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                JOIN san_pham sp ON od.product_id = sp.id
                LEFT JOIN kho k ON o.kho_id = k.id
                WHERE (k.vung_id = ? OR k.vung_id IS NULL) AND o.status = 'delivered'
                GROUP BY date, week, month
                ORDER BY date ASC
            ";
            
            $stmt = $conn->prepare($timeSql);
            $stmt->bind_param("i", $vung_id);
            $stmt->execute();
            $timeResult = $stmt->get_result();
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
        }
        echo json_encode($chartData);
    ?>;

    // Chuẩn bị dữ liệu cho biểu đồ sản phẩm bán chạy
    const topProductsData = <?php
        $topProductsChartData = [];
        if (!empty($topProducts)) {
            foreach ($topProducts as $product) {
                $topProductsChartData[] = [
                    'label' => $product['ten_san_pham'],
                    'value' => $product['total_sold'],
                    'revenue' => $product['total_revenue']
                ];
            }
        }
        echo json_encode($topProductsChartData);
    ?>;

    // Chuẩn bị dữ liệu cho biểu đồ tồn kho theo danh mục
    const inventoryData = <?php
        $inventoryChartData = [];
        if (!empty($inventoryByCategory)) {
            foreach ($inventoryByCategory as $category) {
                $inventoryChartData[] = [
                    'label' => $category['category'],
                    'quantity' => $category['total_quantity'],
                    'value' => $category['total_value']
                ];
            }
        }
        echo json_encode($inventoryChartData);
    ?>;

    // Tạo biểu đồ số lượng sản phẩm bán chạy
    const topProductsQuantityCtx = document.getElementById('topProductsQuantityChart').getContext('2d');
    const topProductsQuantityChart = new Chart(topProductsQuantityCtx, {
        type: 'pie',
        data: {
            labels: topProductsData.map(item => item.label),
            datasets: [{
                data: topProductsData.map(item => item.value),
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(246, 194, 62, 1)',
                    'rgba(231, 74, 59, 1)',
                    'rgba(54, 185, 204, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = topProductsData[context.dataIndex];
                            return [
                                context.label,
                                'Số lượng: ' + context.raw,
                                'Doanh thu: ' + data.revenue.toLocaleString('vi-VN') + '₫'
                            ];
                        }
                    }
                }
            }
        }
    });

    // Tạo biểu đồ doanh thu sản phẩm bán chạy
    const topProductsRevenueCtx = document.getElementById('topProductsRevenueChart').getContext('2d');
    const topProductsRevenueChart = new Chart(topProductsRevenueCtx, {
        type: 'pie',
        data: {
            labels: topProductsData.map(item => item.label),
            datasets: [{
                data: topProductsData.map(item => item.revenue),
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(246, 194, 62, 1)',
                    'rgba(231, 74, 59, 1)',
                    'rgba(54, 185, 204, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = topProductsData[context.dataIndex];
                            return [
                                context.label,
                                'Doanh thu: ' + context.raw.toLocaleString('vi-VN') + '₫',
                                'Số lượng: ' + data.value
                            ];
                        }
                    }
                }
            }
        }
    });

    // Tạo biểu đồ số lượng tồn kho theo danh mục
    const inventoryQuantityCtx = document.getElementById('inventoryQuantityChart').getContext('2d');
    const inventoryQuantityChart = new Chart(inventoryQuantityCtx, {
        type: 'pie',
        data: {
            labels: inventoryData.map(item => item.label),
            datasets: [{
                data: inventoryData.map(item => item.quantity),
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(246, 194, 62, 1)',
                    'rgba(231, 74, 59, 1)',
                    'rgba(54, 185, 204, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = inventoryData[context.dataIndex];
                            return [
                                context.label,
                                'Số lượng: ' + context.raw,
                                'Giá trị: ' + data.value.toLocaleString('vi-VN') + '₫'
                            ];
                        }
                    }
                }
            }
        }
    });

    // Tạo biểu đồ giá trị tồn kho theo danh mục
    const inventoryValueCtx = document.getElementById('inventoryValueChart').getContext('2d');
    const inventoryValueChart = new Chart(inventoryValueCtx, {
        type: 'pie',
        data: {
            labels: inventoryData.map(item => item.label),
            datasets: [{
                data: inventoryData.map(item => item.value),
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(246, 194, 62, 1)',
                    'rgba(231, 74, 59, 1)',
                    'rgba(54, 185, 204, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = inventoryData[context.dataIndex];
                            return [
                                context.label,
                                'Giá trị: ' + context.raw.toLocaleString('vi-VN') + '₫',
                                'Số lượng: ' + data.quantity
                            ];
                        }
                    }
                }
            }
        }
    });

    // Hàm chuyển đổi biểu đồ sản phẩm bán chạy
    function switchTopProductsChart(type) {
        if (type === 'quantity') {
            document.getElementById('topProductsQuantityChart').style.display = 'block';
            document.getElementById('topProductsRevenueChart').style.display = 'none';
        } else {
            document.getElementById('topProductsQuantityChart').style.display = 'none';
            document.getElementById('topProductsRevenueChart').style.display = 'block';
        }
    }

    // Hàm chuyển đổi biểu đồ tồn kho
    function switchInventoryChart(type) {
        if (type === 'quantity') {
            document.getElementById('inventoryQuantityChart').style.display = 'block';
            document.getElementById('inventoryValueChart').style.display = 'none';
        } else {
            document.getElementById('inventoryQuantityChart').style.display = 'none';
            document.getElementById('inventoryValueChart').style.display = 'block';
        }
    }

    let currentPeriod = 'day';
    let currentData = [];

    // Hàm gom nhóm dữ liệu theo khoảng thời gian
    function groupDataByPeriod(data, period) {
        const groupedData = {};
        
        data.forEach(item => {
            let key;
            switch(period) {
                case 'day':
                    key = item.time;
                    break;
                case 'week':
                    key = `Tuần ${item.week}`;
                    break;
                case 'month':
                    key = item.month;
                    break;
            }
            
            if (!groupedData[key]) {
                groupedData[key] = {
                    period: key,
                    revenue: 0,
                    quantity: 0
                };
            }
            
            groupedData[key].revenue += item.revenue;
            groupedData[key].quantity += item.quantity;
        });
        
        return Object.values(groupedData);
    }

    // Hàm cập nhật biểu đồ
    function updateCharts(period) {
        currentPeriod = period;
        currentData = groupDataByPeriod(rawData, period);
        
        revenueChart.data.labels = currentData.map(item => item.period);
        revenueChart.data.datasets[0].data = currentData.map(item => item.revenue);
        revenueChart.update();
        
        quantityChart.data.labels = currentData.map(item => item.period);
        quantityChart.data.datasets[0].data = currentData.map(item => item.quantity);
        quantityChart.update();
    }

    // Khởi tạo biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Doanh thu',
                data: [],
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
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
                            return value.toLocaleString('vi-VN') + '₫';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = currentData[context.dataIndex];
                            return [
                                'Doanh thu: ' + context.raw.toLocaleString('vi-VN') + '₫',
                                'Số lượng: ' + data.quantity
                            ];
                        }
                    }
                }
            }
        }
    });

    // Khởi tạo biểu đồ số lượng
    const quantityCtx = document.getElementById('quantityChart').getContext('2d');
    const quantityChart = new Chart(quantityCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Số lượng bán',
                data: [],
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const data = currentData[context.dataIndex];
                            return [
                                'Số lượng bán: ' + context.raw,
                                'Doanh thu: ' + data.revenue.toLocaleString('vi-VN') + '₫'
                            ];
                        }
                    }
                }
            }
        }
    });

    // Hàm lọc theo khoảng thời gian
    function filterByPeriod(period) {
        updateCharts(period);
    }

    // Hàm chuyển đổi giữa các biểu đồ
    function switchChart(type) {
        if (type === 'revenue') {
            document.getElementById('revenueChart').style.display = 'block';
            document.getElementById('quantityChart').style.display = 'none';
        } else {
            document.getElementById('revenueChart').style.display = 'none';
            document.getElementById('quantityChart').style.display = 'block';
        }
    }

    // Khởi tạo biểu đồ với dữ liệu theo ngày
    updateCharts('day');

    // Hàm lọc đơn hàng theo trạng thái
    function filterOrders(status) {
        const rows = document.querySelectorAll('#recentOrdersTable tbody tr[data-status]');
        rows.forEach(row => {
            const rowStatus = row.dataset.status.toLowerCase();
            const statusMatch = (status === 'all') || 
                               (status === 'pending' && rowStatus === 'pending') ||
                               (status === 'approved' && rowStatus === 'approved') ||
                               (status === 'shipped' && rowStatus === 'shipped') ||
                               (status === 'delivered' && rowStatus === 'delivered') ||
                               (status === 'cancelled' && rowStatus === 'cancelled');
            row.style.display = statusMatch ? '' : 'none';
        });
        
        document.querySelectorAll('.card-header .btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.btn[onclick="filterOrders('${status}')"]`).classList.add('active');
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>