<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quan_ly_kho";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Lấy dữ liệu từ form
    $tensp = mysqli_real_escape_string($conn, $_POST['ten_san_pham']);
    $mota = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $thongsosp = mysqli_real_escape_string($conn, $_POST['thongsosp']);
    $loaisp = mysqli_real_escape_string($conn, $_POST['loai_san_pham']);
    $hieusp = mysqli_real_escape_string($conn, $_POST['hieusp']);
    $ma_san_pham = "SP-" . time();
    $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 0;
    
    // Thư mục lưu ảnh
    $target_dir = __DIR__ . "/uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($action == 'them') {
        $hinh_anh = '';

        // Xử lý upload ảnh
        if (!empty($_FILES['hinh_anh']['name'])) {
            $hinh_anh = basename($_FILES['hinh_anh']['name']);
            $target_file = $target_dir . $hinh_anh;
            move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file);
        }

        // Chèn vào bảng `san_pham`
        $stmt = $conn->prepare("INSERT INTO san_pham (ma_san_pham, ten_san_pham, mo_ta, thongsosp, loai_id, hinh_anh, hieusp) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $ma_san_pham, $tensp, $mota, $thongsosp, $loaisp, $hinh_anh, $hieusp);

        if ($stmt->execute()) {
            // Lấy ID sản phẩm vừa chèn
            $san_pham_id = $stmt->insert_id;

            // Lấy danh sách tất cả các kho
            $query_kho = "SELECT id FROM kho";
            $result_kho = mysqli_query($conn, $query_kho);

            // Chèn sản phẩm vào tất cả các kho với `so_luong = 0`
            while ($row = mysqli_fetch_assoc($result_kho)) {
                $kho_id = $row['id'];
                $sql_insert_ton_kho = "INSERT INTO ton_kho (kho_id, san_pham_id, so_luong) VALUES ($kho_id, $san_pham_id, $so_luong)";
                mysqli_query($conn, $sql_insert_ton_kho);
            }

            header('Location: index.php?quanly=quanlychitietsp&ac=them');
            exit();
        } else {
            echo "Lỗi khi thêm sản phẩm: " . $stmt->error;
        }

        $stmt->close();
    }
}
    
    if ($action == 'Cập nhật sản phẩm') {
        $id_sp = mysqli_real_escape_string($conn, $_POST['id_sp']);

        // Lấy ảnh hiện tại từ database
        $sql_select = "SELECT hinh_anh FROM san_pham WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id_sp);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $row = $result->fetch_assoc();
        $current_image = $row['hinh_anh'];

        // Xử lý upload ảnh mới (nếu có)
        if (!empty($_FILES['hinh_anh']['name'])) {
            $hinh_anh = basename($_FILES['hinh_anh']['name']);
            $target_file = $target_dir . $hinh_anh;
            move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file);
        } else {
            $hinh_anh = $current_image;
        }

        // Cập nhật sản phẩm với prepared statement
        $stmt_update = $conn->prepare("UPDATE san_pham 
                                       SET ten_san_pham=?, mo_ta=?, thongsosp=?, loai_id=?, hinh_anh=?, hieusp=? 
                                       WHERE id=?");
        $stmt_update->bind_param("ssssssi", $tensp, $mota, $thongsosp, $loaisp, $hinh_anh, $hieusp, $id_sp);

        if ($stmt_update->execute()) {
            header('Location: index.php?quanly=quanlychitietsp&ac=them');
            exit();
        } else {
            echo "Lỗi khi cập nhật sản phẩm: " . $stmt_update->error;
        }

        $stmt_update->close();
        $stmt_select->close();
    }

// Đóng kết nối
$conn->close();
?>