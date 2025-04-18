<?php
// Tăng giới hạn bộ nhớ và thời gian thực thi
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 phút

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id_sp = $_GET['id_sp'];
$thang = $_GET['thang'];

// Lấy tên sản phẩm
$q1 = $conn->query("SELECT ten_san_pham FROM san_pham WHERE id = $id_sp");
$ten_sp = $q1->fetch_assoc()['ten_san_pham'];

// Lấy dữ liệu đơn hàng, nhóm theo kho
$sql = "
    SELECT 
        k.ten_kho,
        k.id AS kho_id,
        o.id AS don_id,
        o.created_at,
        od.quantity,
        od.price,
        o.notes AS ghichu,
        kh.ten_user AS ten_khachhang,
        AVG(ctpn.gia_nhap) AS gia_nhap_tb
    FROM order_details od
    JOIN orders o ON od.order_id = o.id
    LEFT JOIN user kh ON o.user_id = kh.id_user
    JOIN kho k ON od.kho_id = k.id
    LEFT JOIN chi_tiet_phieu_nhap ctpn ON od.product_id = ctpn.san_pham_id
    WHERE od.product_id = $id_sp
        AND o.status = 'delivered'
        AND DATE_FORMAT(o.created_at, '%Y-%m') = '$thang'
    GROUP BY k.id, k.ten_kho, o.id, od.quantity, od.price, o.notes, kh.ten_user
    ORDER BY k.ten_kho, o.created_at DESC
";

// Sử dụng unbuffered query để tiết kiệm bộ nhớ
$result = $conn->query($sql, MYSQLI_USE_RESULT);

// Thiết lập header cho file Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Chi_tiet_loi_nhuan_{$ten_sp}_{$thang}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Bắt đầu xuất dữ liệu
echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
echo "<style>
    table { border-collapse: collapse; width: 100%; font-family: Arial; }
    th, td { border: 1px solid #000; padding: 8px; text-align: center; }
    th { background-color: #4CAF50; color: white; }
    h3 { text-align: center; color: darkblue; }
    .bold { font-weight: bold; text-align: right; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .total-row { background-color: #f2f2f2; }
</style>";

echo "<h3>BÁO CÁO CHI TIẾT LỢI NHUẬN</h3>";
echo "<p style='text-align: center;'><b>Sản phẩm:</b> $ten_sp | <b>Tháng:</b> $thang</p>";

echo "<table>
    <tr>
        <th>STT</th>
        <th>Kho</th>
        <th>Mã đơn</th>
        <th>Ngày bán</th>
        <th>Khách hàng</th>
        <th>Số lượng</th>
        <th>Đơn giá</th>
        <th>Thành tiền</th>
        <th>Lợi nhuận</th>
        <th>Ghi chú</th>
    </tr>";

$stt = 1;
$tong_doanhthu = 0;
$tong_loinhuan = 0;

// Xử lý dữ liệu theo từng dòng để tiết kiệm bộ nhớ
while ($row = $result->fetch_assoc()) {
    $thanhtien = $row['quantity'] * $row['price'];
    $loinhuan = $thanhtien - ($row['quantity'] * ($row['gia_nhap_tb'] ?? 0));
    $tong_doanhthu += $thanhtien;
    $tong_loinhuan += $loinhuan;

    $loinhuan_class = $loinhuan >= 0 ? 'text-success' : 'text-danger';
    
    echo "<tr>
        <td>$stt</td>
        <td class='text-left'>{$row['ten_kho']}</td>
        <td>#{$row['don_id']}</td>
        <td>".date('d/m/Y H:i', strtotime($row['created_at']))."</td>
        <td class='text-left'>".($row['ten_khachhang'] ?? 'Không rõ')."</td>
        <td>{$row['quantity']}</td>
        <td class='text-right'>".number_format($row['price'], 0, ',', '.')."</td>
        <td class='text-right'>".number_format($thanhtien, 0, ',', '.')."</td>
        <td class='text-right $loinhuan_class'>".number_format($loinhuan, 0, ',', '.')."</td>
        <td class='text-left'>{$row['ghichu']}</td>
    </tr>";
    $stt++;
    
    // Xóa bộ nhớ đệm sau mỗi 100 dòng
    if ($stt % 100 == 0) {
        flush();
        ob_flush();
    }
}

// Dòng tổng cộng
echo "<tr class='total-row'>
    <td colspan='7' class='text-right bold'>TỔNG CỘNG:</td>
    <td class='text-right bold'>".number_format($tong_doanhthu, 0, ',', '.')."</td>
    <td class='text-right bold'>".number_format($tong_loinhuan, 0, ',', '.')."</td>
    <td></td>
</tr>";

echo "</table>";

// Đóng kết nối
$result->close();
$conn->close();
?>