<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị Đơn Giản</title>
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 70px;
        --primary-color: #5e81ac;
        --secondary-color: #d8dee9;
        --success-color: #88c0d0;
        --danger-color: #bf616a;
        --info-color: #81a1c1;
        --light-color: #eceff4;
        --dark-color: #2e3440;
        --sidebar-bg: var(--dark-color);
        --sidebar-text: rgba(255, 255, 255, 0.95);
        --sidebar-hover: rgba(255, 255, 255, 0.15);
        --sidebar-active: var(--primary-color);
        --transition-speed: 0.3s;
    }

    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(120deg, var(--light-color) 0%, #e5e9f0 100%);
        min-height: 100vh;
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        transition: width var(--transition-speed) ease;
        overflow-x: hidden;
        box-shadow: 0 4px 20px rgba(46, 52, 64, 0.1);
        z-index: 1000;
    }

    .sidebar-collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-header {
        padding: 20px;
        text-align: center;
        background: linear-gradient(135deg, var(--primary-color), #81a1c1);
        border-bottom: 3px solid rgba(255, 255, 255, 0.3);
        position: relative;
    }

    .sidebar-header .logo {
        font-size: 1.8rem;
        font-weight: 600;
        color: white;
        letter-spacing: 1px;
    }

    .sidebar-menu {
        padding: 15px 0;
    }

    .menu-group {
        padding: 8px 20px;
        font-size: 0.9rem;
        color: var(--secondary-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: rgba(255, 255, 255, 0.05);
    }

    .menu-item {
        padding: 12px 20px;
        color: var(--sidebar-text);
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: background-color var(--transition-speed), padding-left var(--transition-speed);
        position: relative;
        cursor: pointer;
    }

    .menu-item:hover {
        background-color: var(--sidebar-hover);
        padding-left: 25px;
    }

    .menu-item.active {
        background-color: var(--sidebar-active);
        color: white;
    }

    .menu-item i {
        width: 24px;
        text-align: center;
        margin-right: 12px;
        font-size: 1.1rem;
    }

    .menu-text {
        flex-grow: 1;
        transition: opacity var(--transition-speed);
    }

    .sidebar-collapsed .menu-text {
        opacity: 0;
        width: 0;
    }

    .sidebar-collapsed .menu-group {
        display: none;
    }

    /* Sub-menu */
    .sub-menu {
        background: rgba(0, 0, 0, 0.2);
        max-height: 0;
        overflow: hidden;
        transition: max-height var(--transition-speed) ease;
    }

    .menu-item.has-submenu.active + .sub-menu,
    .menu-item.has-submenu:hover + .sub-menu {
        max-height: 200px; /* Điều chỉnh tùy theo số lượng mục con */
    }

    .sub-menu .menu-item {
        padding-left: 40px;
        font-size: 0.95rem;
    }

    /* Tooltip khi thu gọn */
    .sidebar-collapsed .menu-item:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(var(--sidebar-collapsed-width) + 10px);
        background: var(--dark-color);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        white-space: nowrap;
        z-index: 1000;
    }

    /* Badge */
    .badge-notification {
        background: var(--danger-color);
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.8rem;
        margin-left: auto;
    }

    .sidebar-collapsed .badge-notification {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
    }

    .main-content {
        margin-left: var(--sidebar-width);
        padding: 20px;
        transition: margin-left var(--transition-speed) ease;
    }

    .main-content-expanded {
        margin-left: var(--sidebar-collapsed-width);
    }

    .toggle-btn {
        position: absolute;
        right: 15px;
        top: 20px;
        background: none;
        border: none;
        color: white;
        font-size: 1.4rem;
        cursor: pointer;
        transition: transform var(--transition-speed);
    }

    .toggle-btn:hover {
        transform: rotate(180deg);
    }

    @media (max-width: 768px) {
        .sidebar {
            left: -100%;
            width: var(--sidebar-width);
        }
        
        .sidebar-collapsed {
            left: 0;
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .main-content-expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="sidebar-header">
            <div class="logo">ADMIN</div>
        </div>
        
        <div class="sidebar-menu">
            <!-- Nhóm: Tổng quan -->
            <div class="menu-group">Tổng quan</div>
            <a href="index.php?quanly=admin" class="menu-item active" data-tooltip="Dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <div class="menu-group">Thống Kê</div>
            <a href="index.php?quanly=loinhuan" class="menu-item has-submenu" data-tooltip="Lợi Luận">
                <i class="fas fa-warehouse"></i>
                <span class="menu-text">Lợi Nhuận</span>
            </a>
            <!-- Nhóm: Quản lý kho -->
            <div class="menu-group">Quản lý kho</div>
            <a href="index.php?quanly=admin&ac=them" class="menu-item has-submenu" data-tooltip="Kho">
                <i class="fas fa-warehouse"></i>
                <span class="menu-text">Kho</span>
            </a>
            <!-- <div class="sub-menu">
                <a href="index.php?quanly=admin&ac=them" class="menu-item" data-tooltip="Thêm kho">
                    <i class="fas fa-plus"></i>
                    <span class="menu-text">Thêm kho</span>
                </a>
            </div> -->

            <a href="index.php?quanly=quanlynhapkho" class="menu-item" data-tooltip="Nhập kho">
                <i class="fas fa-arrow-down"></i>
                <span class="menu-text">Nhập kho</span>
            </a>
            <a href="index.php?quanly=quanlyxuatkho" class="menu-item" data-tooltip="Xuất kho">
                <i class="fas fa-arrow-up"></i>
                <span class="menu-text">Xuất kho</span>
            </a>
            <a href="index.php?quanly=pheduyetnhapkho" class="menu-item" data-tooltip="Phê duyệt NK">
                <i class="fas fa-check-circle"></i>
                <span class="menu-text">Phê duyệt NK</span>
                <!-- <span class="badge-notification"></span> Ví dụ thông báo -->
            </a>
            <a href="index.php?quanly=pheduyetxuatkho" class="menu-item" data-tooltip="Phê duyệt XK">
                <i class="fas fa-check-circle"></i>
                <span class="menu-text">Phê duyệt XK</span>
            </a>

            <!-- Nhóm: Quản lý sản phẩm -->
            <div class="menu-group">Quản lý sản phẩm</div>
            <a href="index.php?quanly=quanlyloaisp" class="menu-item" data-tooltip="Loại sản phẩm">
                <i class="fas fa-boxes"></i>
                <span class="menu-text">Loại sản phẩm</span>
            </a>
            <a href="index.php?quanly=quanlychitietsp" class="menu-item" data-tooltip="Sản phẩm">
                <i class="fas fa-box"></i>
                <span class="menu-text">Sản phẩm</span>
            </a>

            <!-- Nhóm: Quản lý đơn hàng -->
            <div class="menu-group">Quản lý đơn hàng</div>
            <a href="index.php?quanly=quanlydonhang" class="menu-item" data-tooltip="Đơn hàng">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Đơn hàng</span>
                <!-- <span class="badge-notification"></span> Ví dụ thông báo -->
            </a>
            <a href="index.php?quanly=danhsachdonhang" class="menu-item" data-tooltip="Danh sách đơn hàng">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Danh sách đơn hàng</span>
                <!-- <span class="badge-notification"></span> Ví dụ thông báo -->
            </a>

            <!-- Nhóm: Quản lý khách hàng -->
            <div class="menu-group">Quản lý khách hàng</div>
            <a href="index.php?quanly=quanlykhachhang" class="menu-item" data-tooltip="Khách hàng">
                <i class="fas fa-users"></i>
                <span class="menu-text">Khách hàng</span>
            </a>

            <!-- Nhóm: Hệ thống -->
            <div class="menu-group">Hệ thống</div>
            <a href="#" class="menu-item" data-tooltip="Bài viết">
                <i class="fas fa-file-alt"></i>
                <span class="menu-text">Bài viết</span>
            </a>
            <a href="#" class="menu-item" data-tooltip="Cài đặt">
                <i class="fas fa-cog"></i>
                <span class="menu-text">Cài đặt</span>
            </a>
            <a href="http://localhost/web_mysql1/admincp/modules/logout.php" class="menu-item" data-tooltip="Đăng xuất">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-text">Đăng xuất</span>
            </a>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content" id="mainContent">
        <?php include('modules/content.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        document.getElementById('mainContent').classList.toggle('main-content-expanded');
        
        const isCollapsed = document.getElementById('sidebar').classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            document.getElementById('sidebar').classList.add('sidebar-collapsed');
            document.getElementById('mainContent').classList.add('main-content-expanded');
        }
    });
    </script>
</body>
</html>