<?php  

$vai_tro = $_SESSION['vai_tro'] ?? '';

$tam = $_GET['ac'] ?? '';




if ($tam == 'them') {
    include('modules/quanlykho/them_kho.php');
} elseif ($tam == 'sua' && isset($_GET['id'])) {
    include('modules/quanlykho/sua_kho.php');
} else {
    // hiển thị trang mặc định theo vai trò
    if ($vai_tro == 'admin') {
       
        include('modules/admin/dashboard_admin.php');
    } elseif ($vai_tro == 'quan_ly_vung') {
        
        include('modules/admin/dashboard_vung.php');
    } elseif ($vai_tro == 'quan_ly_kho') {
        
        include('modules/admin/dashboard_kho.php');
    } else {
        echo "Vai trò không hợp lệ hoặc chưa đăng nhập.<br>";
    }
}
?>
