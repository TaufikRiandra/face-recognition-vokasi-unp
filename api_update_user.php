<?php
session_start();
include 'koneksi.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

// Only handle POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
  exit;
}

header('Content-Type: application/json');

// Get data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
$nim = isset($_POST['nim']) ? mysqli_real_escape_string($conn, trim($_POST['nim'])) : '';

// Validate input
if($user_id <= 0 || empty($nama) || empty($nim)) {
  echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
  exit;
}

// Check if user exists
$check_query = mysqli_query($conn, "SELECT id FROM users WHERE id=$user_id LIMIT 1");
if(mysqli_num_rows($check_query) == 0) {
  echo json_encode(['success' => false, 'error' => 'User tidak ditemukan']);
  exit;
}

// Check if NIM already exists (for other users)
$check_nim = mysqli_query($conn, "SELECT id FROM users WHERE nim='$nim' AND id!=$user_id LIMIT 1");
if(mysqli_num_rows($check_nim) > 0) {
  echo json_encode(['success' => false, 'error' => 'NIM sudah digunakan user lain']);
  exit;
}

// Update user
$update_query = mysqli_query($conn, "UPDATE users SET nama='$nama', nim='$nim' WHERE id=$user_id");

if($update_query) {
  echo json_encode(['success' => true, 'message' => 'User berhasil diubah']);
} else {
  echo json_encode(['success' => false, 'error' => 'Gagal mengubah user: ' . mysqli_error($conn)]);
}
?>
