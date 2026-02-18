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
