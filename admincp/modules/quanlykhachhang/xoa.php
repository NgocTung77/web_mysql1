<?php
include('../config.php');

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']); 
    $action = $_GET['action'];

    if ($action == 'delete') {
        
        $sql_delete_order_details = "DELETE FROM order_details WHERE order_id IN (SELECT order_id FROM orders WHERE user_id = $id)";
        $result_order_details = mysqli_query($conn, $sql_delete_order_details);

        
        $sql_delete_orders = "DELETE FROM orders WHERE user_id = $id";
        $result_orders = mysqli_query($conn, $sql_delete_orders);

        
        $sql_delete_cart = "DELETE FROM cart WHERE user_id = $id";
        $result_cart = mysqli_query($conn, $sql_delete_cart);

        
        $sql_delete_reviews = "DELETE FROM reviews WHERE user_id = $id";
        $result_reviews = mysqli_query($conn, $sql_delete_reviews);

        
        $sql_delete_user = "DELETE FROM users WHERE id = $id";
        $result_user = mysqli_query($conn, $sql_delete_user);

        
        if ($result_order_details && $result_orders && $result_cart && $result_reviews && $result_user) {
            // Chuyển hướng sau khi xóa thành công
            header("Location: http://localhost/web_mysql/admincp/index.php?quanly=quanlykhachhang&ac=them");
            exit();
        } else {
            // Thông báo lỗi nếu có vấn đề
            echo "Xóa thất bại: " . mysqli_error($conn);
        }
    }
}
?>
