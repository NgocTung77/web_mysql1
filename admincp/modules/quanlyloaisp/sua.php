<?php

$id_loaisp = $_GET['id'];

$sql = "SELECT * FROM loai_san_pham WHERE id = '$id_loaisp'";
$run = mysqli_query($conn, $sql);
$dong = mysqli_fetch_array($run);
?>

<div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle me-2"></i>Sua sản phẩm mới
                </h2>
            </div>
            <div class="card-body">
                <form action="modules/quanlyloaisp/xuly.php?action=sua&id=<?php echo $id_loaisp; ?>" method="post" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="tenloai" class="form-label">Tên loại sản phẩm</label>
                            <input type="text" class="form-control" name="tenloai" value="<?php echo $dong['ten_loai']; ?>" required>                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div align="center"><input class="btn btn-primary w-100" type="submit" id="sua" name="sua" value="Sửa"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
       