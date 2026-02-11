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

// Get POST data
$nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
$nim = isset($_POST['nim']) ? mysqli_real_escape_string($conn, trim($_POST['nim'])) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';

// Validate input
if(empty($nama) || empty($nim) || empty($email)) {
  echo json_encode(['success' => false, 'error' => 'Semua field harus diisi!']);
  exit;
}

// Validate email format
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'error' => 'Format email tidak valid!']);
  exit;
}

// Check if NIM already exists
$check_nim = mysqli_query($conn, "SELECT id FROM users WHERE nim='$nim' LIMIT 1");
if(mysqli_num_rows($check_nim) > 0) {
  echo json_encode(['success' => false, 'error' => 'NIM sudah terdaftar!']);
  exit;
}

// Check if Email already exists
$check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
if(mysqli_num_rows($check_email) > 0) {
  echo json_encode(['success' => false, 'error' => 'Email sudah terdaftar!']);
  exit;
}

// Insert new user
$insert_q = mysqli_query($conn, "INSERT INTO users (nama, nim, email) VALUES ('$nama', '$nim', '$email')");
if($insert_q) {
  $new_user_id = mysqli_insert_id($conn);
  echo json_encode([
    'success' => true,
    'message' => 'User berhasil ditambahkan!',
    'user_id' => $new_user_id,
    'user_name' => $nama
  ]);
} else {
  echo json_encode(['success' => false, 'error' => 'Gagal menambahkan user: ' . mysqli_error($conn)]);
}
?>
