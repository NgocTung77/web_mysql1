<?php
session_start();

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quan_ly_kho";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if (!isset($_SESSION['id']) || $_SESSION['vai_tro'] !== 'quan_ly_vung') {
    header('Location: login.php');
    exit();
}

$vung_id = $_SESSION['vung_id']; 

// Lấy danh sách kho
$sql = "SELECT k.id, k.ten_kho, k.dia_chi, 
               COALESCE(SUM(tk.so_luong), 0) AS tong_hang
        FROM kho k
        LEFT JOIN ton_kho tk ON k.id = tk.kho_id
        WHERE k.vung_id = $vung_id
        GROUP BY k.id, k.ten_kho, k.dia_chi";

$result = mysqli_query($conn, $sql);

// Lấy dữ liệu cho biểu đồ
$chart_sql = "SELECT k.ten_kho, COALESCE(SUM(tk.so_luong), 0) AS so_luong
              FROM kho k
              LEFT JOIN ton_kho tk ON k.id = tk.kho_id
              WHERE k.vung_id = $vung_id
              GROUP BY k.ten_kho
              ORDER BY so_luong DESC";
$chart_result = mysqli_query($conn, $chart_sql);

$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_labels[] = $row['ten_kho'];
    $chart_data[] = $row['so_luong'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Quản Lý Vùng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-3">Dashboard Quản Lý Vùng</h2>
        
        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Tổng số kho</h5>
                        <?php
                        $total_warehouses = mysqli_num_rows($result);
                        echo "<p class='card-text display-4'>$total_warehouses</p>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Tổng hàng hóa</h5>
                        <?php
                        $total_products = array_sum($chart_data);
                        echo "<p class='card-text display-4'>$total_products</p>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Kho có nhiều hàng nhất</h5>
                        <?php
                        $max_index = array_search(max($chart_data), $chart_data);
                        $max_warehouse = $chart_labels[$max_index];
                        $max_value = max($chart_data);
                        echo "<p class='card-text'>$max_warehouse: $max_value</p>";
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Biểu đồ -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Biểu đồ số lượng hàng trong các kho</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="warehouseChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Phân bố hàng hóa</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="pieChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danh sách kho -->
        <div class="card">
            <div class="card-header">
                <h5>Danh sách kho hàng trong vùng quản lý</h5>
            </div>
            <div class="card-body">
                <input type="text" id="search" class="form-control mb-3" placeholder="Tìm kiếm kho...">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tên Kho</th>
                                <th>Địa Chỉ</th>
                                <th>Số Lượng Hàng</th>
                                <th>Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody id="khoTable">
                            <?php 
                            mysqli_data_seek($result, 0); // Reset con trỏ kết quả
                            while ($row = mysqli_fetch_assoc($result)) { 
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['ten_kho']; ?></td>
                                <td><?php echo $row['dia_chi']; ?></td>
                                <td><?php echo number_format($row['tong_hang']); ?></td>
                                <td>
                                    <a href="chi_tiet_kho.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Xem</a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Tìm kiếm
    document.getElementById('search').addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#khoTable tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
        });
    });
    
    // Biểu đồ cột
    const ctx = document.getElementById('warehouseChart').getContext('2d');
    const warehouseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Số lượng hàng',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng hàng'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Kho hàng'
                    }
                }
            }
        }
    });
    
    // Biểu đồ tròn
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    </script>
</body>

</html>