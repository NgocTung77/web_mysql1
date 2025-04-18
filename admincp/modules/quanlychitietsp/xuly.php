<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";


try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}

// Xử lý upload ảnh
function handleImageUpload($current_image = null) {
    $target_dir = __DIR__ . "/uploads/";
    
    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            throw new Exception("Không thể tạo thư mục upload");
        }
    }

    // Nếu không có file upload, trả về ảnh hiện tại
    if (empty($_FILES['hinh_anh']['name'])) {
        return $current_image;
    }

    $file_name = basename($_FILES['hinh_anh']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    
    $check = getimagesize($_FILES['hinh_anh']['tmp_name']);
    if ($check === false) {
        throw new Exception("File upload không phải là ảnh hợp lệ");
    }

    // Giới hạn loại file
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        throw new Exception("Chỉ chấp nhận file JPG, JPEG, PNG & GIF");
    }

    // Giới hạn kích thước file (5MB)
    if ($_FILES['hinh_anh']['size'] > 5000000) {
        throw new Exception("File upload quá lớn (tối đa 5MB)");
    }

    // Tạo tên file mới để tránh trùng lặp
    $new_filename = uniqid('img_', true) . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    if (!move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
        throw new Exception("Có lỗi khi upload file");
    }

    // Xóa ảnh cũ nếu có
    if ($current_image && file_exists($target_dir . $current_image)) {
        unlink($target_dir . $current_image);
    }

    return $new_filename;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Validate dữ liệu đầu vào
        $required_fields = ['ten_san_pham', 'mo_ta', 'thongsosp', 'loai_san_pham', 'hieusp'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Vui lòng điền đầy đủ thông tin");
            }
        }

        // Lấy dữ liệu từ form
        $tensp = htmlspecialchars($_POST['ten_san_pham']);
        $mota = htmlspecialchars($_POST['mo_ta']);
        $thongsosp = htmlspecialchars($_POST['thongsosp']);
        $loaisp = (int)$_POST['loai_san_pham'];
        $hieusp = htmlspecialchars($_POST['hieusp']);
        $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 0;

        if ($_POST['action'] === 'them') {
            // Xử lý thêm sản phẩm mới
            $ma_san_pham = "SP-" . time();
            $hinh_anh = handleImageUpload();

            // Thêm sản phẩm vào CSDL
            $stmt = $conn->prepare("INSERT INTO san_pham (ma_san_pham, ten_san_pham, mo_ta, thongsosp, loai_id, hinh_anh, hieusp) 
                                    VALUES (:ma_sp, :ten_sp, :mo_ta, :thong_so, :loai_id, :hinh_anh, :hieu_sp)");
            
            $stmt->execute([
                ':ma_sp' => $ma_san_pham,
                ':ten_sp' => $tensp,
                ':mo_ta' => $mota,
                ':thong_so' => $thongsosp,
                ':loai_id' => $loaisp,
                ':hinh_anh' => $hinh_anh,
                ':hieu_sp' => $hieusp
            ]);

            $product_id = $conn->lastInsertId();

            // Thêm vào tồn kho nếu có số lượng
            if ($so_luong > 0) {
                $default_warehouse = 1; // ID kho mặc định
                $stmt = $conn->prepare("INSERT INTO ton_kho (san_pham_id, kho_id, so_luong) 
                                        VALUES (:sp_id, :kho_id, :so_luong)
                                        ON DUPLICATE KEY UPDATE so_luong = so_luong + :so_luong");
                $stmt->execute([
                    ':sp_id' => $product_id,
                    ':kho_id' => $default_warehouse,
                    ':so_luong' => $so_luong
                ]);
            }

            $_SESSION['success_message'] = "Thêm sản phẩm thành công!";
            header('Location: ../../index.php?quanly=quanlychitietsp');
            exit();

        } elseif ($_POST['action'] === 'Cập nhật sản phẩm') {
            // Xử lý cập nhật sản phẩm
            if (!isset($_POST['id_sp']) || !is_numeric($_POST['id_sp'])) {
                throw new Exception("ID sản phẩm không hợp lệ");
            }

            $id_sp = (int)$_POST['id_sp'];

            // Lấy ảnh hiện tại
            $stmt = $conn->prepare("SELECT hinh_anh FROM san_pham WHERE id = :id");
            $stmt->execute([':id' => $id_sp]);
            $current_image = $stmt->fetchColumn();

            // Xử lý upload ảnh mới
            $hinh_anh = handleImageUpload($current_image);

            // Cập nhật sản phẩm
            $stmt = $conn->prepare("UPDATE san_pham 
                                   SET ten_san_pham = :ten_sp, 
                                       mo_ta = :mo_ta, 
                                       thongsosp = :thong_so, 
                                       loai_id = :loai_id, 
                                       hinh_anh = :hinh_anh, 
                                       hieusp = :hieu_sp
                                   WHERE id = :id");
            
            $stmt->execute([
                ':ten_sp' => $tensp,
                ':mo_ta' => $mota,
                ':thong_so' => $thongsosp,
                ':loai_id' => $loaisp,
                ':hinh_anh' => $hinh_anh,
                ':hieu_sp' => $hieusp,
                ':id' => $id_sp
            ]);

            $_SESSION['success_message'] = "Cập nhật sản phẩm thành công!";
            header('Location: ../../index.php?quanly=quanlychitietsp');
            exit();
        }
    }
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Error in product processing: " . $e->getMessage());
    
    // Lưu thông báo lỗi vào session
    $_SESSION['error_message'] = $e->getMessage();
    
    // Lưu dữ liệu form để hiển thị lại
    $_SESSION['form_data'] = $_POST;
    
    // Chuyển hướng về trang trước
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?quanly=quanlychitietsp&ac=them';
    header("Location: $redirect_url");
    exit();
} finally {
    // Đóng kết nối
    $conn = null;
}