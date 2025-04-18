<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-row">
            <div class="footer-col">
                <h5 class="footer-title">VỀ CHÚNG TÔI</h5>
                <p class="footer-text">Shop phụ kiện điện thoại uy tín hàng đầu Việt Nam</p>
                <div class="footer-logo">N</div>
            </div>
            <div class="footer-col">
                <h5 class="footer-title">LIÊN HỆ</h5>
                <div class="footer-contact">
                    <p><i class="fas fa-map-marker-alt footer-icon"></i> 123 Đường ABC, Quận 1, TP.HCM</p>
                    <p><i class="fas fa-phone footer-icon"></i> 0901.234.567</p>
                    <p><i class="fas fa-envelope footer-icon"></i> info@shopphukien.com</p>
                    <p><i class="fas fa-clock footer-icon"></i> Mở cửa: 8:00 - 21:00 (T2 - CN)</p>
                </div>
            </div>
            <div class="footer-col">
                <h5 class="footer-title">HỖ TRỢ KHÁCH HÀNG</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-chevron-right footer-link-icon"></i> Chính sách bảo hành</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right footer-link-icon"></i> Hướng dẫn mua hàng</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right footer-link-icon"></i> Chính sách đổi trả</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right footer-link-icon"></i> Câu hỏi thường gặp</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5 class="footer-title">KẾT NỐI VỚI CHÚNG TÔI</h5>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="footer-newsletter">
                    <p>Đăng ký nhận khuyến mãi</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Nhập email của bạn" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <hr class="footer-divider">
            <div class="footer-copyright">
                <p>© 2023 <strong>Shop Phụ Kiện Điện Thoại</strong>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Sử dụng biến màu từ menu */
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #1cd2ae;
        --text-color: #2c3e50;
        --light-bg: #f8f9fa;
        --dark-bg: #42586e;
    }

    .footer {
        background-color: white;
        color: var(--text-color);
        padding: 3rem 0 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.1);
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .footer-col {
        flex: 1;
        min-width: 250px;
        padding: 0 15px;
        margin-bottom: 30px;
    }

    .footer-title {
        color: var(--primary-color);
        font-size: 1.2rem;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
        font-weight: 600;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 2px;
        background-color: var(--accent-color);
    }

    .footer-text {
        color: var(--text-color);
        line-height: 1.6;
        margin-bottom: 15px;
        opacity: 0.8;
    }

    .footer-logo {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        text-transform: uppercase;
        margin-top: 10px;
    }

    .footer-contact p {
        display: flex;
        align-items: center;
        color: var(--text-color);
        margin-bottom: 12px;
        line-height: 1.5;
        opacity: 0.8;
    }

    .footer-icon {
        color: var(--primary-color);
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: var(--text-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        opacity: 0.8;
    }

    .footer-links a:hover {
        color: var(--primary-color);
        opacity: 1;
        padding-left: 5px;
    }

    .footer-link-icon {
        color: var(--accent-color);
        margin-right: 8px;
        font-size: 0.8rem;
    }

    .footer-social {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: white;
        background-color: var(--primary-color);
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background-color: var(--secondary-color);
        transform: translateY(-3px);
    }

    .footer-newsletter p {
        color: var(--text-color);
        margin-bottom: 10px;
        opacity: 0.8;
    }

    .newsletter-form {
        display: flex;
    }

    .newsletter-form input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px 0 0 6px;
        outline: none;
    }

    .newsletter-form button {
        background-color: var(--accent-color);
        color: white;
        border: none;
        padding: 0 15px;
        border-radius: 0 6px 6px 0;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .newsletter-form button:hover {
        background-color: #17c2a1;
    }

    .footer-divider {
        border: none;
        height: 1px;
        background-color: rgba(0, 0, 0, 0.1);
        margin: 20px 0;
    }

    .footer-bottom {
        padding-bottom: 20px;
        text-align: center;
    }

    .footer-copyright {
        color: var(--text-color);
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .footer-copyright strong {
        color: var(--primary-color);
    }

    @media (max-width: 768px) {
        .footer-col {
            flex: 100%;
            text-align: center;
        }
        
        .footer-title::after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .footer-contact p {
            justify-content: center;
        }
        
        .footer-social {
            justify-content: center;
        }
        
        .footer-links a {
            justify-content: center;
        }
    }
</style>