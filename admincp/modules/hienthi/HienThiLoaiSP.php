<?php
include('./modules/config.php');

$sql = "SELECT * FROM loai_san_pham ORDER BY id DESC";
$run = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý loại sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5e81ac;
            --secondary-color: #d8dee9;
            --success-color: #88c0d0;
            --danger-color: #bf616a;
            --info-color: #81a1c1;
            --light-color: #eceff4;
            --dark-color: #2e3440;
            --transition-speed: 0.3s;
        }

        .right-content {
            padding: 20px;
            margin-left: 0;
            margin-right: 0;
            background-color: var(--light-color);
            min-height: 100vh;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .content-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: white;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            transition: all var(--transition-speed) ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
            width: 100%;
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            padding: 15px;
            color: var(--dark-color);
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
            color: var(--dark-color);
        }

        .table tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .badge {
            padding: 6px 10px;
            font-weight: 500;
            font-size: 0.8rem;
            border-radius: 20px;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .action-btns .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
            margin-right: 5px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #dee2e6;
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-btns {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .action-btns .btn {
                margin-right: 0;
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="right-content">
        <!-- Content Header -->
        <div class="content-header">
            <h1 class="content-title">
                <i class="fas fa-tags me-2"></i>Quản lý loại sản phẩm
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" id="addCategoryBtn">
                <i class="fas fa-plus me-2"></i>Thêm loại sản phẩm
            </button>
        </div>

        <!-- Categories List Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list-alt me-2"></i>Danh sách loại sản phẩm
                </h2>
                <span class="badge badge-primary">
                    <?php echo mysqli_num_rows($run); ?> loại
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">STT</th>
                                <th>Tên loại sản phẩm</th>
                                <th width="15%">ID</th>
                                <th width="15%">Mô tả</th>
                                <th width="20%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($run) > 0): ?>
                                <?php $i = 1; while ($dong = mysqli_fetch_array($run)): ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo htmlspecialchars($dong['ten_loai']); ?></td>
                                    <td><?php echo $dong['id']; ?></td>
                                    <td><?php echo htmlspecialchars($dong['mo_ta']); ?></td>
                                    <td class="action-btns">
                                        <a href="index.php?quanly=quanlyloaisp&ac=sua&id=<?php echo $dong['id']; ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-edit"></i>Sửa
                                        </a>
                                        <a href="modules/quanlyloaisp/xuly.php?action=delete&id=<?php echo $dong['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa loại sản phẩm này?');">
                                            <i class="fas fa-trash"></i>Xóa
                                        </a>
                                    </td>
                                </tr>
                                <?php $i++; endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="no-data">
                                            <i class="fas fa-box-open"></i>
                                            <p>Chưa có loại sản phẩm nào</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm loại sản phẩm -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 10px;">
                <div class="modal-header" style="background: var(--success-color); color: white; border-radius: 10px 10px 0 0;">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Thêm loại sản phẩm mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="addCategoryModalBody">
                    <form action="modules/quanlyloaisp/xuly.php" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="tenloai" class="form-label">Tên loại sản phẩm</label>
                                <input type="text" class="form-control" name="tenloai" id="tenloai" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="mota" class="form-label">Mô tả</label>
                                <input type="text" class="form-control" name="mota" id="mota" required>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100" name="them">
                                    <i class="fas fa-plus me-2"></i>Thêm loại
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>