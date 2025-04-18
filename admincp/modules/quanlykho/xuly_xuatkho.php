<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quan_ly_kho");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kho_xuat = $_POST['kho_xuat'];
    $san_pham_id = $_POST['san_pham_id'];
    $so_luong = $_POST['so_luong'];
    $kho_dich = $_POST['kho_dich'];
    $ghi_chu = $_POST['ghi_chu'];
    $nguoi_tao = $_SESSION['id'];
    
    // Kiểm tra số lượng tồn kho
    $query_ton = "SELECT so_luong FROM ton_kho WHERE kho_id = ? AND san_pham_id = ?";
    $stmt = $conn->prepare($query_ton);
    $stmt->bind_param("ii", $kho_xuat, $san_pham_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row_ton = $result->fetch_assoc();
    
    if ($row_ton['so_luong'] < $so_luong) {
        header("Location: ../xuat_kho.php?status=error&message=Số lượng không đủ trong kho xuất");
        exit();
    }
    
    // Lấy thông tin sản phẩm
    $query_sp = "SELECT ma_san_pham, ten_san_pham, gia_ban FROM san_pham WHERE id = ?";
    $stmt = $conn->prepare($query_sp);
    $stmt->bind_param("i", $san_pham_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $san_pham = $result->fetch_assoc();
    
    // Tạo phiếu xuất chờ phê duyệt
    $ma_phieu = "PX" . date('YmdHis');
    $tong_tien = $so_luong * $san_pham['gia_ban'];
    
    $query = "INSERT INTO phieu_xuat (ma_phieu, kho_id, kho_dich_id, nguoi_xuat, ngay_xuat, ghi_chu, tong_tien, trang_thai) 
              VALUES (?, ?, ?, ?, NOW(), ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiisd", $ma_phieu, $kho_xuat, $kho_dich, $nguoi_tao, $ghi_chu, $tong_tien);
    $stmt->execute();
    $phieu_xuat_id = $stmt->insert_id;
    
    // Thêm chi tiết phiếu xuất
    $query_ct = "INSERT INTO chi_tiet_phieu_xuat (phieu_xuat_id, san_pham_id, so_luong, gia_ban, don_vi_tinh) 
                 VALUES (?, ?, ?, ?, 'Cái')";
    $stmt = $conn->prepare($query_ct);
    $stmt->bind_param("iiid", $phieu_xuat_id, $san_pham_id, $so_luong, $san_pham['gia_ban']);
    $stmt->execute();
    
    // Chuyển hướng với thông báo thành công
    header("Location: http://localhost/web_mysql1/admincp/index.php?quanly=quanlyxuatkho");
    exit();
}
?>