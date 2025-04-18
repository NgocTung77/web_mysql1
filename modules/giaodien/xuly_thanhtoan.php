<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để thanh toán";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin từ form
$fullname = $conn->real_escape_string($_POST['fullname']);
$phone = $conn->real_escape_string($_POST['phone']);
$email = $conn->real_escape_string($_POST['email'] ?? '');
$address = $conn->real_escape_string($_POST['address']);
$payment_method = $conn->real_escape_string($_POST['payment_method']);
$notes = $conn->real_escape_string($_POST['notes'] ?? '');
$kho_for_product = $_POST['kho_for_product'] ?? []; // Danh sách kho cho từng sản phẩm

// Lấy giỏ hàng của người dùng
$sql_cart = "SELECT id FROM cart WHERE user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

if ($result_cart->num_rows === 0) {
    $_SESSION['error'] = "Giỏ hàng của bạn đang trống.";
    header("Location: cart.php");
    exit();
}

$cart = $result_cart->fetch_assoc();
$cart_id = $cart['id'];

// Lấy sản phẩm trong giỏ hàng
$sql_items = "SELECT cd.*, sp.ten_san_pham, sp.hinh_anh, sp.id as product_id
              FROM cart_details cd
              JOIN san_pham sp ON cd.san_pham_id = sp.id
              WHERE cd.cart_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $cart_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

if ($result_items->num_rows === 0) {
    $_SESSION['error'] = "Giỏ hàng trống, không thể thanh toán.";
    header("Location: cart.php");
    exit();
}

// Tính tổng tiền
$total_amount = 0;
$order_items = [];

while ($item = $result_items->fetch_assoc()) {
    $subtotal = $item['quantity'] * $item['price'];
    $total_amount += $subtotal;
    $order_items[] = [
        'product_id' => $item['product_id'],
        'product_name' => $item['ten_san_pham'],
        'product_image' => $item['hinh_anh'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'subtotal' => $subtotal,
        'kho_id' => $kho_for_product[$item['product_id']] ?? 0 // Lấy kho được chọn cho sản phẩm
    ];
}

// Tạo mã đơn hàng
$order_code = 'DH' . strtoupper(uniqid());

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Thêm đơn hàng
    $sql_order = "INSERT INTO orders (user_id, order_code, fullname, phone, email, address, 
        payment_method, notes, total_amount, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("isssssssd", $user_id, $order_code, $fullname, $phone, $email,
                            $address, $payment_method, $notes, $total_amount);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;

    // Thêm chi tiết đơn hàng
    foreach ($order_items as $item) {
        $sql_detail = "INSERT INTO order_details 
            (order_id, product_id, product_name, product_image, quantity, price, subtotal, kho_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        $stmt_detail->bind_param("iissidii", $order_id, $item['product_id'], $item['product_name'],
                                $item['product_image'], $item['quantity'], $item['price'], $item['subtotal'], $item['kho_id']);
        $stmt_detail->execute();
    }

    // Xóa sản phẩm khỏi giỏ hàng
    $stmt_delete = $conn->prepare("DELETE FROM cart_details WHERE cart_id = ?");
    $stmt_delete->bind_param("i", $cart_id);
    $stmt_delete->execute();

    // Lưu lịch sử trạng thái đơn hàng
    $sql_status = "INSERT INTO order_status_history (order_id, status, notes, created_by)
                   VALUES (?, 'pending', 'Đơn hàng được tạo', ?)";
    $stmt_status = $conn->prepare($sql_status);
    $stmt_status->bind_param("ii", $order_id, $user_id);
    $stmt_status->execute();

    // Commit
    $conn->commit();

    // Hiển thị hóa đơn
    $_SESSION['order_success'] = $order_code;
    header("Location: http://localhost/web_mysql1/index.php?xem=giaodien_giohang");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Có lỗi khi đặt hàng: " . $e->getMessage();
    header("Location: http://localhost/web_mysql1/index.php?xem=giaodien_giohang");
    exit();
}
?>