<?php
// SET OUTPUT BUFFERING FIRST - BEFORE ANYTHING ELSE
ob_start();

// Set JSON header BEFORE including any files
header('Content-Type: application/json; charset=utf-8');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Start session safely
if(session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Authenticate
if(!isset($_SESSION['admin_id'])) {
  ob_end_clean();
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized access'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Include connection - with error checking
if(!file_exists('koneksi.php')) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection file not found'], JSON_UNESCAPED_UNICODE);
  exit;
}

include 'koneksi.php';

// Verify connection
if(!$conn) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$results = [];

try {
  // Step 1: Check if keterangan column exists
  $check_col = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME='attendance_logs' AND COLUMN_NAME='keterangan' AND TABLE_SCHEMA=DATABASE()";
  $result = mysqli_query($conn, $check_col);
  $col_exists = $result && mysqli_num_rows($result) > 0;
  
  if(!$col_exists) {
    $sql = "ALTER TABLE `attendance_logs` 
            ADD COLUMN `keterangan` VARCHAR(50) DEFAULT 'normal' AFTER `stored_user_nama`";
    if(!mysqli_query($conn, $sql)) {
      throw new Exception("Error adding keterangan column: " . mysqli_error($conn));
    }
    $results[] = "✓ Keterangan column added";
  } else {
    $results[] = "✓ Keterangan column already exists";
  }

  // Step 2: Check if labor columns exist and add them
  $check_labor = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME='labor' AND COLUMN_NAME='jam_masuk_standar' AND TABLE_SCHEMA=DATABASE()";
  $result = mysqli_query($conn, $check_labor);
  $labor_exists = $result && mysqli_num_rows($result) > 0;
  
  if(!$labor_exists) {
    $sql = "ALTER TABLE `labor` 
            ADD COLUMN `jam_masuk_standar` TIME DEFAULT '09:30:00',
            ADD COLUMN `jam_pulang_standar` TIME DEFAULT '18:30:00',
            ADD COLUMN `toleransi_terlambat` INT DEFAULT 1";
    if(!mysqli_query($conn, $sql)) {
      throw new Exception("Error adding labor columns: " . mysqli_error($conn));
    }
    $results[] = "✓ Labor time columns added";
  } else {
    $results[] = "✓ Labor time columns already exist";
  }

  // Step 3: Drop old trigger if exists
  $drop_trigger = "DROP TRIGGER IF EXISTS tr_attendance_keterangan_insert";
  mysqli_query($conn, $drop_trigger); // Ignore errors if trigger doesn't exist
  $results[] = "✓ Old trigger removed";

  // Step 4: Create new trigger
  $trigger_sql = "CREATE TRIGGER tr_attendance_keterangan_insert 
                  BEFORE INSERT ON attendance_logs 
                  FOR EACH ROW
                  BEGIN
                    DECLARE v_jam_standar TIME;
                    DECLARE v_jam_pulang_standar TIME;
                    DECLARE v_toleransi INT;
                    DECLARE v_jam_attendance TIME;
                    
                    SELECT jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
                    INTO v_jam_standar, v_jam_pulang_standar, v_toleransi
                    FROM labor 
                    WHERE id = NEW.labor_id;
                    
                    IF v_jam_standar IS NULL THEN
                      SET v_jam_standar = '09:30:00';
                      SET v_jam_pulang_standar = '18:30:00';
                      SET v_toleransi = 1;
                    END IF;
                    
                    SET v_jam_attendance = TIME(NEW.created_at);
                    
                    IF NEW.status = 'IN' THEN
                      IF v_jam_attendance > ADDTIME(v_jam_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
                        SET NEW.keterangan = 'terlambat';
                      ELSE
                        SET NEW.keterangan = 'tepat waktu';
                      END IF;
                    ELSEIF NEW.status = 'OUT' THEN
                      IF v_jam_attendance > ADDTIME(v_jam_pulang_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
                        SET NEW.keterangan = 'lembur';
                      ELSE
                        SET NEW.keterangan = 'tepat waktu';
                      END IF;
                    END IF;
                  END";
  
  if(!mysqli_query($conn, $trigger_sql)) {
    throw new Exception("Error creating trigger: " . mysqli_error($conn));
  }
  $results[] = "✓ Trigger created successfully";

  // Step 5: Backfill existing data
  $backfill_sql = "UPDATE attendance_logs al
                   JOIN labor l ON al.labor_id = l.id
                   SET al.keterangan = CASE 
                     WHEN al.status = 'IN' AND TIME(al.created_at) > ADDTIME(l.jam_masuk_standar, SEC_TO_TIME(COALESCE(l.toleransi_terlambat, 1) * 60)) THEN 'terlambat'
                     WHEN al.status = 'OUT' AND TIME(al.created_at) > ADDTIME(l.jam_pulang_standar, SEC_TO_TIME(COALESCE(l.toleransi_terlambat, 1) * 60)) THEN 'lembur'
                     ELSE 'tepat waktu'
                   END
                   WHERE al.keterangan IS NULL OR al.keterangan = 'normal'";
  
  if(!mysqli_query($conn, $backfill_sql)) {
    throw new Exception("Error backfilling data: " . mysqli_error($conn));
  }
  
  $affected = mysqli_affected_rows($conn);
  $results[] = "✓ Backfilled $affected existing records with correct keterangan";

  ob_end_clean();
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'message' => 'Migration completed successfully',
    'results' => $results
  ], JSON_UNESCAPED_UNICODE);
  
} catch(Exception $e) {
  ob_end_clean();
  http_response_code(200);
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage(),
    'results' => $results
  ], JSON_UNESCAPED_UNICODE);
}

exit;
