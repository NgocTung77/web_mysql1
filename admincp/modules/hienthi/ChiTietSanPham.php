<?php


$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    // Lấy thông tin cơ bản của sản phẩm
    $product_query = mysqli_query($conn, 
        "SELECT sp.*, loai.ten_loai 
         FROM san_pham sp 
         JOIN loai_san_pham loai ON sp.loai_id = loai.id 
         WHERE sp.id = $product_id");
    $product = mysqli_fetch_assoc($product_query);
    
    // Lấy thông tin tồn kho theo từng kho
    $inventory_query = mysqli_query($conn, 
        "SELECT tk.so_luong, k.ten_kho, k.dia_chi, k.id as kho_id
         FROM ton_kho tk 
         JOIN kho k ON tk.kho_id = k.id 
         WHERE tk.san_pham_id = $product_id 
         ORDER BY tk.so_luong DESC");
    
    // Tính tổng số lượng tồn kho
    $total_quantity = 0;
    $inventory_rows = [];
    while ($row = mysqli_fetch_assoc($inventory_query)) {
        $total_quantity += $row['so_luong'];
        $inventory_rows[] = $row;
    }
    
    // Hiển thị thông tin sản phẩm
    echo '<div class="row">';
    echo '<div class="col-md-5">';
    echo '<div class="text-center mb-4">';
    
    $image_path = "modules/quanlychitietsp/uploads/" . $product['hinh_anh'];
    if (!empty($product['hinh_anh']) && file_exists($image_path)) {
        echo '<img src="' . $image_path . '" class="img-fluid rounded" style="max-height: 300px;" alt="' . htmlspecialchars($product['ten_san_pham']) . '">';
    } else {
        echo '<img src="modules/quanlychitietsp/uploads/no-image.png" class="img-fluid rounded" style="max-height: 300px;" alt="No image">';
    }
    
    echo '</div>';
    echo '</div>';
    echo '<div class="col-md-7">';
    echo '<h3>' . htmlspecialchars($product['ten_san_pham']) . '</h3>';
    echo '<hr>';
    echo '<p><strong>Mã sản phẩm:</strong> ' . htmlspecialchars($product['ma_san_pham']) . '</p>';
    echo '<p><strong>Loại sản phẩm:</strong> ' . htmlspecialchars($product['ten_loai']) . '</p>';
    echo '<p><strong>Giá bán:</strong> ' . number_format($product['gia_ban'], 0, ',', '.') . ' VND</p>';
    echo '<p><strong>Hiệu sản phẩm:</strong> ' . htmlspecialchars($product['hieusp']) . '</p>';
    echo '<p><strong>Tổng số lượng:</strong> <span class="badge bg-' . ($total_quantity > 0 ? 'success' : 'danger') . '">' . $total_quantity . '</span></p>';
    
    // Hiển thị thông số kỹ thuật nếu có
    if (!empty($product['thongsosp'])) {
        echo '<div class="mt-3">';
        echo '<h5>Thông số kỹ thuật</h5>';
        echo '<div class="bg-light p-3 rounded">' . nl2br(htmlspecialchars($product['thongsosp'])) . '</div>';
        echo '</div>';
    }
    
    // Hiển thị mô tả nếu có
    if (!empty($product['mo_ta'])) {
        echo '<div class="mt-3">';
        echo '<h5>Mô tả sản phẩm</h5>';
        echo '<div class="bg-light p-3 rounded">' . nl2br(htmlspecialchars($product['mo_ta'])) . '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Hiển thị thông tin kho hàng
    if (count($inventory_rows) > 0) {
        echo '<div class="mt-4">';
        echo '<h5><i class="fas fa-warehouse me-2"></i>Thông tin kho hàng</h5>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-hover">';
        echo '<thead class="table-dark">';
        echo '<tr>';
        echo '<th>Kho</th>';
        echo '<th>Địa chỉ</th>';
        echo '<th>Số lượng</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($inventory_rows as $inventory) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($inventory['ten_kho']) . '</td>';
            echo '<td>' . htmlspecialchars($inventory['dia_chi']) . '</td>';
            echo '<td><span class="badge bg-' . ($inventory['so_luong'] > 0 ? 'success' : 'danger') . '">' . $inventory['so_luong'] . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning mt-4">Sản phẩm hiện không có trong kho nào</div>';
    }
} else {
    echo '<div class="alert alert-danger">Không tìm thấy sản phẩm</div>';
}
?>