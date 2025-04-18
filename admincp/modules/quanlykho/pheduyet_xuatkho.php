<?php
// session_start(); // Đảm bảo session được khởi động

$conn = new mysqli("localhost", "root", "", "quan_ly_kho");
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Bật hiển thị lỗi trong quá trình debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$thong_bao = ''; // Khởi tạo biến thông báo

// ==== XỬ LÝ POST PHÊ DUYỆT HOẶC TỪ CHỐI ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra phieu_id có tồn tại và hợp lệ không
    if (!isset($_POST['phieu_id']) || !is_numeric($_POST['phieu_id'])) {
        $thong_bao = [
            'type' => 'error',
            'message' => "❌ Lỗi: ID phiếu không hợp lệ!"
        ];
    } else {
        $phieu_id = (int)$_POST['phieu_id'];
        $action = $_POST['action'];

        // Kiểm tra xem phiếu có tồn tại và ở trạng thái pending không
        $sql_check = "SELECT id, trang_thai FROM phieu_xuat WHERE id = ? AND trang_thai = 'pending'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $phieu_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows === 0) {
            $thong_bao = [
                'type' => 'error',
                'message' => "❌ Lỗi: Không tìm thấy phiếu ID $phieu_id trong cơ sở dữ liệu hoặc phiếu không ở trạng thái chờ duyệt!"
            ];
        } else {
            if ($action === 'approve') {
                // 1. Cập nhật trạng thái phiếu
                $conn->begin_transaction();
                try {
                    // Kiểm tra tồn kho trước khi xuất
                    $sql_check_ton = "SELECT ctpx.san_pham_id, ctpx.so_luong, tk.so_luong as ton_kho
                                    FROM chi_tiet_phieu_xuat ctpx 
                                    JOIN ton_kho tk ON ctpx.san_pham_id = tk.san_pham_id
                                    WHERE ctpx.phieu_xuat_id = ? AND tk.kho_id = (SELECT kho_id FROM phieu_xuat WHERE id = ?)";
                    $stmt = $conn->prepare($sql_check_ton);
                    $stmt->bind_param("ii", $phieu_id, $phieu_id);
                    $stmt->execute();
                    $result_check = $stmt->get_result();

                    while ($row = $result_check->fetch_assoc()) {
                        if ($row['so_luong'] > $row['ton_kho']) {
                            throw new Exception("Số lượng xuất vượt quá tồn kho cho sản phẩm ID: " . $row['san_pham_id']);
                        }
                    }

                    $sql_update = "UPDATE phieu_xuat SET trang_thai = 'completed' WHERE id = ?";
                    $stmt = $conn->prepare($sql_update);
                    $stmt->bind_param("i", $phieu_id);
                    $stmt->execute();

                    // 2. Lấy chi tiết phiếu để cập nhật kho
                    $sql_ct = "SELECT * FROM chi_tiet_phieu_xuat WHERE phieu_xuat_id = ?";
                    $stmt = $conn->prepare($sql_ct);
                    $stmt->bind_param("i", $phieu_id);
                    $stmt->execute();
                    $ct_result = $stmt->get_result();

                    while ($row = $ct_result->fetch_assoc()) {
                        $sp_id = $row['san_pham_id'];
                        $so_luong = $row['so_luong'];

                        // Trừ kho xuất
                        $sql_update_kho_xuat = "UPDATE ton_kho SET so_luong = so_luong - ? 
                                                WHERE san_pham_id = ? AND kho_id = 
                                                (SELECT kho_id FROM phieu_xuat WHERE id = ?)";
                        $stmt = $conn->prepare($sql_update_kho_xuat);
                        $stmt->bind_param("iii", $so_luong, $sp_id, $phieu_id);
                        $stmt->execute();

                        if ($stmt->affected_rows === 0) {
                            throw new Exception("Không thể cập nhật tồn kho xuất cho sản phẩm ID: " . $sp_id);
                        }

                        // Cộng vào kho đích
                        $sql_check_kho_dich = "SELECT * FROM ton_kho 
                                               WHERE san_pham_id = ? AND kho_id = 
                                               (SELECT kho_dich_id FROM phieu_xuat WHERE id = ?)";
                        $stmt = $conn->prepare($sql_check_kho_dich);
                        $stmt->bind_param("ii", $sp_id, $phieu_id);
                        $stmt->execute();
                        $check = $stmt->get_result();

                        if ($check->num_rows > 0) {
                            $sql_update_kho_dich = "UPDATE ton_kho SET so_luong = so_luong + ? 
                                                    WHERE san_pham_id = ? AND kho_id = 
                                                    (SELECT kho_dich_id FROM phieu_xuat WHERE id = ?)";
                            $stmt = $conn->prepare($sql_update_kho_dich);
                            $stmt->bind_param("iii", $so_luong, $sp_id, $phieu_id);
                            $stmt->execute();
                        } else {
                            $sql_insert_kho_dich = "INSERT INTO ton_kho (san_pham_id, kho_id, so_luong) 
                                                    SELECT ?, kho_dich_id, ? FROM phieu_xuat WHERE id = ?";
                            $stmt = $conn->prepare($sql_insert_kho_dich);
                            $stmt->bind_param("iii", $sp_id, $so_luong, $phieu_id);
                            $stmt->execute();
                        }
                    }

                    $conn->commit();
                    $thong_bao = [
                        'type' => 'success',
                        'message' => "✔️ Phiếu ID $phieu_id đã được duyệt và chuyển sang trạng thái 'completed'."
                    ];
                } catch (Exception $e) {
                    $conn->rollback();
                    $thong_bao = [
                        'type' => 'error',
                        'message' => "❌ Lỗi duyệt: " . $e->getMessage()
                    ];
                }

            } elseif ($action === 'reject') {
                $ghi_chu = $conn->real_escape_string($_POST['ghi_chu']);
                $sql_update = "UPDATE phieu_xuat SET trang_thai = 'rejected', ghi_chu = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("si", $ghi_chu, $phieu_id);
                if ($stmt->execute()) {
                    $thong_bao = [
                        'type' => 'warning',
                        'message' => "🚫 Đã từ chối phiếu ID $phieu_id"
                    ];
                } else {
                    $thong_bao = [
                        'type' => 'error',
                        'message' => "❌ Lỗi khi từ chối phiếu: " . $conn->error
                    ];
                }
            }
        }
    }
}

// ==== LẤY DANH SÁCH PHIẾU CHỜ DUYỆT ====
$query = "SELECT px.*, k1.ten_kho AS ten_kho_xuat, k2.ten_kho AS ten_kho_dich, 
                 u1.ho_ten AS nguoi_tao_ten
          FROM phieu_xuat px
          JOIN kho k1 ON px.kho_id = k1.id
          LEFT JOIN kho k2 ON px.kho_dich_id = k2.id
          JOIN nguoi_dung u1 ON px.nguoi_xuat = u1.id
          WHERE px.trang_thai = 'pending'";

if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] == 'quan_ly_vung') {
    $query .= " AND k1.vung_id = " . (int)$_SESSION['vung_id'];
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt phiếu xuất</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
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
            padding-top: 20px;
        }

        .container {
            max-width: 1400px;
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
        }

        .btn-approve {
            background: var(--success-color);
            color: white;
            border: none;
        }

        .btn-reject {
            background: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-approve:hover,
        .btn-reject:hover {
            opacity: 0.9;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-content h3 {
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .modal-content textarea {
            width: 100%;
            height: 100px;
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 1rem;
        }

        .modal-content textarea:focus {
            border-color: var(--info-color);
            box-shadow: 0 0 0 0.25rem rgba(129, 161, 193, 0.25);
            outline: none;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: var(--dark-color);
            border: none;
        }

        .btn-secondary:hover {
            background: var(--info-color);
            color: white;
        }

        /* Alert styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }

        .alert-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Thông báo -->
        <?php if (!empty($thong_bao)): ?>
            <div class="alert alert-<?= $thong_bao['type'] ?>">
                <?= $thong_bao['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Danh sách phiếu chờ duyệt -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-clipboard-list me-2"></i>DANH SÁCH PHIẾU XUẤT CHỜ DUYỆT</h4>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Kho xuất</th>
                                <th>Kho đích</th>
                                <th>Người tạo</th>
                                <th>Ngày tạo</th>
                                <th>Lý do</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['ma_phieu']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho_xuat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho_dich'] ?? 'Không có kho đích'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nguoi_tao_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ngay_xuat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ly_do'] ?? 'Không có'); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="phieu_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-action btn-approve me-2">
                                                <i class="fas fa-check"></i> Duyệt
                                            </button>
                                        </form>
                                        <?php if (isset($row['id']) && !empty($row['id'])): ?>
                                            <button class="btn btn-action btn-reject" data-id="<?php echo htmlspecialchars($row['id']); ?>">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-action btn-reject" disabled>
                                                <i class="fas fa-times"></i> Từ chối (ID không hợp lệ)
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Không có phiếu xuất nào đang chờ phê duyệt</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal từ chối -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-circle me-2"></i>Lý do từ chối</h3>
            <form method="POST">
                <input type="hidden" name="phieu_id" id="reject_phieu_id">
                <input type="hidden" name="action" value="reject">
                <textarea name="ghi_chu" required placeholder="Nhập lý do từ chối..."></textarea>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <button type="submit" class="btn btn-action btn-reject me-2">
                        <i class="fas fa-check me-1"></i>Xác nhận từ chối
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times me-1"></i>Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý modal từ chối
        const rejectButtons = document.querySelectorAll('.btn-reject');
        if (rejectButtons.length > 0) {
            rejectButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const phieuId = btn.getAttribute('data-id');
                    if (phieuId && phieuId !== 'undefined') {
                        document.getElementById('reject_phieu_id').value = phieuId;
                        document.getElementById('rejectModal').style.display = 'flex';
                    } else {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-error';
                        alertDiv.textContent = '❌ Lỗi: Không tìm thấy ID phiếu!';
                        document.querySelector('.container').prepend(alertDiv);
                        setTimeout(() => alertDiv.remove(), 5000);
                    }
                });
            });
        }

        function closeModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        window.addEventListener('click', (event) => {
            if (event.target === document.getElementById('rejectModal')) {
                closeModal();
            }
        });

        // Tự động ẩn thông báo sau 5 giây
        <?php if (!empty($thong_bao)): ?>
            setTimeout(() => {
                document.querySelector('.alert').style.display = 'none';
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>