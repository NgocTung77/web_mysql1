<div class="col-md-6">
    <h5>Đánh giá từ khách hàng</h5>
    <div id="review-list">
        <?php
        try {
            $sql = "SELECT r.rating, r.comment, u.ten_user AS user_name, r.created_at
                    FROM reviews r
                    JOIN user u ON r.id_user = u.id_user
                    WHERE r.id_sp = :id_sp 
                    ORDER BY r.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id_sp' => $product['id_sp']]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($reviews) > 0) {
                foreach ($reviews as $row) {
                    echo '<div class="review-item mb-4">';
                    echo '<div class="d-flex justify-content-between">';
                    echo '<strong>' . htmlspecialchars($row['user_name']) . '</strong>';
                    echo '<div class="text-warning">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                    }
                    echo '</div></div>';
                    echo '<p class="mt-2">' . nl2br(htmlspecialchars($row['comment'])) . '</p>';
                    echo '<small>' . date("d/m/Y H:i", strtotime($row['created_at'])) . '</small>';
                    echo '</div><hr>';
                }
            } else {
                echo '<p>Chưa có đánh giá nào.</p>';
            }
        } catch(PDOException $e) {
            echo '<p>Lỗi khi tải đánh giá.</p>';
        }
        ?>
    </div>
</div>