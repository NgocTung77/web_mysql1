<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<form action="modules/quanlychitietsp/xuly.php" method="POST" enctype="multipart/form-data">
    <div class="row g-4">
    
        <!-- Tên sản phẩm -->
        <div class="col-md-6">
            <div class="form-group">
                <label for="ten_san_pham" class="form-label fw-semibold" style="color: #2e3440;">Tên sản phẩm</label>
                <input type="text" class="form-control" id="ten_san_pham" name="ten_san_pham" required 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
            </div>
        </div>

        <!-- Hiệu sản phẩm -->
        <div class="col-md-6">
            <div class="form-group">
                <label for="hieusp" class="form-label fw-semibold" style="color: #2e3440;">Hiệu sản phẩm</label>
                <input type="text" class="form-control" id="hieusp" name="hieusp" required 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
            </div>
        </div>

        <!-- Loại sản phẩm -->
        <div class="col-md-6">
            <div class="form-group">
                <label for="loai_san_pham" class="form-label fw-semibold" style="color: #2e3440;">Loại sản phẩm</label>
                <select class="form-select" id="loai_san_pham" name="loai_san_pham" required 
                        style="border-radius: 8px; border: 1px solid #d8dee9;">
                    <?php
                    $sql = "SELECT * FROM loai_san_pham";
                    $result = mysqli_query($conn, $sql);
                    while ($dong = mysqli_fetch_array($result)) {
                        echo "<option value='{$dong['id']}'>{$dong['ten_loai']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
       
        <!-- Hình ảnh -->
        <div class="col-md-6">
            <div class="form-group">
                <label for="hinh_anh" class="form-label fw-semibold" style="color: #2e3440;">Hình ảnh</label>
                <input type="file" class="form-control" id="hinh_anh" name="hinh_anh" required 
                       style="border-radius: 8px; border: 1px solid #d8dee9;">
            </div>
        </div>

        <!-- Mô tả -->
        <div class="col-12">
            <div class="form-group">
                <label for="mo_ta" class="form-label fw-semibold" style="color: #2e3440;">Mô tả</label>
                <textarea id="mo_ta" name="mo_ta" class="form-control" rows="4"></textarea>
            </div>
        </div>

        <!-- Thông số kỹ thuật -->
        <div class="col-12">
            <div class="form-group">
                <label for="thongsosp" class="form-label fw-semibold" style="color: #2e3440;">Thông số kỹ thuật</label>
                <textarea id="thongsosp" name="thongsosp" class="form-control" rows="4"></textarea>
            </div>
        </div>
    </div>
    <input type="hidden" name="action" value="them">
    <div class="mt-4 text-end">
        <button type="submit" class="btn" 
                style="background: #88c0d0; color: white; padding: 0.6rem 2rem; border-radius: 30px; font-weight: 500;">
            <i class="fas fa-plus me-2"></i> Thêm sản phẩm
        </button>
    </div>
</form>

<?php
mysqli_close($conn);
?>