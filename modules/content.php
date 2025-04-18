
            <div class="left">
            </div>
    <div class="content">
            <div class="right">  
                <?php
                    if(isset($_GET['xem'])){    
                        $tam=$_GET['xem'];
                    }
                    else{
                        $tam='';
                    }
                    if($tam=='chitietloaisanpham'){
                        include('modules/giaodien/chitietloaisanpham.php');
                    }
                    elseif($tam=='chitietsanpham'){
                        include('modules/giaodien/giaodien_chitietsp.php');
                    }
                    elseif($tam=='dangky'){
                        include('modules/giaodien/register_users.php');
                    }
                    elseif($tam=='dangnhap'){
                        include('modules/giaodien/login_user.php');
                    }
                    elseif($tam=='dangnhapadmin'){
                        include('modules/giaodien/login_admin.php');
                    }
                    elseif($tam=='giaodien_giohang'){
                        include('modules/giaodien/giaodien_giohang.php');
                    }
                    elseif($tam=='donhangnguoidung'){
                        include('modules/giaodien/xem_donhang.php');
                    }
                    elseif($tam=='chitietdonhang'){
                        include('modules/giaodien/.php');
                    }
                    elseif($tam=='doimatkhau'){
                        include('modules/giaodien/giaodien_doimatkhau.php');
                    }
                    elseif($tam=='lienhe'){
                        include('modules/giaodien/lienhe.php');
                    }
                 
                    else{
                        include('modules/giaodien/giaodien_tatcasp.php');
                    }

                    
                ?>
            </div>
        </div>
        <div class="clear"></div>