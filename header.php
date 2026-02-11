<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])){
  header("Location: admin/login.php");
  exit;
}

include 'koneksi.php';

$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);

if(!$a){
  die("Admin tidak ditemukan");
}
?>

<!-- Admin Header HTML -->
<div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
  <div style="display: flex; align-items: center; gap: 15px;">
    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-size: 24px; color: white;">
      <i class="fas fa-user"></i>
    </div>
    <div>
      <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Selamat Datang, <?= htmlspecialchars($a['username']) ?></h3>
      <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">Admin - Sistem Absensi Labor</p>
    </div>
  </div>
  <div style="display: flex; gap: 10px;">
    <a href="admin/logout.php" style="background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='white'; this.style.color='#f59e0b';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.color='white';">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>
