<?php
// Kết nối database
$conn = mysqli_connect("localhost", "root", "", "quan_ly_kho");

// Lấy ID sản phẩm cần xóa
$id_san_pham = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_san_pham > 0) {
    // Xóa dữ liệu liên quan trước
    mysqli_query($conn, "DELETE FROM chi_tiet_phieu_nhap WHERE san_pham_id = $id_san_pham");
    mysqli_query($conn, "DELETE FROM chi_tiet_phieu_xuat WHERE san_pham_id = $id_san_pham");
    mysqli_query($conn, "DELETE FROM ton_kho WHERE san_pham_id = $id_san_pham");

    // Xóa sản phẩm chính
    mysqli_query($conn, "DELETE FROM san_pham WHERE id = $id_san_pham");

    echo "Xóa sản phẩm thành công!";
} else {
    echo "ID sản phẩm không hợp lệ!";
}

// Đóng kết nối
mysqli_close($conn);
?>
