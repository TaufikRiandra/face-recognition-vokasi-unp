<?php
session_start();
include '../../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$inputDescriptor = $input['descriptor'] ?? null;

if(!$inputDescriptor) {
  echo json_encode(['status' => 'error', 'message' => 'No descriptor provided']);
  exit;
}

// Ambil semua face embeddings dari database
$query = mysqli_query($conn, "
  SELECT 
    fe.id,
    fe.user_id,
    fe.embedding_index,
    fe.embedding,
    u.nama as user_name
  FROM face_embeddings fe
  LEFT JOIN users u ON fe.user_id = u.id
  ORDER BY fe.user_id, fe.embedding_index
");

$minDistance = 0.4; // Ambang batas kesamaan
$bestMatch = null;
$bestDistance = 999;
$bestEmbeddingIndex = null;
$userMatches = []; // Track all embeddings per user untuk comparison

while($row = mysqli_fetch_assoc($query)) {
  $dbDescriptor = json_decode($row['embedding']);
  
  if(!is_array($dbDescriptor) || count($dbDescriptor) === 0) {
    continue;
  }
  
  // Hitung jarak Euclidean
  $sum = 0;
  for($i = 0; $i < count($inputDescriptor); $i++) {
    if(isset($dbDescriptor[$i])) {
      $sum += pow($inputDescriptor[$i] - $dbDescriptor[$i], 2);
    }
  }
  $distance = sqrt($sum);
  
  // Track untuk setiap user (multiple embeddings)
  $user_id = $row['user_id'];
  if(!isset($userMatches[$user_id])) {
    $userMatches[$user_id] = [
      'user_name' => $row['user_name'],
      'user_id' => $row['user_id'],
      'matches' => []
    ];
  }
  
  $userMatches[$user_id]['matches'][] = [
    'embedding_index' => $row['embedding_index'],
    'distance' => $distance
  ];
  
  // Simpan match terbaik secara global
  if($distance < $bestDistance) {
    $bestDistance = $distance;
    $bestMatch = $row;
    $bestEmbeddingIndex = $row['embedding_index'];
  }
}

// Tentukan hasil dengan multiple embeddings comparison
if($bestDistance < $minDistance) {
  // Cocok ditemukan
  $name = $bestMatch['user_name'] ?? 'Unknown';
  $confidence = 1 - ($bestDistance / $minDistance); // Normalisasi confidence
  $confidence = max(0, min(1, $confidence)); // Clamp between 0-1
  
  // Ambil info matches untuk user yang cocok untuk debugging/logging
  $best_user_id = $bestMatch['user_id'];
  $user_embeddings_info = [];
  if(isset($userMatches[$best_user_id])) {
    $user_embeddings_info = $userMatches[$best_user_id]['matches'];
  }
  
  // Cek last status untuk user/guest yang cocok (untuk enforce IN/OUT logic)
  $allowedStatus = null;
  $today = date('Y-m-d');
  
  if($best_user_id) {
    // Untuk mahasiswa terdaftar
    $last_status_query = mysqli_query($conn, "
      SELECT status 
      FROM attendance_logs 
      WHERE user_id = $best_user_id AND DATE(created_at) = '$today'
      ORDER BY created_at DESC 
      LIMIT 1
    ");
    
    $lastStatus = null;
    if($last_status_query && mysqli_num_rows($last_status_query) > 0) {
      $status_row = mysqli_fetch_assoc($last_status_query);
      $lastStatus = $status_row['status'];
    }
    
    // Tentukan status yang diizinkan
    if($lastStatus === 'IN') {
      $allowedStatus = 'OUT'; // Harus OUT jika sudah IN
    } else if($lastStatus === 'OUT' || $lastStatus === null) {
      $allowedStatus = 'IN'; // Bisa IN jika sudah OUT atau belum pernah
    }
  }
  
  echo json_encode([
    'status' => 'found',
    'name' => $name,
    'confidence' => $confidence,
    'distance' => $bestDistance,
    'user_id' => $bestMatch['user_id'],
    'matched_embedding_index' => $bestEmbeddingIndex,
    'allowedStatus' => $allowedStatus,  // Status yang diizinkan (IN atau OUT)
    'embeddings_info' => $user_embeddings_info // Info semua embeddings user untuk ref
  ]);
} else {
  // Tidak ada kecocokan
  echo json_encode([
    'status' => 'unknown',
    'message' => 'No matching face found',
    'bestDistance' => $bestDistance
  ]);
}
?>
