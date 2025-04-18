<?php
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "quan_ly_kho"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$kho_id = $_GET['kho_id'];
$san_pham_id = $_GET['san_pham_id'];
$thang = $_GET['thang'];

// Lấy thông tin kho và sản phẩm
$q1 = $conn->query("SELECT ten_kho FROM kho WHERE id = $kho_id");
$ten_kho = $q1->fetch_assoc()['ten_kho'];

$q2 = $conn->query("SELECT ten_san_pham, don_vi_tinh FROM san_pham WHERE id = $san_pham_id");
$sp = $q2->fetch_assoc();
$ten_san_pham = $sp['ten_san_pham'];
$don_vi_tinh = $sp['don_vi_tinh'];

// Lấy giá nhập trung bình của sản phẩm
$q3 = $conn->query("
    SELECT AVG(gia_nhap) AS gia_nhap_tb 
    FROM chi_tiet_phieu_nhap 
    WHERE san_pham_id = $san_pham_id
");
$gia_nhap_tb = $q3->fetch_assoc()['gia_nhap_tb'] ?? 0;

// Lấy chi tiết các đơn hàng của sản phẩm
$sql = "
    SELECT 
        o.id AS order_id,
        o.created_at,
        od.quantity,
        od.price,
        (od.quantity * od.price) AS doanh_thu,
        (od.quantity * (od.price - $gia_nhap_tb)) AS loi_nhuan
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    WHERE od.kho_id = $kho_id
        AND od.product_id = $san_pham_id
        AND o.status = 'delivered'
        AND DATE_FORMAT(o.created_at, '%Y-%m') = '$thang'
    ORDER BY o.created_at DESC
";
$result = $conn->query($sql);

// Tạo file Excel mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Đặt tiêu đề
$sheet->setCellValue('A1', 'CHI TIẾT LỢI NHUẬN SẢN PHẨM');
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A2', 'Sản phẩm: ' . $ten_san_pham);
$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A3', 'Kho: ' . $ten_kho);
$sheet->mergeCells('A3:H3');
$sheet->setCellValue('A4', 'Tháng: ' . $thang);
$sheet->mergeCells('A4:H4');

// Đặt tiêu đề cột
$sheet->setCellValue('A6', 'STT');
$sheet->setCellValue('B6', 'Mã đơn');
$sheet->setCellValue('C6', 'Ngày đặt');
$sheet->setCellValue('D6', 'Số lượng');
$sheet->setCellValue('E6', 'Đơn giá');
$sheet->setCellValue('F6', 'Doanh thu');
$sheet->setCellValue('G6', 'Lợi nhuận');
$sheet->setCellValue('H6', 'Tỷ suất');

// Định dạng tiêu đề
$titleStyle = [
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => '000000']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9D9D9']
    ]
];

$headerStyle = [
    'font' => [
        'bold' => true,
        'size' => 11,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$sheet->getStyle('A1:H1')->applyFromArray($titleStyle);
$sheet->getStyle('A2:H4')->getFont()->setBold(true);
$sheet->getStyle('A6:H6')->applyFromArray($headerStyle);

// Điền dữ liệu
$row = 7;
$tong_so_luong = 0;
$tong_doanh_thu = 0;
$tong_loi_nhuan = 0;

$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

while ($data = $result->fetch_assoc()) {
    $tong_so_luong += $data['quantity'];
    $tong_doanh_thu += $data['doanh_thu'];
    $tong_loi_nhuan += $data['loi_nhuan'];
    $ty_suat = $data['doanh_thu'] > 0 ? ($data['loi_nhuan'] / $data['doanh_thu']) * 100 : 0;

    $sheet->setCellValue('A' . $row, $row - 6);
    $sheet->setCellValue('B' . $row, $data['order_id']);
    $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($data['created_at'])));
    $sheet->setCellValue('D' . $row, $data['quantity'] . ' ' . $don_vi_tinh);
    $sheet->setCellValue('E' . $row, number_format($data['price'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('F' . $row, number_format($data['doanh_thu'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('G' . $row, number_format($data['loi_nhuan'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('H' . $row, number_format($ty_suat, 2) . '%');

    // Áp dụng style cho dòng dữ liệu
    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
    
    // Căn giữa cho các cột số
    $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Tô màu lợi nhuận dương/âm
    if ($data['loi_nhuan'] >= 0) {
        $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('008000');
    } else {
        $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('FF0000');
    }
    
    // Tô màu tỷ suất lợi nhuận
    if ($ty_suat >= 0) {
        $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('008000');
    } else {
        $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FF0000');
    }
    
    $row++;
}

// Thêm dòng tổng cộng
$totalStyle = [
    'font' => [
        'bold' => true,
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E2EFDA']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->setCellValue('D' . $row, $tong_so_luong . ' ' . $don_vi_tinh);
$sheet->setCellValue('F' . $row, number_format($tong_doanh_thu, 0, ',', '.') . ' đ');
$sheet->setCellValue('G' . $row, number_format($tong_loi_nhuan, 0, ',', '.') . ' đ');
$sheet->setCellValue('H' . $row, number_format(($tong_doanh_thu > 0 ? ($tong_loi_nhuan / $tong_doanh_thu) * 100 : 0), 2) . '%');

// Áp dụng style cho dòng tổng cộng
$sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($totalStyle);
$sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('D' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Tô màu lợi nhuận tổng
if ($tong_loi_nhuan >= 0) {
    $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('008000');
} else {
    $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('FF0000');
}

// Tô màu tỷ suất lợi nhuận tổng
$ty_suat_tong = $tong_doanh_thu > 0 ? ($tong_loi_nhuan / $tong_doanh_thu) * 100 : 0;
if ($ty_suat_tong >= 0) {
    $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('008000');
} else {
    $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FF0000');
}

// Căn chỉnh cột
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(15);

// Căn giữa các cột số
$sheet->getStyle('A6:A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('B6:B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('D6:D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('E6:E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('F6:F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('G6:G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('H6:H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Đặt chiều cao cho các dòng
$sheet->getRowDimension(1)->setRowHeight(30);
$sheet->getRowDimension(6)->setRowHeight(25);
for ($i = 7; $i <= $row; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// Tạo file Excel
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Chi_tiet_loi_nhuan_san_pham_' . $ten_san_pham . '_' . $thang . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');

$conn->close();
?> 