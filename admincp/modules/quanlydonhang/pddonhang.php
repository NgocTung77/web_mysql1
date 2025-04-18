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

// Lấy danh sách đơn hàng theo trạng thái
function getOrdersByStatus($conn, $status) {
    $sql = "SELECT o.*, u.ten_user 
            FROM orders o 
            JOIN user u ON o.user_id = u.id_user 
            WHERE o.status = ?
            ORDER BY o.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result();
}

$pending_orders = getOrdersByStatus($conn, 'pending');
$shipping_orders = getOrdersByStatus($conn, 'shipped');
$delivered_orders = getOrdersByStatus($conn, 'delivered');
$cancelled_orders = getOrdersByStatus($conn, 'cancelled');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-header {
            font-weight: bold;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-pending { background-color: #FFC107; color: #000; }
        .badge-approved { background-color: #17A2B8; color: #FFF; }
        .badge-delivered { background-color: #28A745; color: #FFF; }
        .badge-cancelled { background-color: #DC3545; color: #FFF; }
        .status-select {
            min-width: 140px;
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #CED4DA;
        }
        .no-orders {
            padding: 20px;
            text-align: center;
            color: #6C757D;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4 text-center">QUẢN LÝ ĐƠN HÀNG</h1>
        
        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Đơn hàng chờ duyệt -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-clock me-2"></i> ĐƠN HÀNG CHỜ DUYỆT
            </div>
            <div class="card-body p-0">
                <?php if ($pending_orders->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã ĐH</th>
                                    <th>Khách Hàng</th>
                                    <th>Điện Thoại</th>
                                    <th>Tổng Tiền</th>
                                    <th>Ngày Tạo</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $pending_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['id']) ?></td>
                                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= number_format($order['total_amount']) ?>đ</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><span class="status-badge badge-pending">Chờ duyệt</span></td>
                                    <td>
                                        <form method="post" action="modules/quanlydonhang/xuly_donhang.php" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select class="status-select" name="status" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Đang giao</option>
                                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Hoàn thành</option>
                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Hủy đơn</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                        <p>Không có đơn hàng chờ duyệt</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Đơn hàng đang giao -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-truck me-2"></i> ĐƠN HÀNG ĐANG GIAO
            </div>
            <div class="card-body p-0">
                <?php if ($shipping_orders->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã ĐH</th>
                                    <th>Khách Hàng</th>
                                    <th>Điện Thoại</th>
                                    <th>Tổng Tiền</th>
                                    <th>Ngày Tạo</th>
                                    <th>Ngày Duyệt</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $shipping_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['id']) ?></td>
                                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= number_format($order['total_amount']) ?>đ</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></td>
                                    <td><span class="status-badge badge-approved">Đang giao</span></td>
                                    <td>
                                        <form method="post" action="modules/quanlydonhang/xuly_donhang.php" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select class="status-select" name="status" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Đang giao</option>
                                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Hoàn thành</option>
                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Hủy đơn</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-truck fa-2x mb-2"></i>
                        <p>Không có đơn hàng đang giao</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Đơn hàng đã hoàn thành -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i> ĐƠN HÀNG ĐÃ HOÀN THÀNH
            </div>
            <div class="card-body p-0">
                <?php if ($delivered_orders->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã ĐH</th>
                                    <th>Khách Hàng</th>
                                    <th>Điện Thoại</th>
                                    <th>Tổng Tiền</th>
                                    <th>Ngày Tạo</th>
                                    <th>Ngày Hoàn Thành</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $delivered_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_code']) ?></td>
                                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= number_format($order['total_amount']) ?>đ</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></td>
                                    <td><span class="status-badge badge-delivered">Hoàn thành</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p>Không có đơn hàng đã hoàn thành</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Đơn hàng đã hủy -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-times-circle me-2"></i> ĐƠN HÀNG ĐÃ HỦY
            </div>
            <div class="card-body p-0">
                <?php if ($cancelled_orders->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã ĐH</th>
                                    <th>Khách Hàng</th>
                                    <th>Điện Thoại</th>
                                    <th>Tổng Tiền</th>
                                    <th>Ngày Tạo</th>
                                    <th>Ngày Hủy</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $cancelled_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_code']) ?></td>
                                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= number_format($order['total_amount']) ?>đ</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></td>
                                    <td><span class="status-badge badge-cancelled">Đã hủy</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <p>Không có đơn hàng đã hủy</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>