<div class="content-wrapper">
    <?php
    $vai_tro = $_SESSION['vai_tro'] ?? ''; 
    $tam = $_GET['quanly'] ?? ''; 

    switch ($tam) {
        case 'admin':
            include('modules/admin/main.php');
            break;
        case 'quanlykho':
            include('modules/quanlykho/main.php');
            break;
        case 'quanlynhapkho':
                include('modules/quanlykho/nhap_kho.php');
            break;
        case 'quanlyxuatkho':
                include('modules/quanlykho/xuat_kho.php');
            break;
        case 'quanlyloaisp':
                include('modules/quanlyloaisp/main.php');
            break;
        case 'pheduyetnhapkho':
                include('modules/quanlykho/pheduyet_nhapkho.php');
            break;
        case 'pheduyetxuatkho':
                include('modules/quanlykho/pheduyet_xuatkho.php');
            break;
        case 'quanlychitietsp':
                include('modules/quanlychitietsp/main.php');
            break;
        case 'quanlykhachhang':
                include('modules/hienthi/DanhSachKhachHang.php');
            break;            
        case 'quanlydonhang':
                include('modules/quanlydonhang/pddonhang.php');
            break;
        case 'loinhuan':
                include('modules/quanlyloinhuan/loinhuan.php');
            break;
        case 'chitietloinhuan':
                include('modules/quanlyloinhuan/loinhuan_chitiet_kho.php');
            break;
        case 'chitietloinhuan_sanpham':
                include('modules/quanlyloinhuan/loinhuan_chitiet_sanpham.php');
            break;
        case 'danhsachdonhang':
                include('modules/hienthi/DanhSachDonHang.php');
            break;
        case 'chitietdonhang':
                include('modules/hienthi/ChiTietDonHang.php');
            break;
        default: 
            if ($vai_tro === 'admin') {
                include('modules/admin/dashboard_admin.php');
            } elseif ($vai_tro === 'quan_ly_vung') {
                include('modules/admin/dashboard_vung.php'); 
            } else {
                include('modules/admin/dashboard_kho.php');
            }
            break;
    }
    ?>
</div>

<div class="clear"></div>
