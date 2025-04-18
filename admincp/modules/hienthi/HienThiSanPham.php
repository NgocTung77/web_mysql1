<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);

$start_index = ($current_page - 1) * $items_per_page;

$total_items_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM san_pham");
$total_items_row = mysqli_fetch_assoc($total_items_result);
$total_items = $total_items_row['total'];
$total_pages = ceil($total_items / $items_per_page);

$sql = "SELECT sp.id, sp.ma_san_pham, sp.gia_ban, sp.ten_san_pham, sp.mo_ta, sp.thongsosp, sp.hinh_anh, loai.ten_loai, sp.hieusp, 
               SUM(tk.so_luong) AS so_luong
        FROM san_pham sp
        JOIN loai_san_pham loai ON sp.loai_id = loai.id
        LEFT JOIN ton_kho tk ON sp.id = tk.san_pham_id
        GROUP BY sp.id
        ORDER BY sp.id DESC
        LIMIT $start_index, $items_per_page";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #5e81ac;
        --secondary-color: #d8dee9;
        --success-color: #88c0d0;
        --danger-color: #bf616a;
        --info-color: #81a1c1;
        --light-color: #eceff4;
        --dark-color: #2e3440;
        --transition-speed: 0.3s;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(120deg, var(--light-color) 0%, #e5e9f0 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1400px;
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(46, 52, 64, 0.1);
        overflow: hidden;
    }

    .card-header {
        background: var(--primary-color);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid rgba(255, 255, 255, 0.3);
    }

    .card-header h4 {
        margin: 0;
        font-weight: 600;
        font-size: 1.6rem;
        letter-spacing: 0.5px;
    }

    .search-container {
        position: relative;
        width: 280px;
    }

    .search-container input {
        border-radius: 30px;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border: none;
        background: var(--secondary-color);
        color: var(--dark-color);
        font-size: 0.95rem;
        transition: all var(--transition-speed);
    }

    .search-container input:focus {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        outline: none;
    }

    .search-container i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--dark-color);
        font-size: 1rem;
    }

    .btn-add {
        background: var(--success-color);
        border: none;
        border-radius: 30px;
        padding: 0.6rem 1.8rem;
        font-weight: 500;
        color: white;
        transition: all var(--transition-speed);
    }

    .btn-add:hover {
        background: #72a9b8;
        transform: scale(1.05);
        color: white;
    }

    .product-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .product-table th {
        background: var(--dark-color);
        color: white;
        padding: 1rem;
        font-weight: 500;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .product-table td {
        padding: 1rem;
        background: white;
        border-bottom: 1px solid var(--secondary-color);
        transition: all var(--transition-speed);
    }

    .product-table tr:hover td {
        background: var(--light-color);
    }

    .product-img {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--secondary-color);
        transition: transform var(--transition-speed);
    }

    .product-img:hover {
        transform: scale(1.15);
    }

    .stock-status {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        display: inline-block;
    }

    .in-stock {
        background: var(--success-color);
        color: white;
    }

    .out-of-stock {
        background: var(--danger-color);
        color: white;
    }

    .action-btns .btn {
        padding: 0.5rem;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
        border: none;
        transition: all var(--transition-speed);
    }

    .action-btns .btn i {
        font-size: 1rem;
    }

    .btn-buy {
        background: var(--success-color);
        color: white;
    }

    .btn-info {
        background: var(--info-color);
        color: white;
    }

    .btn-edit {
        background: var(--info-color);
        color: white;
    }

    .btn-delete {
        background: var(--danger-color);
        color: white;
    }

    .action-btns .btn:hover {
        transform: scale(1.1);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 2rem;
    }

    .pagination a,
    .pagination span {
        padding: 0.6rem 1.2rem;
        border-radius: 30px;
        text-decoration: none;
        color: var(--primary-color);
        background: white;
        font-weight: 500;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: all var(--transition-speed);
    }

    .pagination a:hover {
        background: var(--primary-color);
        color: white;
    }

    .pagination span {
        background: var(--primary-color);
        color: white;
    }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-boxes me-2"></i> Quản lý sản phẩm</h4>
                <div class="d-flex align-items-center gap-3">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" placeholder="Tìm kiếm sản phẩm...">
                    </div>
                    <a href="#" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i>Thêm sản phẩm
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Hình ảnh</th>
                                <th>Giá bán</th>
                                <th>Loại SP</th>
                                <th>Hiệu SP</th>
                                <th>Số lượng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['ma_san_pham']; ?></td>
                                <td><?php echo htmlspecialchars($row['ten_san_pham']); ?></td>
                                <td>
                                    <?php 
                                            $duong_dan_hinhanh = "modules/quanlychitietsp/uploads/" . $row['hinh_anh'];
                                            if (!empty($row['hinh_anh']) && file_exists($duong_dan_hinhanh)): ?>
                                    <img src="<?php echo htmlspecialchars($duong_dan_hinhanh); ?>" class="product-img"
                                        alt="<?php echo htmlspecialchars($row['ten_san_pham']); ?>">
                                    <?php else: ?>
                                    <img src="modules/quanlychitietsp/uploads/no-image.png" class="product-img"
                                        alt="No image">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($row['gia_ban'], 0, ',', '.'); ?> VND</td>
                                <td><?php echo htmlspecialchars($row['ten_loai']); ?></td>
                                <td><?php echo htmlspecialchars($row['hieusp']); ?></td>
                                <td>
                                    <span
                                        class="stock-status <?php echo ($row['so_luong'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo $row['so_luong']; ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <?php if ($row['so_luong'] > 0): ?>
                                    <a href="mua_sp.php?id=<?php echo $row['id']; ?>" class="btn btn-buy">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="#" class="btn btn-info" data-bs-toggle="modal"
                                        data-bs-target="#detailProductModal"
                                        onclick="loadProductDetails(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-edit" data-bs-toggle="modal"
                                        data-bs-target="#editProductModal"
                                        onclick="loadEditForm(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-delete" data-bs-toggle="modal"
                                        data-bs-target="#deleteProductModal"
                                        onclick="setDeleteId(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="pagination">
                    <?php if ($current_page > 1): ?>
                    <a href="index.php?quanly=quanlychitietsp&ac=them&page=<?php echo $current_page - 1; ?>"><i
                            class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                    <?php if ($i == $current_page): ?>
                    <span><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="index.php?quanly=quanlychitietsp&ac=them&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($current_page < $total_pages): ?>
                    <a href="index.php?quanly=quanlychitietsp&ac=them&page=<?php echo $current_page + 1; ?>"><i
                            class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </nav>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x" style="color: var(--secondary-color);"></i>
                    <p class="mt-3" style="color: var(--dark-color); font-size: 1.2rem;">Chưa có sản phẩm nào trong kho
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Thêm sản phẩm -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header"
                    style="background: var(--success-color); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title" id="addProductModalLabel"><i class="fas fa-plus me-2"></i> Thêm sản phẩm mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php include 'modules/quanlychitietsp/them.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sửa sản phẩm -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header"
                    style="background: var(--info-color); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title" id="editProductModalLabel"><i class="fas fa-edit me-2"></i> Sửa sản phẩm
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editProductModalBody">
                    <!-- Nội dung sẽ được load động qua JavaScript -->

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Xóa sản phẩm -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header"
                    style="background: var(--danger-color); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title" id="deleteProductModalLabel"><i class="fas fa-trash me-2"></i> Xóa sản phẩm
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p style="color: var(--dark-color); font-size: 1.1rem;">Bạn có chắc chắn muốn xóa sản phẩm này
                        không? Hành động này không thể hoàn tác.</p>
                    <form id="deleteForm" action="modules/quanlychitietsp/xoa.php" method="GET">
                        <input type="hidden" name="id" id="deleteProductId">
                    </form>
                </div>
                <div class="modal-footer" style="border-top: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="border-radius: 30px; padding: 0.6rem 1.5rem;">Hủy</button>
                    <button type="submit" form="deleteForm" class="btn"
                        style="background: var(--danger-color); color: white; border-radius: 30px; padding: 0.6rem 1.5rem;">
                        <i class="fas fa-trash me-2"></i> Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Xem chi tiết sản phẩm -->
<div class="modal fade" id="detailProductModal" tabindex="-1" aria-labelledby="detailProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header" style="background: var(--info-color); color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title" id="detailProductModalLabel"><i class="fas fa-info-circle me-2"></i> Chi tiết sản phẩm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailProductModalBody">
                <!-- Nội dung sẽ được load động qua JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                        style="border-radius: 30px; padding: 0.6rem 1.5rem;">Đóng</button>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadProductDetails(id) {
    fetch(`modules/hienthi/ChiTietSanPham.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('detailProductModalBody').innerHTML = data;
        });
}
    function loadEditForm(id) {
        fetch(`modules/quanlychitietsp/sua.php?id=${id}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('editProductModalBody').innerHTML = data;
            });
    }

    function setDeleteId(id) {
        document.getElementById('deleteProductId').value = id;
    }
    </script>
</body>

</html>