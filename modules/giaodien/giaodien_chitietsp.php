<?php
include("admincp/modules/config.php"); // Kết nối CSDL

// Lấy id sản phẩm từ URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Ép kiểu để tránh lỗi SQL Injection
    $sql = "SELECT * FROM san_pham WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);
    
    // Kiểm tra xem biến $id có tồn tại không
    if (isset($id)) {
        // Truy vấn tổng số lượng tồn kho từ bảng ton_kho cho sản phẩm cụ thể
        $sql_tonkho = "SELECT SUM(so_luong) AS so_luong_con FROM ton_kho WHERE san_pham_id = $id";
        $result_tonkho = mysqli_query($conn, $sql_tonkho);

        if ($result_tonkho) {
            $tonkho = mysqli_fetch_assoc($result_tonkho);
            $so_luong_con = $tonkho['so_luong_con'] ?? 0; 
        } else {
            $so_luong_con = 0; // Nếu truy vấn thất bại, gán mặc định là 0
        }
    } else {
        header("Location: index.php");
        exit;
    }
}

// Lấy sản phẩm liên quan
$related_sql = "SELECT * FROM san_pham WHERE loai_id = {$product['loai_id']} AND id != $id LIMIT 4";
$related_query = mysqli_query($conn, $related_sql);
?>
<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
}

.header {
    background-color: var(--primary-color);
    color: white;
    padding: 15px 0;
    text-align: center;
}

.header h1 {
    font-weight: bold;
    margin-bottom: 5px;
}

.header p {
    margin-bottom: 0;
    font-style: italic;
}

.navbar-custom {
    background-color: var(--dark-color);
}

.navbar-custom .navbar-brand,
.navbar-custom .nav-link {
    color: white;
}

.product-detail-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-top: 30px;
}

.product-gallery {
    position: relative;
}

.main-image-container {
    position: relative;
    margin-bottom: 1rem;
    border-radius: 8px;
    overflow: hidden;
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: contain;
    background: var(--light-bg);
    transition: transform 0.3s;
    cursor: zoom-in;
}

.main-image:hover {
    transform: scale(1.05);
}

.thumbnail-container {
    position: relative;
    padding: 0 25px;
}

.thumbnail-slider {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 5px 0;
}

.thumbnail-slider::-webkit-scrollbar {
    display: none;
}

.thumbnail {
    flex: 0 0 80px;
    height: 80px;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
    object-fit: cover;
}

.thumbnail:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.thumbnail.active {
    border-color: var(--primary-color);
}

.slider-button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 25px;
    height: 25px;
    border: none;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    z-index: 1;
}

.slider-button:hover {
    background: var(--secondary-color);
}

.slider-button.prev {
    left: 0;
}

.slider-button.next {
    right: 0;
}

.zoom-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 1000;
    cursor: zoom-out;
}

.zoom-image {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90vh;
    object-fit: contain;
}

.zoom-close {
    position: absolute;
    top: 20px;
    right: 20px;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    z-index: 1001;
}

@media (max-width: 768px) {
    .main-image {
        height: 300px;
    }
    
    .thumbnail {
        flex: 0 0 60px;
        height: 60px;
    }
}

.product-price {
    color: #e74c3c;
    font-weight: bold;
    font-size: 1.8rem;
    margin: 15px 0;
}

.product-rating {
    color: #f39c12;
    margin-bottom: 15px;
}

.product-description {
    margin-bottom: 20px;
    line-height: 1.6;
}

.product-meta {
    margin-bottom: 20px;
}

.product-meta span {
    display: block;
    margin-bottom: 5px;
}

.quantity-control {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.quantity-input {
    width: 60px;
    text-align: center;
    margin: 0 10px;
}

.btn-add-to-cart {
    background-color: var(--secondary-color);
    color: white;
    border: none;
    padding: 10px 20px;
    font-weight: bold;
}

.btn-add-to-cart:hover {
    background-color: #2980b9;
}

.product-tabs {
    margin-top: 30px;
}

.nav-tabs .nav-link.active {
    color: var(--secondary-color);
    font-weight: bold;
}

.tab-content {
    padding: 20px;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 5px 5px;
}

.related-products {
    margin-top: 50px;
}

.related-product-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
    margin-bottom: 20px;
    height: 100%;
}

.related-product-card:hover {
    transform: translateY(-5px);
}

.related-product-img {
    height: 200px;
    object-fit: contain;
    width: 100%;
    padding: 10px;
}

footer {
    background-color: var(--dark-color);
    color: white;
    padding: 30px 0;
    margin-top: 50px;
}

/* CSS cho phần đánh giá */
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating-stars input {
    display: none;
}

.rating-stars label {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    padding: 0 3px;
}

.rating-stars input:checked~label,
.rating-stars input:hover~label {
    color: #ffc107;
}

.rating-stars label:hover,
.rating-stars label:hover~label {
    color: #ffc107;
}

.review-item {
    background-color: #f8f9fa;
}
</style>
<!-- Product Detail Content -->
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $product['ten_san_pham']; ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="product-detail-container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div class="main-image-container">
                        <img src="admincp/modules/quanlychitietsp/uploads/<?php echo htmlspecialchars($product['hinh_anh']); ?>" 
                             alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" 
                             class="main-image" 
                             id="mainImage"
                             onclick="openZoom(this.src)">
                    </div>
                    
                    <div class="thumbnail-container">
                        <button class="slider-button prev" onclick="slideImages('prev')">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        
                        <div class="thumbnail-slider" id="thumbnailSlider">
                            <!-- Hình ảnh chính -->
                            <img src="admincp/modules/quanlychitietsp/uploads/<?php echo htmlspecialchars($product['hinh_anh']); ?>" 
                                 alt="Thumbnail chính" 
                                 class="thumbnail active"
                                 onclick="changeImage(this.src, this)">
                        </div>
                        
                        <button class="slider-button next" onclick="slideImages('next')">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <h2><?php echo $product['ten_san_pham']; ?></h2>
                <div class="product-rating">
                    <?php
                    $id_sp = (int)$_GET['id'];
                    $sql_rating = "SELECT IFNULL(AVG(rating), 0) AS avg_rating, COUNT(*) AS total_reviews 
                    FROM reviews WHERE id_sp = $id_sp";
                    $result_rating = mysqli_query($conn, $sql_rating);
                    $rating_info = mysqli_fetch_assoc($result_rating);

                    $avg_rating = round($rating_info['avg_rating'], 1);
                    $total_reviews = $rating_info['total_reviews'];

                    // Hiển thị sao đánh giá
                    for ($i = 1; $i <= 5; $i++) {
                    if ($i <= floor($avg_rating)) {
                    echo '<i class="fas fa-star"></i>'; 
                    } elseif ($i - 0.5 == $avg_rating) {
                    echo '<i class="fas fa-star-half-alt"></i>';
                    } else {
                    echo '<i class="far fa-star"></i>'; 
                    }
}
                        ?>
                    <span class="ms-2">(<?php echo $total_reviews; ?> đánh giá)</span>
                </div>
                <div class="product-price"><?php echo number_format($product['gia_ban'], 0, ',', '.'); ?> VNĐ</div>
                <div class="product-description">
                    <p><?php echo $product['mo_ta']; ?></p>
                </div>

                <div class="product-meta">
                    <span><strong>Mã sản phẩm:</strong> <?php echo $product['id']; ?></span>
                    <span><strong>Thương hiệu:</strong> <?php echo $product['hieusp']; ?></span>
                    <span><strong>Số lượng còn:</strong> <?php echo $so_luong_con ?></span>
                </div>

                <!-- Form thêm vào giỏ hàng -->
                <form onsubmit="event.preventDefault(); addToCart();">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <div class="quantity-control">
                        <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity()">-</button>
                        <input type="number" name="quantity" class="form-control quantity-input" value="1" min="1"
                            id="quantity">
                        <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity()">+</button>
                    </div>

                    <button type="submit" class="btn btn-add-to-cart btn-lg">
                        <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                </form>

                <div class="mt-4">
                    <button class="btn btn-outline-primary me-2">
                        <i class="fas fa-heart me-1"></i>Yêu thích
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-share-alt me-1"></i>Chia sẻ
                    </button>
                </div>
            </div>
        </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Product Tabs -->
        <div class="product-tabs">
            <ul class="nav nav-tabs" id="productTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab"
                        data-bs-target="#description" type="button">Mô tả sản phẩm</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs"
                        type="button">Thông số kỹ thuật</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews"
                        type="button">Đánh giá</button>
                </li>
            </ul>
            <div class="tab-content" id="productTabContent">
                <!-- Mô tả sản phẩm -->
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <h4>Mô tả chi tiết</h4>
                    <p><?php echo $product['thongsosp']; ?></p>
                </div>

                <!-- Thông số kỹ thuật -->
                <div class="tab-pane fade" id="specs" role="tabpanel">
                    <h4>Thông số kỹ thuật</h4>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th scope="row">Thương hiệu</th>
                                <td><?php echo $product['hieusp']; ?></td>
                            </tr>

                            <?php
                                // Hiển thị các thông số kỹ thuật từ trường thongsokt (nếu có)
                                if (!empty($product['thongsosp'])) {
                                    $specs = json_decode($product['thongsosp'], true);
                                    if (is_array($specs)) {
                                        foreach ($specs as $key => $value) {
                                            echo '<tr>';
                                            echo '<th scope="row">' . htmlspecialchars($key) . '</th>';
                                            echo '<td>' . htmlspecialchars($value) . '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                }
                                ?>
                            <tr>
                                <th scope="row">Bảo hành</th>
                                <td>12 tháng</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Đánh giá sản phẩm -->
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <h4>Đánh giá sản phẩm</h4>

                    <!-- Hiển thị thông báo lỗi/thành công -->
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Form đánh giá -->
                        <div class="col-md-6">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <form id="reviewForm" method="post" action="modules/giaodien/xu_ly_danhgia.php">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

                                <div class="mb-3">
                                    <label class="form-label">Đánh giá của bạn:</label>
                                    <div class="rating-stars">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating"
                                            value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">Nhận xét:</label>
                                    <textarea name="comment" class="form-control" rows="3" required></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Bạn cần <a href="login.php">đăng nhập</a> để đánh giá sản phẩm này.
                            </div>
                            <?php endif; ?>
                        </div>
                                            <?php
                    include("admincp/modules/config.php"); // Kết nối CSDL

                    $id_sp = isset($_GET['id']) ? (int)$_GET['id'] : 0;

                    // Lấy danh sách đánh giá từ CSDL
                    $sql = "SELECT u.ten_user, r.rating, r.comment, r.created_at 
                            FROM reviews r
                            JOIN user u ON r.id_user = u.id_user
                            WHERE r.id_sp = $id_sp
                            ORDER BY r.created_at DESC";
                    $result = mysqli_query($conn, $sql);
                    ?>

                    <!-- Hiển thị danh sách đánh giá -->
                    <h5>Đánh giá từ khách hàng</h5>
                    <div id="reviewList">
                        <?php if (mysqli_num_rows($result) > 0) { ?>
                            <?php while ($review = mysqli_fetch_assoc($result)) { ?>
                                <div class="review-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($review['ten_user']); ?></strong>
                                        <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="rating mb-2">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                                        }
                                        ?>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
                        <?php } ?>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

   <!-- Related Products Section -->
<div class="related-products container mt-5">
    <h3>Sản phẩm liên quan</h3>
    <div class="row">
        <?php
               
            $loai_id = $product['loai_id'];
            $id_sp = $product['id'];
            $sql_related = "SELECT * FROM san_pham WHERE loai_id = $loai_id AND id != $id_sp LIMIT 4";
            $result = mysqli_query($conn, $sql_related);
            if (mysqli_num_rows($result) > 0) {
                while ($related = mysqli_fetch_assoc($result)) {
                    echo '<div class="col-md-3">';
                    echo '<div class="related-product-card">';
                    echo '<img src="./admincp/modules/quanlychitietsp/uploads/' . $related['hinh_anh'] . '" class="related-product-img" alt="' . htmlspecialchars($related['ten_san_pham']) . '">';
                    echo '<div class="p-3">';
                    echo '<h5>' . $related['ten_san_pham'] . '</h5>';
                    echo '<p class="text-danger">' . number_format($related['gia_ban'], 0, ',', '.') . ' VNĐ</p>';
                    echo '<a href="index.php?xem=chitietsanpham&id=' . $related['id'] . '" class="btn btn-sm btn-secondary">Xem chi tiết</a>';
                    echo '</div></div></div>';
                }
            } else {
                echo '<p class="text-muted">Không có sản phẩm liên quan.</p>';
            }

        ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>


<script>
$(document).ready(function() {
    // Khởi tạo tabs
    var tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabElms.forEach(function(tabEl) {
        new bootstrap.Tab(tabEl);
    });
});

// Thêm hình ảnh phụ vào slider
function addProductImages() {
    const slider = document.getElementById('thumbnailSlider');
    const mainImage = '<?php echo $product['hinh_anh']; ?>'; // Lấy tên file hình chính
    
    // Tách phần tên và phần mở rộng
    const lastDotIndex = mainImage.lastIndexOf('.');
    const baseName = mainImage.substring(0, lastDotIndex); // img_67f8e0cf492db2.59981972
    const extension = mainImage.substring(lastDotIndex); // .jpg
    
    console.log('Tên file gốc:', baseName);
    console.log('Phần mở rộng:', extension);
    
    // Thêm 4 hình ảnh phụ
    for(let i = 1; i <= 6; i++) {
        const imagePath = `admincp/modules/quanlychitietsp/uploads/${baseName}_${i}${extension}`;
        console.log('Đang tìm hình:', imagePath);
        
        // Kiểm tra xem hình ảnh có tồn tại không
        const img = new Image();
        img.onload = function() {
            console.log('Đã tìm thấy hình:', imagePath);
            const thumbnail = document.createElement('img');
            thumbnail.src = imagePath;
            thumbnail.alt = 'Thumbnail phụ';
            thumbnail.className = 'thumbnail';
            thumbnail.onclick = function() {
                changeImage(this.src, this);
            };
            slider.appendChild(thumbnail);
        };
        img.onerror = function() {
            console.log('Không tìm thấy hình:', imagePath);
        };
        img.src = imagePath;
    }
}

// Hàm đổi hình ảnh chính
function changeImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    // Cập nhật trạng thái active cho thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

// Hàm cuộn slider và chuyển ảnh
function slideImages(direction) {
    const slider = document.getElementById('thumbnailSlider');
    const thumbnails = slider.getElementsByClassName('thumbnail');
    const scrollAmount = 100;
    
    // Tìm thumbnail đang active
    let activeIndex = -1;
    for(let i = 0; i < thumbnails.length; i++) {
        if(thumbnails[i].classList.contains('active')) {
            activeIndex = i;
            break;
        }
    }
    
    // Xác định thumbnail tiếp theo hoặc trước đó
    if(direction === 'next' && activeIndex < thumbnails.length - 1) {
        // Chuyển sang ảnh tiếp theo
        changeImage(thumbnails[activeIndex + 1].src, thumbnails[activeIndex + 1]);
        // Cuộn slider nếu cần
        if((activeIndex + 1) * 100 > slider.scrollLeft + slider.offsetWidth) {
            slider.scrollLeft += scrollAmount;
        }
    } else if(direction === 'prev' && activeIndex > 0) {
        // Chuyển sang ảnh trước đó
        changeImage(thumbnails[activeIndex - 1].src, thumbnails[activeIndex - 1]);
        // Cuộn slider nếu cần
        if((activeIndex - 1) * 100 < slider.scrollLeft) {
            slider.scrollLeft -= scrollAmount;
        }
    }
}

// Hàm zoom ảnh
function openZoom(src) {
    const overlay = document.getElementById('zoomOverlay');
    const zoomImage = document.getElementById('zoomImage');
    overlay.style.display = 'block';
    zoomImage.src = src;
    document.body.style.overflow = 'hidden';
}

// Hàm đóng zoom
function closeZoom() {
    document.getElementById('zoomOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

// Đóng zoom khi nhấn ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeZoom();
    }
});

// Thêm hình ảnh phụ khi trang tải xong
document.addEventListener('DOMContentLoaded', function() {
    addProductImages();
});

// Hàm giảm số lượng sản phẩm
function decreaseQuantity() {
    var quantityInput = document.getElementById('quantity');
    var currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

// Hàm tăng số lượng sản phẩm
function increaseQuantity() {
    var quantityInput = document.getElementById('quantity');
    var currentValue = parseInt(quantityInput.value);
    var maxStock = <?php echo $so_luong_con; ?>;
    if (currentValue < maxStock) {
        quantityInput.value = currentValue + 1;
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: 'Số lượng không được vượt quá số lượng tồn kho'
        });
    }
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;
    const productId = <?php echo $id; ?>;
    
    fetch(`./modules/giaodien/xuly_giohang.php?action=add&product_id=${productId}&quantity=${quantity}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Cập nhật số lượng sản phẩm trong giỏ hàng
                    updateCartCount();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi thêm vào giỏ hàng'
            });
        });
}

function updateCartCount() {
    fetch('./modules/giaodien/xuly_giohang.php?action=get_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count;
                }
            }
        });
}

// Hàm xóa sản phẩm
function deleteProduct(cartDetailId) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa sản phẩm này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`./modules/giaodien/xuly_giohang.php?action=delete&id=${cartDetailId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Reload trang để cập nhật giỏ hàng
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi xóa sản phẩm'
                    });
                });
        }
    });
}

// Hàm cập nhật số lượng sản phẩm
function updateQuantity(cartDetailId, newQuantity) {
    fetch(`./modules/giaodien/xuly_giohang.php?action=update&id=${cartDetailId}&quantity=${newQuantity}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật tổng tiền
                document.getElementById('total-price').textContent = data.total;
                // Cập nhật số lượng
                document.getElementById(`quantity-${cartDetailId}`).value = newQuantity;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi cập nhật số lượng'
            });
        });
}
</script>

<!-- Thêm SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>