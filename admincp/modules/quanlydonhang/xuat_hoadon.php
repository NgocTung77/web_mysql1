<?php
$conn = new mysqli("localhost", "root", "", "quan_ly_kho");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$id = $_GET['id'];

// Lấy thông tin đơn hàng
$sql_don = mysqli_query($conn, "
    SELECT o.*, u.ten_user 
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.id_user
    WHERE o.id = $id
");
$don = mysqli_fetch_assoc($sql_don);

// Lấy chi tiết đơn hàng
$sql_chitiet = mysqli_query($conn, "
    SELECT od.*, sp.ten_san_pham 
    FROM order_details od
    JOIN san_pham sp ON od.product_id = sp.id
    WHERE od.order_id = $id
");

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="hoadon-donhang-'.$id.'.xls"');

echo '<table border="1" cellspacing="0" cellpadding="5" style="width: 100%; font-family: Arial, sans-serif; border-collapse: collapse;">';

// Thông tin cửa hàng
echo '<tr><th colspan="6" style="background-color: #f0f0f0; text-align: center; font-size: 20px; padding: 10px;">HÓA ĐƠN MUA HÀNG</th></tr>';
echo '<tr><td colspan="2"><strong>Cửa hàng:</strong> Công Ty TNHH XYZ</td><td colspan="2"><strong>Địa chỉ:</strong> 123 Đường ABC</td><td colspan="2"><strong>SĐT:</strong> 0901234567</td></tr>';

// Thông tin đơn hàng
echo '<tr><td><strong>Mã đơn hàng:</strong></td><td>#'.$don['id'].'</td>
          <td><strong>Khách hàng:</strong></td><td>'.$don['ten_user'].'</td>
          <td><strong>Ngày:</strong></td><td>'.$don['created_at'].'</td></tr>';
echo '<tr><td colspan="6"><strong>Ghi chú:</strong> '.$don['notes'].'</td></tr>';
echo '</table><br>';

echo '<table border="1" cellspacing="0" cellpadding="5" style="width: 100%; font-family: Arial, sans-serif; border-collapse: collapse;">';
echo '<tr style="background-color: #ddd;">
        <th style="padding: 5px; text-align: center;">STT</th>
        <th style="padding: 5px; text-align: center;">Tên sản phẩm</th>
        <th style="padding: 5px; text-align: center;">Số lượng</th>
        <th style="padding: 5px; text-align: center;">Đơn giá</th>
        <th style="padding: 5px; text-align: center;">Thành tiền</th>
      </tr>';

$stt = 1;
$tong = 0;
while ($row = mysqli_fetch_assoc($sql_chitiet)) {
    $thanhtien = $row['quantity'] * $row['price'];
    $tong += $thanhtien;
    echo '<tr>
            <td style="padding: 5px; text-align: center;">'.$stt++.'</td>
            <td style="padding: 5px; text-align: left;">'.$row['ten_san_pham'].'</td>
            <td style="padding: 5px; text-align: center;">'.$row['quantity'].'</td>
            <td style="padding: 5px; text-align: right;">'.number_format($row['price']).' đ</td>
            <td style="padding: 5px; text-align: right;">'.number_format($thanhtien).' đ</td>
          </tr>';
}
echo '<tr>
        <td colspan="4" style="padding: 5px; text-align: right;"><strong>Tổng cộng:</strong></td>
        <td style="padding: 5px; text-align: right;"><strong>'.number_format($tong).' đ</strong></td>
      </tr>';
echo '</table>';
?>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 </body>