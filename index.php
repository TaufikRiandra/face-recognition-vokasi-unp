<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Jika belum login, arahkan ke halaman login
    header("Location: admin/login.php");
    exit;
} else {
    // Jika sudah login, arahkan ke dashboard
    header("Location: pages/dashboard.php");
    exit;
}
?>
