<?php
session_start();
include 'koneksi.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

header('Content-Type: application/json');

// Get search parameter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// Build where clause
$where_clause = '';
if(!empty($search)) {
  $where_clause = "WHERE nama LIKE '%$search%' OR nim LIKE '%$search%'";
}

// Get users sorted by ID desc (newest first)
$query = "SELECT id, nama, nim, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM users $where_clause ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if(!$result) {
  echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
  exit;
}

$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'users' => $users,
  'total' => count($users)
]);
?>
