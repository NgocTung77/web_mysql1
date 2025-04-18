<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

// Kết nối CSDL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Kết nối thất bại']));
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']));
}

$user_id = $_SESSION['user_id'];

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;
    
    // Kiểm tra sản phẩm tồn tại và lấy giá
    $sql_product = "SELECT id, gia_ban FROM san_pham WHERE id = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    
    if ($result_product->num_rows == 0) {
        die(json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']));
    }
    
    $product = $result_product->fetch_assoc();
    $price = $product['gia_ban'];
    
    // Kiểm tra số lượng tồn kho
    $sql_stock = "SELECT SUM(so_luong) AS total FROM ton_kho WHERE san_pham_id = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("i", $product_id);
    $stmt_stock->execute();
    $stock_result = $stmt_stock->get_result();
    $stock = $stock_result->fetch_assoc();
    
    if ($stock['total'] < $quantity) {
        die(json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']));
    }
    
    // Kiểm tra nếu user đã có giỏ hàng chưa
    $sql_cart = "SELECT id FROM cart WHERE user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $cart_result = $stmt_cart->get_result();
    
    if ($cart_result->num_rows == 0) {
        // Nếu chưa có giỏ hàng, tạo mới
        $sql_new_cart = "INSERT INTO cart (user_id) VALUES (?)";
        $stmt_new_cart = $conn->prepare($sql_new_cart);
        $stmt_new_cart->bind_param("i", $user_id);
        $stmt_new_cart->execute();
        $cart_id = $stmt_new_cart->insert_id;
    } else {
        // Nếu có giỏ hàng rồi, lấy ID
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
    }
    
    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $sql_check = "SELECT id, quantity FROM cart_details WHERE cart_id = ? AND san_pham_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $cart_id, $product_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    
    if ($check_result->num_rows > 0) {
        // Nếu đã có, cập nhật số lượng
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        $sql_update = "UPDATE cart_details SET quantity = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt_update->execute();
    } else {
        // Nếu chưa có, thêm vào giỏ hàng
        $sql_insert = "INSERT INTO cart_details (cart_id, san_pham_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
        $stmt_insert->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng']);
}

// Xử lý cập nhật số lượng
if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['id']) && isset($_GET['quantity'])) {
    $cart_detail_id = $_GET['id'];
    $new_quantity = intval($_GET['quantity']);
    
    // Kiểm tra số lượng hợp lệ
    if ($new_quantity < 1) {
        die(json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']));
    }
    
    // Lấy cart_id từ cart_details
    $sql_get_cart = "SELECT cart_id FROM cart_details WHERE id = ?";
    $stmt_get_cart = $conn->prepare($sql_get_cart);
    $stmt_get_cart->bind_param("i", $cart_detail_id);
    $stmt_get_cart->execute();
    $cart_result = $stmt_get_cart->get_result();
    
    if ($cart_result->num_rows == 0) {
        die(json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']));
    }
    
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    // Cập nhật số lượng
    $sql_update = "UPDATE cart_details SET quantity = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $new_quantity, $cart_detail_id);
    
    if ($stmt_update->execute()) {
        // Tính lại tổng tiền
        $sql_total = "SELECT SUM(quantity * price) as total FROM cart_details WHERE cart_id = ?";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $cart_id);
        $stmt_total->execute();
        $total_result = $stmt_total->get_result();
        $total = $total_result->fetch_assoc()['total'];
        
        // Lấy thành tiền của sản phẩm vừa cập nhật
        $sql_subtotal = "SELECT quantity * price as subtotal FROM cart_details WHERE id = ?";
        $stmt_subtotal = $conn->prepare($sql_subtotal);
        $stmt_subtotal->bind_param("i", $cart_detail_id);
        $stmt_subtotal->execute();
        $subtotal_result = $stmt_subtotal->get_result();
        $subtotal = $subtotal_result->fetch_assoc()['subtotal'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật số lượng thành công',
            'total' => number_format($total, 0, ',', '.'),
            'subtotal' => number_format($subtotal, 0, ',', '.')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cập nhật thất bại']);
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $cart_detail_id = $_GET['id'];
    
    // Lấy cart_id từ cart_details
    $sql_get_cart = "SELECT cart_id FROM cart_details WHERE id = ?";
    $stmt_get_cart = $conn->prepare($sql_get_cart);
    $stmt_get_cart->bind_param("i", $cart_detail_id);
    $stmt_get_cart->execute();
    $cart_result = $stmt_get_cart->get_result();
    
    if ($cart_result->num_rows == 0) {
        die(json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']));
    }
    
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    // Xóa sản phẩm
    $sql_delete = "DELETE FROM cart_details WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $cart_detail_id);
    
    if ($stmt_delete->execute()) {
        // Tính lại tổng tiền
        $sql_total = "SELECT SUM(quantity * price) as total FROM cart_details WHERE cart_id = ?";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $cart_id);
        $stmt_total->execute();
        $total_result = $stmt_total->get_result();
        $total = $total_result->fetch_assoc()['total'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa thành công',
            'total' => number_format($total, 0, ',', '.')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xóa thất bại']);
    }
}

$conn->close();
?>