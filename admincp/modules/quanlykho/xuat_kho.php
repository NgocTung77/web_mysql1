<?php
$conn = mysqli_connect("localhost", "root", "", "quan_ly_kho");

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

// Kiểm tra quyền truy cập
$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? '';
$kho_id = $_SESSION['kho_id'] ?? '';

if (!in_array($vai_tro, ['admin', 'quan_ly_vung', 'quan_ly_kho'])) {
    die("Bạn không có quyền truy cập chức năng này");
}

$search_results = [];
$search_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_term = trim($_POST['search_term']);
    $search_term = '%' . mysqli_real_escape_string($conn, $search_term) . '%';
    
    // Sử dụng LEFT JOIN để hiển thị sản phẩm ngay cả khi không có tồn kho
    $query_search = "SELECT sp.id, sp.ten_san_pham, sp.ma_san_pham, 
                    tk.kho_id, k.ten_kho, tk.so_luong
                    FROM san_pham sp
                    LEFT JOIN ton_kho tk ON sp.id = tk.san_pham_id
                    LEFT JOIN kho k ON tk.kho_id = k.id
                    WHERE (LOWER(sp.ten_san_pham) LIKE LOWER(?) OR LOWER(sp.ma_san_pham) LIKE LOWER(?))";
    
    // Áp dụng phân quyền
    $params = [$search_term, $search_term];
    $types = "ss";
    
    if ($vai_tro == 'quan_ly_vung') {
        $query_search .= " AND k.vung_id = ?";
        $params[] = $vung_id;
        $types .= "i";
    } elseif ($vai_tro == 'quan_ly_kho') {
        $query_search .= " AND tk.kho_id = ?";
        $params[] = $kho_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query_search);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $search_results = $stmt->get_result();
    
    // Kiểm tra lý do không tìm thấy kết quả
    if ($search_results->num_rows == 0) {
        $check_product = $conn->prepare("SELECT id FROM san_pham 
                                        WHERE LOWER(ten_san_pham) LIKE LOWER(?) OR LOWER(ma_san_pham) LIKE LOWER(?)");
        $check_product->bind_param("ss", $search_term, $search_term);
        $check_product->execute();
        $product_result = $check_product->get_result();
        
        if ($product_result->num_rows == 0) {
            $search_message = "Không tìm thấy sản phẩm nào có tên hoặc mã khớp với '$search_term'.";
        } else {
            $search_message = "Sản phẩm '$search_term' không có tồn kho hoặc không thuộc kho nào.";
        }
    }
}

// Lấy danh sách kho theo quyền
if ($vai_tro == 'admin') {
    $query_kho = "SELECT * FROM kho";  
} elseif ($vai_tro == 'quan_ly_vung') {
    $query_kho = "SELECT * FROM kho WHERE vung_id = $vung_id";  
} else {
    $query_kho = "SELECT * FROM kho WHERE id = $kho_id";
}

$result_kho = mysqli_query($conn, $query_kho);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xuất Kho - Quản Lý Hiện Đại</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --text-color: #333;
            --background-color: #f5f6fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        button, .btn {
            background: #5e81ac;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        button:hover, .btn:hover {
            background: var(--primary-color);
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-success {
            background: var(--success-color);
        }

        .btn-disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .search-section {
            background: #f8f9fd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .search-results {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .transfer-form {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fd;
            border-radius: 5px;
        }

        .active {
            display: block;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Quản Lý Xuất Kho</h2>
        
        <!-- Hiển thị thông báo -->
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                <?php echo $_GET['message'] ?? ''; ?>
            </div>
        <?php endif; ?>
        
        <!-- Phần tìm kiếm sản phẩm -->
        <div class="search-section">
            <form method="POST">
                <div class="form-group">
                    <label for="search_term">Tìm kiếm sản phẩm</label>
                    <input type="text" id="search_term" name="search_term" placeholder="Nhập tên hoặc mã sản phẩm" required>
                </div>
                <button type="submit" name="search">Tìm kiếm</button>
            </form>
            
            <!-- Hiển thị kết quả tìm kiếm -->
            <?php if (!empty($search_results) && $search_results->num_rows > 0): ?>
                <div class="search-results">
                    <h3>Kết quả tìm kiếm</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Kho</th>
                                <th>Số lượng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $search_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['ma_san_pham']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_san_pham']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_kho'] ?? 'Chưa có kho'); ?></td>
                                    <td><?php echo htmlspecialchars($row['so_luong'] ?? '0'); ?></td>
                                    <td>
                                        <?php if ($row['so_luong'] > 0 && $row['kho_id']): ?>
                                            <button class="btn-transfer" 
                                                    data-product-id="<?php echo $row['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($row['ten_san_pham']); ?>"
                                                    data-warehouse-id="<?php echo $row['kho_id']; ?>"
                                                    data-warehouse-name="<?php echo htmlspecialchars($row['ten_kho']); ?>"
                                                    data-quantity="<?php echo $row['so_luong']; ?>">
                                                Xuất kho
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>
                                                Không đủ tồn kho
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="alert alert-info">
                    <?php echo $search_message ?: "Không tìm thấy sản phẩm phù hợp. Vui lòng kiểm tra lại tên/mã sản phẩm hoặc đảm bảo sản phẩm có tồn kho trong kho."; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Form xuất kho (ẩn cho đến khi chọn sản phẩm) -->
        <div id="transferForm" class="transfer-form">
            <h3>Xuất kho sản phẩm</h3>
            <form action="modules/quanlykho/xuly_xuatkho.php" method="POST">
                <input type="hidden" id="product_id" name="san_pham_id">
                <input type="hidden" id="source_warehouse" name="kho_xuat">
                
                <div class="form-group">
                    <label>Sản phẩm</label>
                    <input type="text" id="product_name" readonly>
                </div>
                
                <div class="form-group">
                    <label>Kho nguồn</label>
                    <input type="text" id="source_warehouse_name" readonly>
                </div>
                
                <div class="form-group">
                    <label>Số lượng hiện có</label>
                    <input type="text" id="current_quantity" readonly>
                </div>
                
                <div class="form-group">
                    <label for="so_luong">Số lượng xuất</label>
                    <input type="number" id="so_luong" name="so_luong" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="kho_dich">Kho đích</label>
                    <select name="kho_dich" id="kho_dich" required>
                        <?php 
                        mysqli_data_seek($result_kho, 0);
                        while ($row = mysqli_fetch_assoc($result_kho)): 
                            if ($row['id'] != ($_POST['kho_xuat'] ?? '')): ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['ten_kho']); ?>
                                </option>
                            <?php endif;
                        endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ghi_chu">Lý do xuất kho</label>
                    <input type="text" name="ghi_chu" placeholder="Nhập lý do xuất kho" required>
                </div>
                
                <button type="submit" class="btn-success">Xác nhận xuất kho</button>
                <button type="button" id="cancelTransfer" class="btn-danger">Hủy bỏ</button>
            </form>
        </div>
        
        <!-- Hiển thị lịch sử xuất kho -->
        <div class="history-section" style="margin-top: 40px;">
            <h3>Lịch sử xuất kho gần đây</h3>
            <?php include('modules/hienthi/HienThiXuatKho.php'); ?>
        </div>
    </div>

    <script>
        // Xử lý hiển thị form xuất kho khi chọn sản phẩm
        document.querySelectorAll('.btn-transfer').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                const warehouseId = this.getAttribute('data-warehouse-id');
                const warehouseName = this.getAttribute('data-warehouse-name');
                const quantity = this.getAttribute('data-quantity');
                
                document.getElementById('product_id').value = productId;
                document.getElementById('product_name').value = productName;
                document.getElementById('source_warehouse').value = warehouseId;
                document.getElementById('source_warehouse_name').value = warehouseName;
                document.getElementById('current_quantity').value = quantity;
                document.getElementById('so_luong').max = quantity;
                
                document.getElementById('transferForm').classList.add('active');
                document.getElementById('transferForm').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        document.getElementById('cancelTransfer').addEventListener('click', function() {
            document.getElementById('transferForm').classList.remove('active');
        });
        
        document.getElementById('so_luong').addEventListener('change', function() {
            const max = parseInt(this.max);
            if (this.value > max) {
                alert('Số lượng xuất không được vượt quá số lượng hiện có (' + max + ')');
                this.value = max;
            }
        });
    </script>
    
    
</body>
</html>