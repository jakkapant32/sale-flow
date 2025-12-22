<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<header>
    <nav>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="logo"><i class="fas fa-chart-line"></i> SalesFlow</a>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> แดชบอร์ด</a></li>
            <li><a href="customers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> ลูกค้า</a></li>
            <li><a href="deals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'deals.php' ? 'active' : '' ?>"><i class="fas fa-handshake"></i> ดีล</a></li>
            <li><a href="activities.php" class="<?= basename($_SERVER['PHP_SELF']) == 'activities.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> กิจกรรม</a></li>
            <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> สินค้า</a></li>
            <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> คำสั่งซื้อ</a></li>
            <li><a href="#" class="btn-primary" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
            <script>
                function logout() {
                    fetch('api/auth/logout', { method: 'POST' })
                        .then(() => window.location.href = 'index.php');
                }
            </script>
        </ul>
    </nav>
</header>

