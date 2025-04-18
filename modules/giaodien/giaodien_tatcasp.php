<?php

include('admincp/modules/config.php');

// Nhận tham số tìm kiếm từ người dùng
$searchName = isset($_GET['ten_san_pham']) ? $_GET['ten_san_pham'] : '';
$searchPrice = isset($_GET['gia_ban']) ? $_GET['gia_ban'] : '';
$searchBrand = isset($_GET['hieusp']) ? $_GET['hieusp'] : '';
$searchCategory = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$whereClause = "1=1";

// Thêm điều kiện tìm kiếm nếu có
if ($searchName) $whereClause .= " AND ten_san_pham LIKE '%" . mysqli_real_escape_string($conn, $searchName) . "%'";
if ($searchPrice) $whereClause .= " AND gia_ban <= " . (int)$searchPrice;
if ($searchBrand) $whereClause .= " AND hieusp LIKE '%" . mysqli_real_escape_string($conn, $searchBrand) . "%'";
if ($searchCategory > 0) $whereClause .= " AND loai_id = " . $searchCategory;

$items_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Xây dựng câu lệnh SQL đếm sản phẩm
$sql_count = "SELECT COUNT(*) as total_products FROM san_pham WHERE $whereClause";
$result_count = mysqli_query($conn, $sql_count) or die("Lỗi truy vấn đếm sản phẩm: " . mysqli_error($conn));
$row_count = mysqli_fetch_assoc($result_count);
$total_products = $row_count['total_products'];

// Tính tổng số trang
$total_pages = ceil($total_products / $items_per_page);

// Lấy danh sách loại sản phẩm
$sql_categories = "SELECT id, ten_loai FROM loai_san_pham ORDER BY ten_loai";
$categories_result = mysqli_query($conn, $sql_categories);

// Lấy danh sách sản phẩm cho danh sách chính
$sql_all = "SELECT * FROM san_pham WHERE $whereClause ORDER BY id DESC LIMIT $items_per_page OFFSET $offset";
$query_all = mysqli_query($conn, $sql_all) or die("Lỗi truy vấn sản phẩm: " . mysqli_error($conn));

// Lấy 3 sản phẩm mới nhất cho carousel
$sql_carousel = "SELECT * FROM san_pham ORDER BY id DESC LIMIT 5";
$query_carousel = mysqli_query($conn, $sql_carousel);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e3a8a; /* Xanh navy sang trọng */
            --secondary-color: #2563eb; /* Xanh dương cho hover */
            --accent-color: #d97706; /* Vàng ánh kim */
            --text-color: #111827; /* Xám đậm */
            --light-bg: #f1f5f9; /* Xám nhạt */
            --card-bg: #ffffff; /* Trắng */
            --hover-bg: #e2e8f0; /* Xám nhạt hover */
        }

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        /* Carousel styles */
        .carousel {
            position: relative;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem; /* Thêm khoảng cách bên dưới */
        }

        .carousel-slides {
            display: flex;
            width: 100%;
            transition: transform 0.5s ease-in-out;
        }

        .carousel-slide {
            min-width: 100%;
            position: relative;
            background-color: var(--light-bg); /* Nền cho hình ảnh */
        }

        .carousel-slide img {
            width: 100%;
            height: 500px;
            object-fit: contain; /* Giữ tỷ lệ, không cắt */
            display: block;
            margin: 0 auto;
        }

        .carousel-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Tăng độ tương phản lớp phủ */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .carousel-title {
            font-size: 3rem; /* Tăng kích thước chữ */
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .carousel-price {
            font-size: 2.2rem; /* Tăng kích thước giá */
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 1rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .carousel-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .carousel-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            color: white;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .carousel-prev {
            left: 1rem;
        }

        .carousel-next {
            right: 1rem;
        }

        .carousel-dots {
            position: absolute;
            bottom: 1rem;
            width: 100%;
            text-align: center;
        }

        .carousel-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: var(--accent-color);
        }

        /* Main container styles */
        .main-container {
            display: flex;
            width: 100%;
            gap: 1rem;
            padding: 1rem 2rem;
        }

        .sidebar {
            width: 220px;
            background-color: var(--card-bg);
            border-radius: 0 8px 8px 0;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            position: sticky;
            top: 1rem;
            height: fit-content;
            margin: 0;
        }

        .sidebar h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 0.5rem;
        }

        .sidebar a {
            text-decoration: none;
            color: var(--text-color);
            font-size: 1rem;
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        .sidebar a:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
        }

        .sidebar a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .content-container {
            flex: 1;
            padding: 0 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-container {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .input-group input,
        .input-group select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--light-bg);
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 138, 0.3);
            background-color: var(--card-bg);
            outline: none;
        }

        .search-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .search-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .reset-button {
            color: var(--primary-color);
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            color: var(--accent-color);
            transform: rotate(180deg);
        }

        .products-header {
            margin-bottom: 1.5rem;
        }

        .products-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .products-header p {
            font-size: 1rem;
            color: #6b7280;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            padding: 1rem;
            background-color: var(--light-bg);
            border-bottom: 1px solid #e2e8f0;
        }

        .product-info {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 3em;
            text-decoration: none;
        }

        .product-price {
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0.5rem 0;
        }

        .product-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .detail-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .detail-button:hover {
            background-color: var(--secondary-color);
        }

        .buy-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
        }

        .buy-button:hover {
            background-color: #b45309;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: var(--hover-bg);
            color: var(--secondary-color);
        }

        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-products h4 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .no-products p {
            color: #6b7280;
        }

        .sidebar-toggle {
            display: none;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
                padding: 1rem;
            }

            .sidebar {
                display: none;
                width: 100%;
                border-radius: 8px;
                position: static;
            }

            .sidebar.active {
                display: block;
            }

            .sidebar-toggle {
                display: block;
            }

            .content-container {
                width: 100%;
                padding: 0 1rem;
            }

            .carousel-slide img {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .search-bar {
                grid-template-columns: 1fr;
            }

            .input-group label {
                font-size: 0.9rem;
            }

            .input-group input, .input-group select {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            .search-button {
                padding: 0.5rem 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .product-image {
                height: 150px;
            }

            .carousel-slide img {
                height: 300px;
            }

            .carousel-title {
                font-size: 2rem; /* Thu nhỏ trên mobile */
            }

            .carousel-price {
                font-size: 1.8rem; /* Thu nhỏ trên mobile */
            }

            .carousel-button {
                padding: 0.5rem 1.5rem;
                font-size: 1rem;
            }

            .carousel-nav {
                font-size: 2rem; /* Thu nhỏ nút điều hướng */
                padding: 0.3rem;
            }

            .carousel-dot {
                width: 10px;
                height: 10px;
            }
        }
    </style>
</head>
<body>
    

    <!-- Carousel -->
    <div class="carousel">
        <div class="carousel-slides">
            <?php while ($slide = mysqli_fetch_assoc($query_carousel)): ?>
                <div class="carousel-slide">
                    <img src="admincp/modules/quanlychitietsp/uploads/<?= htmlspecialchars($slide['hinh_anh']) ?>" 
                         alt="<?= htmlspecialchars($slide['ten_san_pham']) ?>">
                    <div class="carousel-overlay">
                        <h2 class="carousel-title"><?= htmlspecialchars($slide['ten_san_pham']) ?></h2>
                        <div class="carousel-price"><?= $slide['gia_ban'] ?> ₫</div>
                        <a href="index.php?xem=chitietsanpham&id=<?= $slide['id'] ?>" class="carousel-button">Chi tiết</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <button class="carousel-nav carousel-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-nav carousel-next"><i class="fas fa-chevron-right"></i></button>
        <div class="carousel-dots">
            <?php 
            mysqli_data_seek($query_carousel, 0);
            $slide_count = mysqli_num_rows($query_carousel);
            for ($i = 0; $i < $slide_count; $i++): ?>
                <span class="carousel-dot <?= $i == 0 ? 'active' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Main content -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰ Danh mục</button>
    <div class="main-container">
        <div class="sidebar">
            <h3>Danh mục sản phẩm</h3>
            <ul>
                <li>
                    <a href="index.php?xem=tatcasp" class="<?= $searchCategory == 0 ? 'active' : '' ?>">
                        <i class="fas fa-th"></i> Tất cả sản phẩm
                    </a>
                </li>
                <?php 
                mysqli_data_seek($categories_result, 0);
                while ($category = mysqli_fetch_assoc($categories_result)): ?>
                    <li>
                        <a href="index.php?xem=tatcasp&category_id=<?= $category['id'] ?>" 
                           class="<?= $searchCategory == $category['id'] ? 'active' : '' ?>">
                            <i class="fas fa-angle-right"></i> <?= htmlspecialchars($category['ten_loai']) ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="content-container">
            <div class="search-container">
                <form method="GET" action="" class="search-bar">
                    <input type="hidden" name="xem" value="tatcasp">
                    <div class="input-group">
                        <label for="ten_san_pham">Tên sản phẩm</label>
                        <input type="text" name="ten_san_pham" id="ten_san_pham" placeholder="Nhập tên sản phẩm" value="<?php echo htmlspecialchars($searchName); ?>">
                    </div>
                    <div class="input-group">
                        <label for="gia_ban">Giá tối đa</label>
                        <input type="number" name="gia_ban" id="gia_ban" placeholder="Nhập giá tối đa" value="<?php echo htmlspecialchars($searchPrice); ?>">
                    </div>
                    <div class="input-group">
                        <label for="hieusp">Thương hiệu</label>
                        <input type="text" name="hieusp" id="hieusp" placeholder="Nhập thương hiệu" value="<?php echo htmlspecialchars($searchBrand); ?>">
                    </div>
                    <div class="input-group">
                        <label for="category_id">Loại sản phẩm</label>
                        <select name="category_id" id="category_id">
                            <option value="0">Tất cả loại sản phẩm</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?= $category['id'] ?>" <?= $searchCategory == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['ten_loai']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </button>
                        <a href="index.php?xem=tatcasp" class="reset-button" title="Làm mới">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="products-container">
                <div class="products-header">
                    <h2>Sản phẩm <?php echo $searchCategory > 0 ? 'theo danh mục' : 'tất cả'; ?></h2>
                    <p>Tìm thấy <?php echo $total_products; ?> sản phẩm</p>
                </div>
                <?php if (mysqli_num_rows($query_all) > 0): ?>
                    <div class="products-grid">
                        <?php while ($dong_all = mysqli_fetch_array($query_all)): ?>
                            <div class="product-card">
                                <a href="index.php?xem=chitietsanpham&id=<?php echo $dong_all['id']; ?>">
                                    <img src="admincp/modules/quanlychitietsp/uploads/<?php echo htmlspecialchars($dong_all['hinh_anh']); ?>" 
                                         alt="<?php echo htmlspecialchars($dong_all['ten_san_pham']); ?>" 
                                         class="product-image">
                                </a>
                                <div class="product-info">
                                    <a href="index.php?xem=chitietsanpham&id=<?php echo $dong_all['id']; ?>" class="product-name">
                                        <?php echo htmlspecialchars($dong_all['ten_san_pham']); ?>
                                    </a>
                                    <div class="product-price">
                                        <?php echo $dong_all['gia_ban']; ?> ₫
                                    </div>
                                    <div class="product-buttons">
                                        <a href="index.php?xem=chitietsanpham&id=<?php echo $dong_all['id']; ?>" class="detail-button">
                                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                                        </a>
                                        <a href="index.php?xem=giaodien_giohang&id=<?php echo $dong_all['id']; ?>" class="buy-button">
                                            <i class="fas fa-cart-plus me-1"></i>Mua ngay
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="index.php?xem=tatcasp&page=<?= ($page - 1) ?>&ten_san_pham=<?= urlencode($searchName) ?>&gia_ban=<?= urlencode($searchPrice) ?>&hieusp=<?= urlencode($searchBrand) ?>&category_id=<?= $searchCategory ?>">
                                <i class="fas fa-chevron-left me-1"></i>Trang trước
                            </a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="index.php?xem=tatcasp&page=<?= $i ?>&ten_san_pham=<?= urlencode($searchName) ?>&gia_ban=<?= urlencode($searchPrice) ?>&hieusp=<?= urlencode($searchBrand) ?>&category_id=<?= $searchCategory ?>" 
                               class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="index.php?xem=tatcasp&page=<?= ($page + 1) ?>&ten_san_pham=<?= urlencode($searchName) ?>&gia_ban=<?= urlencode($searchPrice) ?>&hieusp=<?= urlencode($searchBrand) ?>&category_id=<?= $searchCategory ?>">
                                Trang sau<i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open fa-3x mb-3" style="color: #6b7280;"></i>
                        <h4>Không tìm thấy sản phẩm phù hợp</h4>
                        <p>Hãy thử điều chỉnh tiêu chí tìm kiếm của bạn</p>
                        <a href="index.php?xem=tatcasp" class="detail-button mt-3">
                            <i class="fas fa-undo me-1"></i>Xem tất cả sản phẩm
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Carousel functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const slideCount = slides.length;

        function showSlide(index) {
            if (index >= slideCount) currentSlide = 0;
            else if (index < 0) currentSlide = slideCount - 1;
            else currentSlide = index;

            document.querySelector('.carousel-slides').style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach(dot => dot.classList.remove('active'));
            dots[currentSlide].classList.add('active');
        }

        document.querySelector('.carousel-prev').addEventListener('click', () => {
            showSlide(currentSlide - 1);
        });

        document.querySelector('.carousel-next').addEventListener('click', () => {
            showSlide(currentSlide + 1);
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });

        // Auto slide every 5 seconds
        setInterval(() => {
            showSlide(currentSlide + 1);
        }, 5000);

        // Sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>