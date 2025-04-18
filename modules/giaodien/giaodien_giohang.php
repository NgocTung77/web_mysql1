<?php


$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quan_ly_kho";

// Kết nối CSDL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để xem giỏ hàng";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy cart của user
$sql_cart = "SELECT id FROM cart WHERE user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_result = $stmt_cart->get_result();

$cart_id = null;
if ($cart_result->num_rows > 0) {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['id'];

    // Lấy sản phẩm trong cart
    $sql_items = "SELECT cd.id, sp.ten_san_pham, sp.hinh_anh, cd.quantity, cd.price, sp.id as product_id
                  FROM cart_details cd
                  JOIN san_pham sp ON cd.san_pham_id = sp.id
                  WHERE cd.cart_id = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $cart_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    
    // Tính tổng tiền và lưu thông tin sản phẩm
    $total_price = 0;
    $order_items = [];
    while ($row = $result_items->fetch_assoc()) {
        $subtotal = $row['quantity'] * $row['price'];
        $total_price += $subtotal;
        $order_items[] = $row;
    }
    
    // Lấy danh sách kho (dựa trên bảng ton_kho, chỉ lấy các kho có tồn > 0 cho các sản phẩm trong giỏ)
    $sql_kho = "SELECT DISTINCT k.id, k.ten_kho, k.dia_chi
                FROM cart_details cd
                JOIN ton_kho tk ON cd.san_pham_id = tk.san_pham_id
                JOIN kho k ON tk.kho_id = k.id
                WHERE cd.cart_id = ? 
                  AND tk.so_luong > 0";
    $stmt_kho = $conn->prepare($sql_kho);
    $stmt_kho->bind_param("i", $cart_id);
    $stmt_kho->execute();
    $result_kho = $stmt_kho->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng | Hệ thống Quản lý Kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: 'Nunito', sans-serif;
        background-color: #f8f9fa;
    }

    .cart-container {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 0.15rem 1.75rem rgba(58, 59, 69, .15);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .cart-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: #fff;
        padding: 1.5rem;
        border-bottom: none;
    }

    .total-display {
        font-size: 1.5rem;
        font-weight: bold;
        color: #007bff;
        text-align: right;
        margin-top: 20px;
        padding: 15px;
        background-color: #f1f8ff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .product-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        cursor: pointer;
    }

    .quantity-input {
        width: 50px;
        text-align: center;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 5px;
    }

    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.15rem 1.75rem rgba(58, 59, 69, .15);
    }

    .card-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: none;
        padding: 1.25rem;
    }

    .btn-checkout {
        background: linear-gradient(135deg, #28a745, #218838);
        border: none;
        padding: 12px 24px;
        font-weight: 600;
        border-radius: 8px;
    }

    .btn-checkout:hover {
        background: linear-gradient(135deg, #218838, #1e7e34);
    }

    .empty-cart {
        padding: 3rem;
        text-align: center;
    }

    .empty-cart i {
        font-size: 4rem;
        color: #6c757d;
        margin-bottom: 1.5rem;
    }

    .product-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .product-name {
        font-weight: 600;
        color: #212529;
    }

    .product-code {
        color: #6c757d;
        font-size: 0.875rem;
    }

    .price {
        font-weight: 600;
        color: #28a745;
    }

    .subtotal {
        font-weight: 700;
        color: #007bff;
    }

    .remove-btn {
        color: #dc3545;
        transition: all 0.3s ease;
    }

    .remove-btn:hover {
        color: #c82333;
        transform: scale(1.1);
    }

    .payment-method {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .payment-method:hover {
        background-color: #f8f9fa;
    }

    .payment-method i {
        font-size: 1.5rem;
    }

    .warehouse-select {
        border-radius: 8px;
        padding: 10px;
        border: 1px solid #dee2e6;
        width: 100%;
    }

    .warehouse-info {
        font-size: 0.875rem;
        color: #6c757d;
    }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row">
            <!-- Giỏ hàng -->
            <div class="col-lg-8">
                <div class="cart-container">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!$cart_id): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3 class="mb-3">Giỏ hàng của bạn đang trống</h3>
                        <p class="text-muted mb-4">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="cart-header">
                        <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng của bạn</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 120px">Sản phẩm</th>
                                    <th>Thông tin</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($order_items as $item):
                                    $subtotal = $item['quantity'] * $item['price'];
                                ?>
                                <tr data-id="<?php echo $item['id']; ?>">
                                    <td>
                                        <img src="admincp/modules/quanlychitietsp/uploads/<?php echo $item['hinh_anh']; ?>"
                                            alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>"
                                            class="product-image">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <span class="product-name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></span>
                                            <span class="product-code">Mã SP: #<?php echo $item['product_id']; ?></span>
                                        </div>
                                    </td>
                                    <td class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>₫</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="decreaseQuantity(<?php echo $item['id']; ?>)">-</button>
                                            <input type="number" class="form-control quantity-input" 
                                                   id="quantity-<?php echo $item['id']; ?>" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="increaseQuantity(<?php echo $item['id']; ?>)">+</button>
                                        </div>
                                    </td>
                                    <td class="subtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</td>
                                    <td>
                                        <button class="remove-btn" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="total-display">
                        Tổng tiền: <span id="total-price"><?php echo number_format($total_price, 0, ',', '.'); ?>₫</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form thanh toán -->
            <?php if ($cart_id): ?>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <form method="POST" action="./modules/giaodien/xuly_thanhtoan.php">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Thông tin giao hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="fullname" class="form-label">Họ và tên</label>
                                <input type="text" name="fullname" id="fullname" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="tel" name="phone" id="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ</label>
                                <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Ghi chú đơn hàng</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán</h5>
                        </div>
                        <div class="card-body">
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="cod" value="cod" class="form-check-input" checked>
                                <label for="cod" class="form-check-label">
                                    <i class="fas fa-money-bill-wave text-success"></i>
                                    Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="bank" value="bank" class="form-check-input">
                                <label for="bank" class="form-check-label">
                                    <i class="fas fa-university text-primary"></i>
                                    Chuyển khoản ngân hàng
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="momo" value="momo" class="form-check-input">
                                <label for="momo" class="form-check-label">
                                    <i class="fas fa-wallet text-danger"></i>
                                    Ví MoMo
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn kho giao hàng -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Chọn kho giao hàng</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            foreach ($order_items as $item):
                                $product_id = $item['product_id'];
                                $sql_tonkho = "SELECT tk.kho_id, k.ten_kho, k.dia_chi, tk.so_luong 
                                               FROM ton_kho tk 
                                               JOIN kho k ON tk.kho_id = k.id
                                               WHERE tk.san_pham_id = ? AND tk.so_luong > 0
                                               ORDER BY tk.so_luong DESC";
                                $stmt_tonkho = $conn->prepare($sql_tonkho);
                                $stmt_tonkho->bind_param("i", $product_id);
                                $stmt_tonkho->execute();
                                $result_tonkho = $stmt_tonkho->get_result();
                            ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo $item['ten_san_pham']; ?></label>
                                <select name="warehouse[<?php echo $product_id; ?>]" class="warehouse-select" required>
                                    <?php while ($kho = $result_tonkho->fetch_assoc()): ?>
                                    <option value="<?php echo $kho['kho_id']; ?>">
                                        <?php echo $kho['ten_kho']; ?> 
                                        (Còn <?php echo $kho['so_luong']; ?> sản phẩm)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="warehouse-info mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Chọn kho có sẵn hàng gần bạn nhất
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-checkout w-100">
                        <i class="fas fa-shopping-bag me-2"></i>Đặt hàng
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Hàm giảm số lượng
    function decreaseQuantity(cartDetailId) {
        const input = document.getElementById(`quantity-${cartDetailId}`);
        let currentValue = parseInt(input.value);
        if (currentValue > 1) {
            currentValue--;
            input.value = currentValue;
            updateQuantity(cartDetailId, currentValue);
        }
    }

    // Hàm tăng số lượng
    function increaseQuantity(cartDetailId) {
        const input = document.getElementById(`quantity-${cartDetailId}`);
        let currentValue = parseInt(input.value);
        currentValue++;
        input.value = currentValue;
        updateQuantity(cartDetailId, currentValue);
    }

    // Hàm cập nhật số lượng sản phẩm
    function updateQuantity(cartDetailId, newQuantity) {
        if (newQuantity < 1) {
            newQuantity = 1;
        }
        
        fetch(`./modules/giaodien/xuly_giohang.php?action=update&id=${cartDetailId}&quantity=${newQuantity}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật tổng tiền
                    const totalPriceElement = document.getElementById('total-price');
                    if (totalPriceElement) {
                        totalPriceElement.textContent = data.total + '₫';
                    }
                    
                    // Cập nhật số lượng
                    const quantityInput = document.getElementById(`quantity-${cartDetailId}`);
                    if (quantityInput) {
                        quantityInput.value = newQuantity;
                    }
                    
                    // Cập nhật thành tiền của sản phẩm
                    const priceElement = document.querySelector(`tr[data-id="${cartDetailId}"] .subtotal`);
                    if (priceElement) {
                        priceElement.textContent = data.subtotal + '₫';
                    }
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

    // Hàm xóa sản phẩm
    function deleteItem(cartDetailId) {
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
    </script>
</body>
</html>