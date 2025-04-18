<?php
session_start();

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kho_id = $_POST['kho_id'];
    $nguoi_nhap = $_SESSION['id'];
    $ma_phieu = "PN-" . time();
    $trang_thai = "pending"; 
    $tong_tien = 0; 

    $sql = "INSERT INTO phieu_nhap (kho_id, nguoi_nhap, ma_phieu, trang_thai, tong_tien) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $kho_id, $nguoi_nhap, $ma_phieu, $trang_thai, $tong_tien);

    if ($stmt->execute()) {
        $phieu_nhap_id = $stmt->insert_id;

        // Lưu chi tiết phiếu nhập
        foreach ($_POST['san_pham_id'] as $index => $sp_id) {
            $so_luong = $_POST['so_luong'][$index];
            $gia_nhap = $_POST['gia_nhap'][$index];
            $gia_nhap_tron = $_POST['gia_nhap_tron'][$index]; 
            $do_lech = $_POST['do_lech'][$index];
            $gia_ban = $_POST['gia_ban'][$index]; 
            $don_vi_tinh = $_POST['don_vi_tinh'][$index];

            $thanh_tien = $so_luong * $gia_nhap_tron;
            $tong_tien += $thanh_tien;

            $sql_ct = "INSERT INTO chi_tiet_phieu_nhap (phieu_nhap_id, san_pham_id, so_luong, gia_nhap, don_vi_tinh, do_lech) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_ct = $conn->prepare($sql_ct);
            $stmt_ct->bind_param("iiidsd", $phieu_nhap_id, $sp_id, $so_luong, $gia_nhap, $don_vi_tinh, $do_lech);
            $stmt_ct->execute();

            $sql_update_sp = "UPDATE san_pham SET gia_ban = ? WHERE id = ?";
            $stmt_update_sp = $conn->prepare($sql_update_sp);
            $stmt_update_sp->bind_param("di", $gia_ban, $sp_id);
            $stmt_update_sp->execute();
        }

        $sql_update_pn = "UPDATE phieu_nhap SET tong_tien = ? WHERE id = ?";
        $stmt_update_pn = $conn->prepare($sql_update_pn);
        $stmt_update_pn->bind_param("di", $tong_tien, $phieu_nhap_id);
        $stmt_update_pn->execute();

        $_SESSION['message'] = "Tạo phiếu nhập thành công! Đang chờ phê duyệt.";
        header('Location: http://localhost/web_mysql1/admincp/index.php?quanly=quanlynhapkho');
    } else {
        echo "<div class='alert alert-danger'>Lỗi khi tạo phiếu nhập.</div>";
    }
}
?>
