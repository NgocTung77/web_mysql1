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

$conn->set_charset("utf8");

// Lấy vai trò và thông tin phân quyền
$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? '';
$kho_id = $_SESSION['kho_id'] ?? '';

if (!in_array($vai_tro, ['admin', 'quan_ly_vung', 'quan_ly_kho'])) {
    die("Bạn không có quyền truy cập chức năng này");
}

// Xử lý lọc dữ liệu
$filter_results = null;
$filter = "WHERE 1=1";
$params = [];
$types = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!empty($from_date)) {
        $filter .= " AND DATE(px.ngay_xuat) >= ?";
        $params[] = $from_date;
        $types .= 's';
    }
    
    if (!empty($to_date)) {
        $filter .= " AND DATE(px.ngay_xuat) <= ?";
        $params[] = $to_date;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $filter .= " AND px.trang_thai = ?";
        $params[] = $status;
        $types .= 's';
    }
}

// Áp dụng phân quyền
if ($vai_tro == 'quan_ly_vung') {
    $filter .= " AND k.vung_id = ?";
    $params[] = $vung_id;
    $types .= 'i';
} elseif ($vai_tro == 'quan_ly_kho') {
    $filter .= " AND px.kho_id = ?";
    $params[] = $kho_id;
    $types .= 'i';
}

// Lấy danh sách phiếu xuất
$query = "SELECT px.*, k.ten_kho AS ten_kho_xuat, k2.ten_kho AS ten_kho_dich,
          u1.ho_ten AS nguoi_tao_ten
          FROM phieu_xuat px
          JOIN kho k ON px.kho_id = k.id
          LEFT JOIN kho k2 ON px.kho_dich_id = k2.id
          JOIN nguoi_dung u1 ON px.nguoi_xuat = u1.id
          $filter
          ORDER BY px.ngay_xuat DESC";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$filter_results = $stmt->get_result();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="dashboard-card">
                <div class="card-header">
                    <h4><i class="fas fa-list-alt me-2"></i>LỊCH SỬ XUẤT KHO</h4>
                </div>

                <div class="card-body">
                    <div class="filter-section mb-4">
                        <form method="POST" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Từ ngày</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo isset($_POST['from_date']) ? htmlspecialchars($_POST['from_date']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Đến ngày</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo isset($_POST['to_date']) ? htmlspecialchars($_POST['to_date']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="pending" <?php echo isset($_POST['status']) && $_POST['status'] == 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="completed" <?php echo isset($_POST['status']) && $_POST['status'] == 'completed' ? 'selected' : ''; ?>>Đã duyệt</option>
                                    <option value="rejected" <?php echo isset($_POST['status']) && $_POST['status'] == 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="filter" class="btn btn-detail">
                                    <i class="fas fa-filter me-2"></i>Lọc
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã phiếu</th>
                                    <th>Kho xuất</th>
                                    <th>Kho đích</th>
                                    <th>Ngày tạo</th>
                                    <th>Người tạo</th>
                                    <th>Trạng thái</th>
                                    <th>Tổng tiền</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($filter_results && $filter_results->num_rows > 0): ?>
                                    <?php while ($row = $filter_results->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['ma_phieu']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ten_kho_xuat']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ten_kho_dich'] ?? 'Không có'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['ngay_xuat'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nguoi_tao_ten']); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $row['trang_thai'] == 'completed' ? 'bg-success' : 
                                                         ($row['trang_thai'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                                ?>">
                                                    <?php 
                                                    echo $row['trang_thai'] == 'completed' ? 'Đã duyệt' : 
                                                         ($row['trang_thai'] == 'rejected' ? 'Từ chối' : 'Chờ duyệt');
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="total-amount text-end"><?php echo number_format($row['tong_tien'], 0, ',', '.') . ' đ'; ?></td>
                                            <td>
                                                <a href="index.php?quanly=quanlykho&ac=chitietxuatkho&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-detail">
                                                    <i class="fas fa-eye me-1"></i>Chi tiết
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Không có dữ liệu để hiển thị.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

.table {
    margin: 0;
    color: var(--dark-color);
}

.table thead th {
    background: var(--primary-color);
    color: white;
    font-weight: 500;
    border: none;
    padding: 1rem;
}

.table tbody tr:hover {
    background: var(--secondary-color);
}

.btn-detail {
    background: var(--info-color);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: all var(--transition-speed);
}

.btn-detail:hover {
    background: var(--primary-color);
    transform: translateY(-1px);
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    font-weight: 500;
}

.badge.bg-success {
    background: var(--success-color) !important;
}

.badge.bg-danger {
    background: var(--danger-color) !important;
}

.badge.bg-warning {
    background: var(--info-color) !important;
}

.total-amount {
    font-weight: 600;
    color: var(--primary-color);
}

.text-center {
    text-align: center;
}
</style>

<?php
$stmt->close();
$conn->close();
?>