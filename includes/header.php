<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Only redirect if accessing protected pages
$publicPages = ['index.php', 'register.php', 'login.php'];
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && !in_array($currentPage, $publicPages)) {
    header('Location: login.php');
    exit();
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<header>
    <nav>
        <div class="nav-brand">
            <a href="dashboard.php" class="logo"><i class="fas fa-chart-line"></i> SalesFlow</a>
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="เปิดเมนู">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> แดชบอร์ด</a></li>
            <li><a href="customers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> ลูกค้า</a></li>
            <li><a href="deals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'deals.php' ? 'active' : '' ?>"><i class="fas fa-handshake"></i> ดีล</a></li>
            <li><a href="activities.php" class="<?= basename($_SERVER['PHP_SELF']) == 'activities.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> กิจกรรม</a></li>
            <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> สินค้า</a></li>
            <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> คำสั่งซื้อ</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> จัดการผู้ใช้</a></li>
            <?php endif; ?>
            <li><a href="#" class="btn-primary" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
            <script>
                function logout() {
                    fetch('api/auth/logout', { method: 'POST' })
                        .then(() => window.location.href = 'index.php');
                }
            </script>
        </ul>
    </nav>
    <div class="nav-overlay" id="navOverlay" aria-hidden="true"></div>
</header>
<script>
(function() {
    var btn = document.getElementById('hamburgerBtn');
    var nav = document.getElementById('navLinks');
    var overlay = document.getElementById('navOverlay');
    if (btn && nav) {
        function toggleMenu() {
            nav.classList.toggle('open');
            overlay.classList.toggle('show');
            document.body.classList.toggle('menu-open');
            btn.setAttribute('aria-label', nav.classList.contains('open') ? 'ปิดเมนู' : 'เปิดเมนู');
            btn.querySelector('i').className = nav.classList.contains('open') ? 'fas fa-times' : 'fas fa-bars';
        }
        function closeMenu() {
            nav.classList.remove('open');
            overlay.classList.remove('show');
            document.body.classList.remove('menu-open');
            if (btn) {
                btn.setAttribute('aria-label', 'เปิดเมนู');
                btn.querySelector('i').className = 'fas fa-bars';
            }
        }
        btn.addEventListener('click', toggleMenu);
        if (overlay) overlay.addEventListener('click', closeMenu);
        nav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', closeMenu);
        });
    }
})();
</script>

