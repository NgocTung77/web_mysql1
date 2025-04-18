<?php if ($row_chitiet): ?>
    <div class="wrapper_chitiet">
        <div class="hinhanh_sanpham">
            <h3><?php echo $row_chitiet['tensp']; ?></h3>
            <img src="admincp/modules/quanlychitietsp/uploads/<?php echo !empty($row_chitiet['hinhanh']) ? $row_chitiet['hinhanh'] : 'default.jpg'; ?>" alt="<?php echo $row_chitiet['tensp']; ?>" />
            <p style="color: red; font-size:20px;margin-top:20px;"><i class="fa-solid fa-sack-dollar"></i> <strong>Giá:</strong> <?php echo number_format($row_chitiet['gia'], 0, ',', '.') . ' VND'; ?></p>
        </div>
        <div class="chitiet_sanpham">
            <p><strong>Danh mục:</strong> <?php echo $row_chitiet['tenloaisp']; ?></p>
            <p><strong>Nhãn hàng:</strong> <?php echo $row_chitiet['hieusp']; ?></p>
            <!-- Form thêm giỏ hàng -->
            <form method="POST" action="modules/right/themgiohang.php?id=<?php echo $row_chitiet['id_sp']; ?>">
                <p><strong>Số lượng còn lại:</strong> <?php echo $row_chitiet['thutu']; ?></p>
                <input type="submit" name="themgiohang" value="Thêm vào giỏ hàng">
            </form>
            <form action="modules/right/themgiohang.php?id=<?php echo $row_chitiet['id_sp']; ?>" method="POST">
                <input type="submit" name="themgiohang" value="Mua">
            </form> 
        </div>
    </div>
<?php else: ?>
    <p>Sản phẩm không tồn tại.</p>
<?php endif; ?>
