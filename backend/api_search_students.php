<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

// Support both GET and POST
$query = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  $query = $input['query'] ?? $_POST['query'] ?? '';
} else {
  $query = $_GET['query'] ?? '';
}

// Trim dan validasi query
$query = trim($query);

if(strlen($query) < 1) {
  echo json_encode(['success' => false, 'message' => 'Query terlalu pendek']);
  exit;
}

// Escape query untuk safety
$query = mysqli_real_escape_string($conn, $query);

// Search students berdasarkan nama atau NIM
$sql = "
  SELECT 
    id, 
    nama, 
    nim
  FROM users 
  WHERE role = 'mahasiswa' 
    AND (nama LIKE '%$query%' OR nim LIKE '%$query%')
  ORDER BY nama ASC
  LIMIT 10
";

$result = mysqli_query($conn, $sql);

if(!$result) {
  echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
  exit;
}

$students = [];
while($row = mysqli_fetch_assoc($result)) {
  $students[] = [
    'id' => $row['id'],
    'nama' => $row['nama'],
    'nim' => $row['nim']
  ];
}

echo json_encode([
  'success' => true,
  'students' => $students,
  'count' => count($students)
]);
?>
