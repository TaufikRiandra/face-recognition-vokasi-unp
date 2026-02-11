<?php
session_start();
include '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);

if($user_id < 1) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
  exit;
}

// Get user data
$query = "SELECT id, nama, nim, email, role FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) === 0) {
  echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
  exit;
}

$user = mysqli_fetch_assoc($result);

// Get latest attendance status for TODAY (untuk enforce IN/OUT per hari)
$today = date('Y-m-d');
$status_query = "
  SELECT status, created_at, labor_id 
  FROM attendance_logs 
  WHERE user_id = $user_id AND DATE(created_at) = '$today'
  ORDER BY created_at DESC 
  LIMIT 1
";
$status_result = mysqli_query($conn, $status_query);
$lastStatus = null;
$lastLaborId = null;
$lastAttendanceTime = null;

if($status_result && mysqli_num_rows($status_result) > 0) {
  $status_row = mysqli_fetch_assoc($status_result);
  $lastStatus = $status_row['status']; // 'IN' atau 'OUT'
  $lastLaborId = $status_row['labor_id'];
  $lastAttendanceTime = $status_row['created_at'];
}

// Tentukan status yang diizinkan untuk absensi berikutnya
$allowedStatus = null;
if($lastStatus === 'IN') {
  // Jika sudah IN, harus OUT dulu
  $allowedStatus = 'OUT';
} else if($lastStatus === 'OUT' || $lastStatus === null) {
  // Jika sudah OUT atau belum pernah absen hari ini, bisa IN
  $allowedStatus = 'IN';
}

echo json_encode([
  'status' => 'success',
  'user' => [
    'id' => $user['id'],
    'nama' => $user['nama'],
    'nim' => $user['nim'],
    'email' => $user['email'],
    'role' => $user['role'],
    'lastStatus' => $lastStatus,
    'lastLaborId' => $lastLaborId,
    'lastAttendanceTime' => $lastAttendanceTime,
    'allowedStatus' => $allowedStatus  // Status yang diizinkan untuk absensi berikutnya
  ]
]);
?>
