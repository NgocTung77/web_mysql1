<?php
include('./modules/config.php');
$vai_tro = $_SESSION['vai_tro'] ?? '';
$vung_id = $_SESSION['vung_id'] ?? null;

$dieukien_kho = "";

if ($vai_tro == 'admin') {
    $dieukien_kho = ""; 
} elseif ($vai_tro == 'quan_ly_vung') {
    if (!isset($vung_id)) {
        die("Lỗi: Không xác định được vùng quản lý!");
    }
    $dieukien_kho = "WHERE k.vung_id = $vung_id"; 
} else {
    die("Lỗi: Không có quyền truy cập!");
}

// Truy vấn danh sách kho (lọc theo vùng nếu có)
$sql_kho = "SELECT * FROM kho k $dieukien_kho";
$query_kho = mysqli_query($conn, $sql_kho);
if (!$query_kho) {
    die("Lỗi truy vấn kho: " . mysqli_error($conn));
}

// Truy vấn toàn bộ sản phẩm (KHÔNG lọc theo vùng nữa)
$sql_sp = "SELECT * FROM san_pham";
$query_sp = mysqli_query($conn, $sql_sp);
if (!$query_sp) {
    die("Lỗi truy vấn sản phẩm: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập Hàng Vào Kho</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #3498db;
        --success-color: #28a745;
    }

    body {
        background-color: #f8f9fa;
        padding-top: 20px;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 30px;
    }

    .card-header {
        background-color: var(--primary-color);
        color: white;
        border-radius: 10px 10px 0 0 !important;
        padding: 1.2rem;
    }

    .btn-primary {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-secondary {
        border-color: var(--secondary-color);
        color: var(--secondary-color);
    }

    .btn-outline-secondary:hover {
        background-color: var(--secondary-color);
        color: white;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }

    .total-display {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
        text-align: right;
        margin-top: 20px;
        padding: 10px;
        background-color: #f1f8ff;
        border-radius: 5px;
    }

    .product-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }

    .add-product-btn {
        margin-bottom: 20px;
    }
    </style>

</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-0"><i class="fas fa-boxes me-2"></i>NHẬP HÀNG VÀO KHO</h3>
                    </div>
                    <div class="card-body">
                        <form action="modules/quanlykho/xuly_nhapkho.php" method="POST" id="importForm">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="kho_id" class="form-label fw-bold">Kho nhập hàng</label>
                                    <select class="form-select" id="kho_id" name="kho_id" required>
                                        <option value="" selected disabled>-- Chọn kho --</option>
                                        <?php while ($row = mysqli_fetch_assoc($query_kho)) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['ten_kho']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ngày nhập</label>
                                    <input type="date" class="form-control" name="ngay_nhap"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <h5 class="mb-3"><i class="fas fa-list-ol me-2"></i>Danh sách sản phẩm</h5>

                            <div id="productContainer">
                                <!-- Product item template -->
                                <div class="product-item">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Sản phẩm</label>
                                            <select class="form-select" name="san_pham_id[]" required>
                                                <option value="" selected disabled>-- Chọn sản phẩm --</option>
                                                <?php 
                                                mysqli_data_seek($query_sp, 0);
                                                while ($row = mysqli_fetch_assoc($query_sp)) { ?>
                                                <option value="<?php echo $row['id']; ?>">
                                                    <?php echo $row['ten_san_pham']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Số lượng</label>
                                            <input type="number" class="form-control" name="so_luong[]" min="1"
                                                value="1" required oninput="calculateTotal()">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Đơn vị tính</label>
                                            <input type="text" class="form-control" name="don_vi_tinh[]"
                                                placeholder="Cái, Hộp...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Giá nhập</label>
                                            <input type="number" class="form-control" name="gia_nhap[]" min="1" required
                                                oninput="calculateTotal()">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Giá bán</label>
                                            <input type="number" class="form-control" name="gia_ban[]" min="1" required>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-3">
                                            <label class="form-label">Giá nhập tròn</label>
                                            <input type="text" class="form-control" name="gia_nhap_tron[]" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Độ lệch</label>
                                            <input type="text" class="form-control" name="do_lech[]" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ghi chú</label>
                                            <input type="text" class="form-control" name="ghi_chu[]"
                                                placeholder="Ghi chú (nếu có)">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-secondary add-product-btn"
                                onclick="addProduct()">
                                <i class="fas fa-plus-circle me-2"></i>Thêm sản phẩm
                            </button>

                            <div class="total-display">
                                <span id="tong_tien">0 VND</span>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times-circle me-2"></i>Hủy bỏ
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Lưu phiếu nhập
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div>
        <?php
            include('modules/hienthi/HienThiNhapKho.php');
        ?>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Thêm sản phẩm mới vào form
    function addProduct() {
        const container = document.getElementById('productContainer');
        const newProduct = document.createElement('div');
        newProduct.className = 'product-item';
        newProduct.innerHTML = `
        <div class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="san_pham_id[]" required>
                    <option value="" selected disabled>-- Chọn sản phẩm --</option>
                    ${productOptions}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="so_luong[]" min="1" value="1" required oninput="calculateTotal()">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="don_vi_tinh[]" placeholder="Cái, Hộp...">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="gia_nhap[]" min="1" required oninput="calculateTotal()">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="gia_ban[]" min="1" required>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <input type="text" class="form-control" name="gia_nhap_tron[]" readonly>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="do_lech[]" readonly>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="ghi_chu[]" placeholder="Ghi chú (nếu có)">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeProduct(this)">
            <i class="fas fa-trash-alt me-1"></i>Xóa
        </button>
    `;
        container.appendChild(newProduct);
    }


    // Xóa sản phẩm khỏi form
    function removeProduct(button) {
        const productItem = button.closest('.product-item');
        productItem.remove();
        calculateTotal();
    }

    // Tính toán tổng tiền và các giá trị liên quan
// Tính toán tổng tiền và các giá trị liên quan
function calculateTotal() {
    let total = 0;
    const giaNhapInputs = document.querySelectorAll('input[name="gia_nhap[]"]');
    const giaNhapTronInputs = document.querySelectorAll('input[name="gia_nhap_tron[]"]');
    const doLechInputs = document.querySelectorAll('input[name="do_lech[]"]');
    const soLuongInputs = document.querySelectorAll('input[name="so_luong[]"]');

    giaNhapInputs.forEach((input, index) => {
        const giaNhap = parseFloat(input.value);
        const soLuong = parseInt(soLuongInputs[index].value);

        if (!isNaN(giaNhap) && !isNaN(soLuong)) {
            const giaNhapTron = Math.round(giaNhap / 1000) * 1000;
            const doLech = giaNhapTron - giaNhap;

            // Sửa: giữ nguyên giá trị số thuần
            giaNhapTronInputs[index].value = giaNhapTron;
            doLechInputs[index].value = doLech;

            total += giaNhapTron * soLuong;
        } else {
            giaNhapTronInputs[index].value = '';
            doLechInputs[index].value = '';
        }
    });

    // Format hiển thị tổng tiền
    document.getElementById('tong_tien').innerText = total.toLocaleString('vi-VN') + ' VND';
}


    // Tính toán ngay khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
    });
    </script>

</body>

</html>