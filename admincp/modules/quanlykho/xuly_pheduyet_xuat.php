<?php

// Giả sử ID admin đang đăng nhập
$_SESSION['id'] = 1;

$conn = new mysqli("localhost", "root", "", "quan_ly_kho");
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phieu_id = $_POST['phieu_id'];
    $action = $_POST['action'];

    // Lấy thông tin phiếu
    $sql = "SELECT * FROM phieu_xuat WHERE id = $phieu_id";
    $phieu = $conn->query($sql)->fetch_assoc();

    // Lấy chi tiết phiếu xuất
    $sql_ct = "SELECT * FROM chi_tiet_phieu_xuat WHERE phieu_xuat_id = $phieu_id";
    $ct = $conn->query($sql_ct)->fetch_assoc();

    if ($action == 'approve') {
        $conn->begin_transaction();
        try {
            // Trừ kho xuất
            $sql1 = "UPDATE ton_kho SET so_luong = so_luong - {$ct['so_luong']} 
                     WHERE kho_id = {$phieu['kho_id']} AND san_pham_id = {$ct['san_pham_id']}";
            $conn->query($sql1);

            // Cộng kho đích
            $sql2 = "INSERT INTO ton_kho (kho_id, san_pham_id, so_luong) 
                     VALUES ({$phieu['kho_dich_id']}, {$ct['san_pham_id']}, {$ct['so_luong']})
                     ON DUPLICATE KEY UPDATE so_luong = so_luong + {$ct['so_luong']}";
            $conn->query($sql2);

            // Cập nhật trạng thái
            $sql3 = "UPDATE phieu_xuat SET trang_thai = 'completed', 
                     nguoi_duyet = {$_SESSION['user_id']}, ngay_duyet = NOW()
                     WHERE id = $phieu_id";
            $conn->query($sql3);

            $conn->commit();
            echo "<script>alert('Đã duyệt phiếu thành công'); window.location.href='pheduyetxuatkho.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "Lỗi: " . $e->getMessage();
        }
    } else {
        // Từ chối phiếu
        $ghi_chu = $_POST['ghi_chu'] ?? 'Từ chối';
        $sql = "UPDATE phieu_xuat SET trang_thai = 'cancelled', 
                nguoi_duyet = {$_SESSION['user_id']}, ngay_duyet = NOW(), ghi_chu = '$ghi_chu'
                WHERE id = $phieu_id";
        $conn->query($sql);

        echo "<script>alert('Đã từ chối phiếu'); window.location.href='pheduyetxuatkho.php';</script>";
    }
}
?>
