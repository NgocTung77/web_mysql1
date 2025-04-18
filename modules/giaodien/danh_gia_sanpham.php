<?php
$sql_danhgia = "SELECT reviews.rating, reviews.comment, users.name AS user_name, reviews.created_at
                FROM reviews
                JOIN users ON reviews.user_id = users.id
                WHERE reviews.product_id = '$id_sp' ORDER BY reviews.created_at DESC";
$result_danhgia = mysqli_query($conn, $sql_danhgia);
?>

<!-- Tabs -->
<div class="w3-bar w3-black">
    <button class="w3-bar-item w3-button" onclick="openTab('Mota')">Mô tả sản phẩm</button>
    <button class="w3-bar-item w3-button" onclick="openTab('ThongSo')">Thông số kỹ thuật</button>
    <button class="w3-bar-item w3-button" onclick="openTab('DanhGia')">Đánh giá sản phẩm</button>
</div>

<!-- Nội dung các tab -->
<div id="Mota" class="tab-content active">
    <p><?php echo $row_chitiet['mota']; ?></p>
</div>
<div id="ThongSo" class="tab-content">
    <h3>Thông số kỹ thuật</h3>
    <p><?php echo $row_chitiet['thongsosp'];  ?></p>
</div>

<div id="DanhGia" class="tab-content">
    <h3>Đánh giá sản phẩm</h3>

    <!-- Form đánh giá -->
    <form action="./modules/right/xu_ly_danhgia.php" method="POST">
        <input type="hidden" name="product_id" value="<?php echo $row_chitiet['id_sp']; ?>">
        <label for="rating">Đánh giá (1-5):</label>
        <input type="number" name="rating" min="1" max="5" required><br><br>
        <label for="comment">Bình luận:</label><br>
        <textarea name="comment" rows="5" required></textarea><br><br>
        <input type="submit" value="Gửi Đánh Giá">
    </form>
    
    <h4>Các đánh giá:</h4>
    <?php
    if (mysqli_num_rows($result_danhgia) > 0) {
        while ($row_danhgia = mysqli_fetch_assoc($result_danhgia)) {
            echo '<div class="review">';
            echo '<p><strong>' . $row_danhgia['user_name'] . '</strong> - <em>' . date('d-m-Y H:i', strtotime($row_danhgia['created_at'])) . '</em></p>';
            echo '<p>Đánh giá: ' . str_repeat('★', $row_danhgia['rating']) . str_repeat('☆', 5 - $row_danhgia['rating']) . '</p>';
            echo '<p>' . $row_danhgia['comment'] . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>Chưa có đánh giá nào cho sản phẩm này.</p>';
    }
    ?>
</div>
