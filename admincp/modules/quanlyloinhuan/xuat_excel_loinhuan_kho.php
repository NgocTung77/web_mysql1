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
$thang = $_GET['thang'];

// Lấy thông tin kho
$q1 = $conn->query("SELECT ten_kho FROM kho WHERE id = $kho_id");
$ten_kho = $q1->fetch_assoc()['ten_kho'];

// Lấy danh sách sản phẩm và lợi nhuận
$sql = "
    SELECT 
        sp.id,
        sp.ten_san_pham,
        sp.don_vi_tinh,
        COUNT(DISTINCT o.id) AS so_don,
        SUM(od.quantity) AS tong_so_luong,
        SUM(od.quantity * od.price) AS tong_doanh_thu,
        SUM(od.quantity * (od.price - (
            SELECT AVG(gia_nhap) 
            FROM chi_tiet_phieu_nhap 
            WHERE san_pham_id = sp.id
        ))) AS tong_loi_nhuan,
        COALESCE(AVG(od.price), 0) AS gia_ban
    FROM san_pham sp
    JOIN order_details od ON sp.id = od.product_id
    JOIN orders o ON od.order_id = o.id
    WHERE od.kho_id = $kho_id
        AND o.status = 'delivered'
        AND DATE_FORMAT(o.created_at, '%Y-%m') = '$thang'
    GROUP BY sp.id, sp.ten_san_pham, sp.don_vi_tinh
    ORDER BY tong_loi_nhuan DESC
";
$result = $conn->query($sql);

// Tạo file Excel mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Đặt tiêu đề
$sheet->setCellValue('A1', 'CHI TIẾT LỢI NHUẬN KHO');
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A2', 'Kho: ' . $ten_kho);
$sheet->mergeCells('A2:J2');
$sheet->setCellValue('A3', 'Tháng: ' . $thang);
$sheet->mergeCells('A3:J3');

// Đặt tiêu đề cột
$sheet->setCellValue('A5', 'STT');
$sheet->setCellValue('B5', 'Mã SP');
$sheet->setCellValue('C5', 'Tên sản phẩm');
$sheet->setCellValue('D5', 'Đơn vị tính');
$sheet->setCellValue('E5', 'Số đơn');
$sheet->setCellValue('F5', 'Số lượng bán');
$sheet->setCellValue('G5', 'Giá bán');
$sheet->setCellValue('H5', 'Doanh thu');
$sheet->setCellValue('I5', 'Lợi nhuận');
$sheet->setCellValue('J5', 'Tỷ suất');

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

$sheet->getStyle('A1:J1')->applyFromArray($titleStyle);
$sheet->getStyle('A2:J3')->getFont()->setBold(true);
$sheet->getStyle('A5:J5')->applyFromArray($headerStyle);

// Điền dữ liệu
$row = 6;
$tong_so_don = 0;
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
    $tong_so_don += $data['so_don'];
    $tong_so_luong += $data['tong_so_luong'];
    $tong_doanh_thu += $data['tong_doanh_thu'];
    $tong_loi_nhuan += $data['tong_loi_nhuan'];
    
    $ty_suat = $data['tong_doanh_thu'] > 0 ? ($data['tong_loi_nhuan'] / $data['tong_doanh_thu']) * 100 : 0;

    $sheet->setCellValue('A' . $row, $row - 5);
    $sheet->setCellValue('B' . $row, $data['id']);
    $sheet->setCellValue('C' . $row, $data['ten_san_pham']);
    $sheet->setCellValue('D' . $row, $data['don_vi_tinh']);
    $sheet->setCellValue('E' . $row, $data['so_don']);
    $sheet->setCellValue('F' . $row, $data['tong_so_luong']);
    $sheet->setCellValue('G' . $row, number_format($data['gia_ban'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('H' . $row, number_format($data['tong_doanh_thu'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('I' . $row, number_format($data['tong_loi_nhuan'], 0, ',', '.') . ' đ');
    $sheet->setCellValue('J' . $row, number_format($ty_suat, 2) . '%');

    // Áp dụng style cho dòng dữ liệu
    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
    
    // Căn giữa cho các cột số
    $sheet->getStyle('A' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $row . ':J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Tô màu lợi nhuận dương/âm
    if ($data['tong_loi_nhuan'] >= 0) {
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('008000');
    } else {
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('FF0000');
    }
    
    // Tô màu tỷ suất lợi nhuận
    if ($ty_suat >= 0) {
        $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('008000');
    } else {
        $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('FF0000');
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
$sheet->setCellValue('E' . $row, $tong_so_don);
$sheet->setCellValue('F' . $row, $tong_so_luong);
$sheet->setCellValue('H' . $row, number_format($tong_doanh_thu, 0, ',', '.') . ' đ');
$sheet->setCellValue('I' . $row, number_format($tong_loi_nhuan, 0, ',', '.') . ' đ');
$sheet->setCellValue('J' . $row, number_format(($tong_doanh_thu > 0 ? ($tong_loi_nhuan / $tong_doanh_thu) * 100 : 0), 2) . '%');

// Áp dụng style cho dòng tổng cộng
$sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($totalStyle);
$sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('E' . $row . ':J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Tô màu lợi nhuận tổng
if ($tong_loi_nhuan >= 0) {
    $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('008000');
} else {
    $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('FF0000');
}

// Tô màu tỷ suất lợi nhuận tổng
$ty_suat_tong = $tong_doanh_thu > 0 ? ($tong_loi_nhuan / $tong_doanh_thu) * 100 : 0;
if ($ty_suat_tong >= 0) {
    $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('008000');
} else {
    $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('FF0000');
}

// Căn chỉnh cột
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(30);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(10);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(15);

// Đặt chiều cao cho các dòng
$sheet->getRowDimension(1)->setRowHeight(30);
$sheet->getRowDimension(5)->setRowHeight(25);
for ($i = 6; $i <= $row; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// Tạo file Excel
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Chi_tiet_loi_nhuan_kho_' . $ten_kho . '_' . $thang . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');

$conn->close();
?> 