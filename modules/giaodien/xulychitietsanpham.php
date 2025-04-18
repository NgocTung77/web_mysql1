<?php
$id_sp = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id_sp)) {
    die("ID sản phẩm không hợp lệ.");
}

// Truy vấn để lấy chi tiết sản phẩm
$sql_chitiet = "SELECT chitietsp.*, loaisp.tenloaisp 
                FROM chitietsp 
                JOIN loaisp ON chitietsp.id_loaisp = loaisp.id_loaisp 
                WHERE chitietsp.id_sp = '$id_sp' 
                LIMIT 1";
$query_chitiet = mysqli_query($conn, $sql_chitiet);

if (!$query_chitiet) {
    die("Truy vấn thất bại: " . mysqli_error($conn));
}

$row_chitiet = mysqli_fetch_array($query_chitiet);
?>
