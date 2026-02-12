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

// Get user ID
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if($user_id <= 0) {
  echo json_encode(['success' => false, 'error' => 'User ID tidak valid']);
  exit;
}

// Check if user exists
$check_query = mysqli_query($conn, "SELECT id FROM users WHERE id=$user_id LIMIT 1");
if(mysqli_num_rows($check_query) == 0) {
  echo json_encode(['success' => false, 'error' => 'User tidak ditemukan']);
  exit;
}

// Delete user
$delete_query = mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");

if($delete_query) {
  echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
} else {
  echo json_encode(['success' => false, 'error' => 'Gagal menghapus user: ' . mysqli_error($conn)]);
}
?>
