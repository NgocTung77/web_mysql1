<?php
if (isset($_GET['ac'])) {
    if ($_GET['ac'] == 'sua' && isset($_GET['id'])) {
        include('modules/quanlykho/sua_kho.php');
    } elseif ($_GET['ac'] == 'chitietxuatkho' && isset($_GET['id'])) {
        include('modules/hienthi/ChiTietXuatKho.php');
    }
    elseif($_GET['ac'] == 'chitietnhapkho' && isset($_GET['id'])){
        include('modules/hienthi/ChiTietNhapKho.php');
    }
    
    else {
        include('modules/quanlykho/them_kho.php');
    }
} else {
    include('modules/quanlykho/them_kho.php');
}
?>
