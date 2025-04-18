    <?php
include('../config.php');

// Retrieve data from the form
$tenloaisp = $_POST['tenloai'];

if (isset($_POST['them'])) {
    $sql = "INSERT INTO loai_san_pham (ten_loai) VALUES ('$tenloaisp')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../../index.php?quanly=quanlyloaisp&ac=them');
        exit();
    } else {
        echo "Error inserting data: " . mysqli_error($conn);
    }
}


if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    // Delete action
    if ($action == 'delete') {
        $sql = "DELETE FROM loai_san_pham WHERE id = $id";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            header("Location: ../../index.php?quanly=quanlyloaisp&ac=them"); 
            exit();
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
        }
    }

    if ($action == 'sua') {
        // Prepare the update query
        $sql = "UPDATE loai_san_pham SET ten_loai = '$tenloaisp' WHERE id = $id";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            header("Location: ../../index.php?quanly=quanlyloaisp&ac=them"); 
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    }
}

// Close the database connection
mysqli_close($conn);
?>
