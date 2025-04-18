<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id_sp = isset($_GET['id']) ? $_GET['id'] : 0;
$sql = "SELECT * FROM san_pham WHERE id = $id_sp";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
?>
<form action="modules/quanlychitietsp/xuly.php" method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="form-group">
                <label for="ten_san_pham" class="form-label fw-semibold" style="color: #2e3440;">Tên sản phẩm</label>
                <input type="text" class="form-control" id="ten_san_pham" name="ten_san_pham" 
                       value="<?php echo htmlspecialchars($row['ten_san_pham']); ?>" required 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="hieusp" class="form-label fw-semibold" style="color: #2e3440;">Hiệu sản phẩm</label>
                <input type="text" class="form-control" id="hieusp" name="hieusp" 
                       value="<?php echo htmlspecialchars($row['hieusp']); ?>" required 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="loai_san_pham" class="form-label fw-semibold" style="color: #2e3440;">Loại sản phẩm</label>
                <select class="form-select" id="loai_san_pham" name="loai_san_pham" required 
                        style="border-radius: 8px; border: 1px solid #d8dee9;">
                    <?php
                    $sql_loaisp = "SELECT * FROM loai_san_pham";
                    $result_loaisp = mysqli_query($conn, $sql_loaisp);
                    while ($dong = mysqli_fetch_array($result_loaisp)) {
                        $selected = $row['loai_id'] == $dong['id'] ? 'selected' : '';
                        echo "<option value='{$dong['id']}' $selected>{$dong['ten_loai']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="hinh_anh" class="form-label fw-semibold" style="color: #2e3440;">Hình ảnh</label>
                <input type="file" class="form-control" id="hinh_anh" name="hinh_anh" 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
                <?php if (!empty($row['hinh_anh'])): ?>
                    <div class="mt-2">
                        <img src="modules/quanlychitietsp/uploads/<?php echo $row['hinh_anh']; ?>" 
                             class="img-thumbnail" style="max-width: 120px; border-radius: 6px;">
                        <p class="text-muted mt-1" style="font-size: 0.9rem;">Hình ảnh hiện tại</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label for="mo_ta" class="form-label fw-semibold" style="color: #2e3440;">Mô tả</label>
                <textarea id="mo_ta" name="mo_ta" class="form-control" rows="4" 
                          style="border-radius: 8px; border: 1px solid #d8dee9;"><?php echo htmlspecialchars($row['mo_ta']); ?></textarea>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label for="thongsosp" class="form-label fw-semibold" style="color: #2e3440;">Thông số kỹ thuật</label>
                <textarea id="thongsosp" name="thongsosp" class="form-control" rows="4" 
                          style="border-radius: 8px; border: 1px solid #d8dee9;"><?php echo htmlspecialchars($row['thongsosp']); ?></textarea>
            </div>
        </div>
    </div>
    <input type="hidden" name="id_sp" value="<?php echo $row['id']; ?>">
    <input type="hidden" name="action" value="Cập nhật sản phẩm">
    <div class="mt-4 text-end">
        <button type="submit" class="btn" 
                style="background: #81a1c1; color: white; padding: 0.6rem 2rem; border-radius: 30px; font-weight: 500;">
            <i class="fas fa-save me-2"></i> Cập nhật sản phẩm
        </button>
    </div>
</form>
<script src="https://cdn.tiny.cloud/1/j9f1oxvskrxx2ese8g4h69a7ke3tlfdh6ql73ugjo5yd7di7/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#mo_ta, #thongsosp',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 250,
        menubar: false,
        statusbar: false
    });
</script>
<?php } else { ?>
    <div class="alert alert-danger" style="border-radius: 8px;">Sản phẩm không tồn tại!</div>
<?php } ?>

<?php
mysqli_close($conn);
?>