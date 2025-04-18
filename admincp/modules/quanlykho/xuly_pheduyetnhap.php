<?php
session_start();
include('../config.php');

if (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit();
}

if (!isset($_POST['phieu_id']) || !isset($_POST['action'])) {
    $_SESSION['message'] = "Dữ liệu không hợp lệ!";
    header('Location: pheduyet_nhapkho.php');
    exit();
}

$phieu_id = intval($_POST['phieu_id']);
$action = $_POST['action'];

// Kiểm tra giá trị action hợp lệ
if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['message'] = "Hành động không hợp lệ!";
    header('Location: pheduyet_nhapkho.php');
    exit();
}

try {
    // Sử dụng kết nối từ config.php
    if ($conn->connect_error) {
        throw new Exception("Kết nối thất bại: " . $conn->connect_error);
    }

    // Kiểm tra trạng thái hiện tại của phiếu
    $sql_check = "SELECT trang_thai, kho_id FROM phieu_nhap WHERE id = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("i", $phieu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $phieu = $result->fetch_assoc();

    if (!$phieu) {
        throw new Exception("Không tìm thấy phiếu nhập!");
    }

    if ($phieu['trang_thai'] !== 'pending') {
        throw new Exception("Phiếu nhập đã được xử lý trước đó!");
    }

    // Bắt đầu transaction
    $conn->begin_transaction();

    if ($action === 'approve') {
        // Cập nhật trạng thái phiếu nhập
        $sql_update = "UPDATE phieu_nhap SET trang_thai = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("i", $phieu_id);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật trạng thái phiếu: " . $conn->error);
        }

        // Cập nhật tồn kho
        $sql_products = "SELECT san_pham_id, so_luong FROM chi_tiet_phieu_nhap WHERE phieu_nhap_id = ?";
        $stmt = $conn->prepare($sql_products);
        $stmt->bind_param("i", $phieu_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $san_pham_id = $row['san_pham_id'];
            $so_luong = $row['so_luong'];
            $kho_id = $phieu['kho_id'];

            // Kiểm tra tồn kho
            $sql_check_ton = "SELECT so_luong FROM ton_kho WHERE san_pham_id = ? AND kho_id = ?";
            $stmt = $conn->prepare($sql_check_ton);
            $stmt->bind_param("ii", $san_pham_id, $kho_id);
            $stmt->execute();
            $ton_result = $stmt->get_result();

            if ($ton_result->num_rows > 0) {
                // Cập nhật số lượng
                $sql_update_kho = "UPDATE ton_kho SET so_luong = so_luong + ? WHERE san_pham_id = ? AND kho_id = ?";
                $stmt = $conn->prepare($sql_update_kho);
                $stmt->bind_param("iii", $so_luong, $san_pham_id, $kho_id);
            } else {
                // Thêm mới
                $sql_insert_kho = "INSERT INTO ton_kho (san_pham_id, kho_id, so_luong) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql_insert_kho);
                $stmt->bind_param("iii", $san_pham_id, $kho_id, $so_luong);
            }

            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi cập nhật tồn kho: " . $conn->error);
            }
        }

        $_SESSION['message'] = "Đã phê duyệt phiếu nhập thành công!";
    } elseif ($action === 'reject') {
        // Cập nhật trạng thái phiếu nhập
        $sql_update = "UPDATE phieu_nhap SET trang_thai = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("i", $phieu_id);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi từ chối phiếu: " . $conn->error);
        }
        $_SESSION['message'] = "Đã từ chối phiếu nhập!";
    }

    // Commit transaction
    $conn->commit();
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['message'] = "Có lỗi xảy ra: " . $e->getMessage();
} finally {
    $conn->close();
}

header('Location: pheduyet_nhapkho.php');
exit();
?>