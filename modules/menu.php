<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh điều hướng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #1cd2ae;
        --text-color: #2c3e50;
        --light-bg: #f8f9fa;
        --dark-bg: #42586e;
    }
    
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
        justify-content: center;
        align-items: center;
    }
    
    .modal-content {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 500px;
        padding: 30px;
        position: relative;
        animation: modalopen 0.3s;
    }
    
    @keyframes modalopen {
        from {opacity: 0; transform: translateY(-50px);}
        to {opacity: 1; transform: translateY(0);}
    }
    
    .close-btn {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 24px;
        color: #aaa;
        cursor: pointer;
    }
    
    .close-btn:hover {
        color: #333;
    }
    
    /* Form styles */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 500;
    }
    
    .form-group label.required:after {
        content: " *";
        color: red;
    }
    
    .input-with-icon {
        position: relative;
    }
    
    .input-with-icon input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .input-with-icon input:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color);
    }
    
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #777;
    }
    
    .form-submit-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 20px;
        width: 100%;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
        font-weight: 600;
        margin-top: 10px;
    }
    
    .form-submit-btn:hover {
        background-color: var(--secondary-color);
    }
    
    .form-footer {
        text-align: center;
        margin-top: 20px;
        color: #555;
    }
    
    .form-footer a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .form-footer a:hover {
        text-decoration: underline;
    }
    
    /* Navbar styles */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        background-color: white;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 1000;
    }
    
    .logo {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        text-transform: uppercase;
    }
    
    .menu {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .menu ul {
        display: flex;
        list-style: none;
        gap: 1.5rem;
        align-items: center;
    }
    
    .menu li {
        position: relative;
    }
    
    .menu a {
        text-decoration: none;
        color: var(--text-color);
        font-weight: 500;
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .menu a:hover {
        color: var(--primary-color);
    }
    
    .search-form {
        display: flex;
        align-items: center;
        background-color: var(--light-bg);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        transition: all 0.3s ease;
        width: 250px;
    }
    
    .search-form:hover {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    #searchInput {
        border: none;
        background: transparent;
        padding: 0.5rem;
        width: 100%;
        outline: none;
        color: var(--text-color);
    }
    
    #searchInput::placeholder {
        color: #888;
        font-weight: 400;
    }
    
    #sreachIcon {
        color: var(--primary-color);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    #sreachIcon:hover {
        transform: scale(1.1);
    }
    
    .submenu {
        position: absolute;
        top: 100%;
        left: 0;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 0.5rem 0;
        min-width: 180px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 100;
    }
    
    .menu li:hover .submenu {
        opacity: 1;
        visibility: visible;
        transform: translateY(5px);
    }
    
    .submenu ul {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    
    .submenu li {
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }
    
    .submenu li:hover {
        background-color: var(--light-bg);
    }
    
    .submenu a {
        color: var(--text-color);
        font-size: 0.9rem;
    }
    
    .user-menu {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        position: relative;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .user-menu:hover {
        background-color: var(--light-bg);
    }
    
    .user-menu span {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        font-weight: 600;
        cursor: pointer;
    }
    
    #auth-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 0.5rem 0;
        min-width: 200px;
        display: none;
        z-index: 100;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transform: scale(0.95);
        opacity: 0;
        transition: all 0.2s ease;
    }
    
    #auth-menu.show {
        display: block;
        transform: scale(1);
        opacity: 1;
    }
    
    .auth-menu-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem 1.2rem;
        color: var(--text-color);
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    
    .auth-menu-item:hover {
        background-color: var(--light-bg);
        color: var(--primary-color);
        padding-left: 1.2rem;
    }
    
    #settings-icon {
        color: var(--text-color);
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0.5rem;
        border-radius: 50%;
    }
    
    #settings-icon:hover {
        background-color: var(--light-bg);
        color: var(--primary-color);
        transform: rotate(90deg);
    }
    
    .auth-buttons {
        display: flex;
        gap: 0.8rem;
        align-items: center;
    }
    
    .auth-button {
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .register-button {
        background-color: var(--accent-color);
        color: white;
        border: 2px solid var(--accent-color);
    }
    
    .register-button:hover {
        background-color: #17c2a1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(28, 210, 174, 0.3);
    }
    
    .login-button {
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        background-color: white;
    }
    
    .login-button:hover {
        background-color: var(--primary-color);
        color: white;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
    
    .auth-button:active {
        transform: translateY(0);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }
    
    .auth-button i {
        font-size: 0.9rem;
    }
    
    /* Responsive Design */
    
    /* Tablets (max-width: 1024px) */
    @media (max-width: 1024px) {
        .navbar {
            padding: 1rem;
        }
        
        .logo {
            font-size: 2rem;
        }
        
        .menu ul {
            gap: 1rem;
        }
        
        .search-form {
            width: 200px;
        }
        
        .auth-buttons {
            gap: 0.5rem;
        }
        
        .auth-button {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    }
    
    /* Small Tablets and Mobiles (max-width: 768px) */
    @media (max-width: 768px) {
        .navbar {
            flex-wrap: wrap;
            padding: 0.8rem;
            position: relative;
        }
        
        .logo {
            font-size: 1.8rem;
            margin-right: 1rem;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-right: 1rem;
        }

        .main-nav a {
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .main-nav a:hover {
            color: var(--primary-color);
        }
        
        .menu-toggle {
            display: block;
            position: relative;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
        }
        
        .menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 280px;
            background-color: white;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            z-index: 1000;
        }
        
        .menu.active {
            display: block;
        }

        .menu ul {
            flex-direction: column;
            width: 100%;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .menu li {
            width: 100%;
        }

        .menu li a {
            padding: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            text-decoration: none;
        }

        /* Hide desktop elements on mobile */
        .menu li:not(.user-menu):not(.auth-buttons) {
            display: none !important;
        }

        .menu .user-menu {
            padding: 0;
            margin: 0;
        }

        .menu #auth-menu {
            position: static;
            display: block;
            box-shadow: none;
            padding: 0;
            margin-top: 0.5rem;
            opacity: 1;
            transform: none;
            min-width: 100%;
        }

        .auth-menu-item {
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            background-color: var(--light-bg);
            margin: 0.3rem 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .auth-menu-item:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-0.2rem);
        }

        .auth-menu-item i {
            width: 20px;
            text-align: center;
        }

        .search-form {
            width: auto;
            flex: 1;
            margin: 0 1rem;
            order: 0;
        }

        /* Show auth buttons in mobile menu */
        .auth-buttons {
            display: flex !important;
            flex-direction: column;
            width: 100%;
            gap: 0.5rem;
        }

        .auth-buttons .auth-button {
            width: 100%;
            justify-content: flex-start;
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            background-color: var(--light-bg);
            color: var(--text-color);
            border: none;
            box-shadow: none;
        }

        .auth-buttons .auth-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-0.2rem);
        }

        /* Always show auth menu on mobile */
        #auth-menu {
            display: block !important;
            opacity: 1 !important;
        }

        /* Hide user info on mobile */
        .user-menu .user-avatar,
        .user-menu span,
        .user-menu #settings-icon {
            display: none;
        }
    }
    
    /* Mobiles (max-width: 480px) */
    @media (max-width: 480px) {
        .navbar {
            padding: 0.8rem;
        }
        
        .logo {
            font-size: 1.8rem;
        }
        
        .menu a {
            font-size: 0.9rem;
        }
        
        .search-form {
            margin: 0.5rem 0;
        }
        
        .auth-button {
            font-size: 0.85rem;
        }
        
        .user-menu span {
            font-size: 0.9rem;
        }
        
        .auth-menu-item {
            font-size: 0.85rem;
        }
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
        display: none;
        cursor: pointer;
        padding: 0.5rem;
        font-size: 1.5rem;
        color: var(--primary-color);
    }

    /* Desktop styles */
    @media (min-width: 769px) {
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-toggle {
            display: none !important;
        }

        .main-nav {
            display: none !important;
        }

        .menu {
            display: flex !important;
        }

        .menu ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }

        .secondary-menu {
            display: none !important;
        }
    }

    /* Improved Search Bar */
    .search-form {
        position: relative;
        background-color: var(--light-bg);
        border-radius: 2rem;
        transition: all 0.3s ease;
        width: 300px;
        margin: 0 1rem;
    }

    .search-form:focus-within {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    #searchInput {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 2.5rem;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        color: var(--text-color);
    }

    #searchInput::placeholder {
        color: #888;
    }

    #sreachIcon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color);
        font-size: 1rem;
    }

    @media (max-width: 768px) {
        .search-form {
            width: 100%;
            margin: 0.5rem 0;
            order: -1;
        }
        
        #searchInput {
            padding: 0.8rem 1rem 0.8rem 2.5rem;
        }
    }
</style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">N</div>
        
        <!-- Main nav for mobile -->
        <div class="main-nav">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="index.php?xem=lienhe&id=1"><i class="fas fa-envelope"></i> Liên hệ</a>
        </div>

        <div class="menu" id="mainMenu">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="index.php?xem=lienhe&id=1"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                <li>
                    <form class="search-form" id="searchForm">
                        <input type="text" placeholder="Tìm kiếm sản phẩm..." id="searchInput" oninput="searchProducts()">
                        <i id="sreachIcon" class="fas fa-search"></i>
                    </form>
                </li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="user-menu">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        <i class="fas fa-cog" id="settings-icon"></i>
                        
                        <div id="auth-menu">
                            <a href="index.php?xem=donhangnguoidung&id=1" class="auth-menu-item">
                                <i class="fas fa-receipt"></i> Đơn hàng
                            </a>
                            <a href="index.php?xem=giaodien_giohang&id=1" class="auth-menu-item">
                                <i class="fas fa-shopping-cart"></i> Giỏ hàng
                            </a>
                            <a href="index.php?xem=doimatkhau&id=1" class="auth-menu-item">
                                <i class="fas fa-key"></i> Đổi mật khẩu
                            </a>
                            <a href="./modules/giaodien/logout.php" class="auth-menu-item">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="auth-buttons">
                        <a href="#" id="openRegisterModal" class="auth-button register-button">
                            <i class="fas fa-user-plus"></i> Đăng ký
                        </a>
                        <a href="#" id="openLoginModal" class="auth-button login-button">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Toggle button for mobile -->
        <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </nav>

    <!-- Modal đăng ký -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Đăng ký tài khoản</h2>
        <form method="POST" action="modules/giaodien/xuly_dangky.php">
            <div class="form-group">
                <label class="required">Tên người dùng</label>
                <div class="input-with-icon">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="ten_user" placeholder="Nhập tên người dùng" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="required">Email</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Nhập địa chỉ email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="required">Mật khẩu</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="registerPassword" placeholder="Nhập mật khẩu" required>
                    <i class="fas fa-eye toggle-password" id="toggleRegisterPassword"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label>Địa chỉ</label>
                <div class="input-with-icon">
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input type="text" name="dia_chi" placeholder="Nhập địa chỉ">
                </div>
            </div>
            
            <div class="form-group">
                <label>Số điện thoại</label>
                <div class="input-with-icon">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="tel" name="sodienthoai" placeholder="Nhập số điện thoại">
                </div>
            </div>
            
            <button type="submit" class="form-submit-btn">Đăng ký</button>
            
            <div class="form-footer">
                Đã có tài khoản? <a href="#" id="switchToLogin">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
</div>

    <!-- Modal đăng nhập -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Đăng Nhập</h2>
            <form method="POST" action="modules/giaodien/xuly_login.php">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="Nhập email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                <button type="submit" class="form-submit-btn">Đăng Nhập</button>
            </form>
            <div class="form-footer">
                Chưa có tài khoản? <a href="#" id="switchToRegister">Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle menu on mobile
        const menuToggle = document.getElementById('menuToggle');
        const mainMenu = document.getElementById('mainMenu');
        
        menuToggle.addEventListener('click', function() {
            mainMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.navbar') && mainMenu.classList.contains('active')) {
                mainMenu.classList.remove('active');
            }
        });
        
        // Hiển thị/ẩn menu người dùng
        document.getElementById('settings-icon')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('auth-menu');
            menu.classList.toggle('show');
        });
        
        // Đóng menu khi click ra ngoài
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('auth-menu');
            if (menu && !e.target.closest('#auth-menu') && !e.target.closest('.user-menu')) {
                menu.classList.remove('show');
            }
        });
        
        // Tìm kiếm sản phẩm
        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value;
            // Thực hiện tìm kiếm AJAX ở đây
            console.log('Searching for:', searchTerm);
        }
        
        // Hiệu ứng khi nhấn Enter trong ô tìm kiếm
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Thực hiện tìm kiếm
                searchProducts();
            }
        });
        
        // Xử lý modal đăng ký
        const registerModal = document.getElementById('registerModal');
        const openRegisterBtn = document.getElementById('openRegisterModal');
        const closeRegisterBtn = registerModal.querySelector('.close-btn');
        
        // Xử lý modal đăng nhập
        const loginModal = document.getElementById('loginModal');
        const openLoginBtn = document.getElementById('openLoginModal');
        const closeLoginBtn = loginModal.querySelector('.close-btn');
        
        // Chuyển đổi giữa đăng nhập và đăng ký
        const switchToLogin = document.getElementById('switchToLogin');
        const switchToRegister = document.getElementById('switchToRegister');
        
        // Mở modal đăng ký
        if (openRegisterBtn) {
            openRegisterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                registerModal.style.display = 'flex';
            });
        }
        
        // Mở modal đăng nhập
        if (openLoginBtn) {
            openLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loginModal.style.display = 'flex';
            });
        }
        
        // Đóng modal đăng ký
        if (closeRegisterBtn) {
            closeRegisterBtn.addEventListener('click', function() {
                registerModal.style.display = 'none';
            });
        }
        
        // Đóng modal đăng nhập
        if (closeLoginBtn) {
            closeLoginBtn.addEventListener('click', function() {
                loginModal.style.display = 'none';
            });
        }
        
        // Chuyển từ đăng ký sang đăng nhập
        if (switchToLogin) {
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                registerModal.style.display = 'none';
                loginModal.style.display = 'flex';
            });
        }
        
        // Chuyển từ đăng nhập sang đăng ký
        if (switchToRegister) {
            switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                loginModal.style.display = 'none';
                registerModal.style.display = 'flex';
            });
        }
        
        // Đóng modal khi click bên ngoài
        window.addEventListener('click', function(event) {
            if (event.target == registerModal) {
                registerModal.style.display = 'none';
            }
            if (event.target == loginModal) {
                loginModal.style.display = 'none';
            }
        });
        
        // Hiển thị/ẩn mật khẩu
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
        // Hiển thị/ẩn mật khẩu đăng ký
document.getElementById('toggleRegisterPassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('registerPassword');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        this.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
    </script>
</body>
</html>