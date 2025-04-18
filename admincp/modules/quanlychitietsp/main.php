<div class="right">
    <?php
        if (isset($_GET['ac']) && $_GET['ac'] == 'sua' && isset($_GET['id'])) {
            include('modules/quanlychitietsp/sua.php');
        } else {
            include('modules/quanlychitietsp/them.php');
        }
    ?>
</div>

<div class="right">
    <?php
        include('modules/hienthi/HienThiSanPham.php');
    ?>
</div>
