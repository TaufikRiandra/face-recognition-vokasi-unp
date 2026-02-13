<?php
// Start output buffering and clear any potential output
ob_start();

// Set Content-Type header BEFORE anything else
header('Content-Type: application/json; charset=utf-8');

// Start session
if(session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include database connection
include 'koneksi.php';

// Check if admin is authenticated
if(!isset($_SESSION['admin_id'])) {
  ob_end_clean();
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized access'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Verify database connection
if(!$conn) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$migration_file = dirname(__FILE__) . '/../database/migration_add_keterangan.sql';

if(!file_exists($migration_file)) {
  ob_end_clean();
  http_response_code(404);
  echo json_encode(['status' => 'error', 'message' => 'Migration file not found at: ' . $migration_file], JSON_UNESCAPED_UNICODE);
  exit;
}

$sql_content = file_get_contents($migration_file);

// Handle line endings consistently
$sql_content = str_replace("\r\n", "\n", $sql_content);
$sql_content = str_replace("\r", "\n", $sql_content);

// Remove comment lines (lines starting with --)
$lines = explode("\n", $sql_content);
$sql_lines = array_filter(array_map(function($line) {
  $line = trim($line);
  // Skip empty lines and SQL comments
  if(empty($line) || strpos($line, '--') === 0) return '';
  return $line;
}, $lines));

$sql_content = implode("\n", $sql_lines);

// Split by semicolon and filter out empty statements and DELIMITER commands
$statements = explode(';', $sql_content);
$statements = array_filter(array_map('trim', $statements));
$statements = array_filter($statements, function($s) { 
  if(empty($s)) return false;
  $upper = strtoupper($s);
  if(strpos($upper, 'DELIMITER') === 0) return false;
  return true;
});

$executed = 0;
$failed = 0;
$errors = [];

foreach($statements as $key => $statement) {
  // Skip if statement is just DELIMITER variations
  $stmt_upper = strtoupper(trim($statement));
  if(strpos($stmt_upper, 'DELIMITER') === 0 || empty($stmt_upper)) {
    continue;
  }
  
  if(!mysqli_query($conn, $statement)) {
    $failed++;
    $error_msg = mysqli_error($conn);
    $errors[] = [
      'statement_num' => $key,
      'statement' => substr($statement, 0, 150),
      'error' => $error_msg
    ];
  } else {
    $executed++;
  }
}

if($failed === 0) {
  ob_end_clean();
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'message' => 'Migration completed successfully',
    'executed' => $executed,
    'failed' => $failed
  ], JSON_UNESCAPED_UNICODE);
} else {
  ob_end_clean();
  http_response_code(200);
  echo json_encode([
    'status' => 'error',
    'message' => 'Migration completed with errors',
    'executed' => $executed,
    'failed' => $failed,
    'errors' => $errors
  ], JSON_UNESCAPED_UNICODE);
}
exit;
