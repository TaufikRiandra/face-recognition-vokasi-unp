<?php
session_start();
include 'koneksi.php';
include 'helpers_attendance.php';

header('Content-Type: application/json');

// DEBUG: Log ke file terpisah di root project
$log_file = __DIR__ . '/../attendance_debug.log';
$log_handle = fopen($log_file, 'a');
if ($log_handle) {
    $log_entry = "[" . date('Y-m-d H:i:s') . "] " . getmypid() . " ";
    fwrite($log_handle, "\n" . str_repeat("=", 100) . "\n");
    fwrite($log_handle, $log_entry . "=== ATTENDANCE REQUEST ===\n");
    fwrite($log_handle, $log_entry . "Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "\n");
    fwrite($log_handle, $log_entry . "POST data: " . json_encode($_POST) . "\n");
    fwrite($log_handle, $log_entry . "Server time: " . date('Y-m-d H:i:s') . " (TZ: " . date_default_timezone_get() . ")\n");
    fwrite($log_handle, $log_entry . "DB Connection: " . ($conn ? "OK" : "FAILED") . "\n");
    
    error_log("=== ATTENDANCE REQUEST ===");
    error_log("Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET'));
    error_log("All POST data: " . json_encode($_POST));
    error_log("Server time: " . date('Y-m-d H:i:s') . " (Timezone: " . date_default_timezone_get() . ")");
    error_log("DB Connection: " . ($conn ? "OK" : "FAILED"));
} else {
    error_log("WARNING: Could not open log file: $log_file");
}

// Helper untuk write ke file log
function writeToLog($message) {
    global $log_handle;
    error_log($message);
    if ($log_handle) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] " . getmypid() . " ";
        fwrite($log_handle, $log_entry . $message . "\n");
        fflush($log_handle);
    }
}

if(!isset($_SESSION['admin_id'])) {
  if ($log_handle) {
      $log_entry = "[" . date('Y-m-d H:i:s') . "] " . getmypid() . " ";
      fwrite($log_handle, $log_entry . "ERROR: Session not authorized\n");
      fclose($log_handle);
  }
  error_log("ERROR: Session not authorized");
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($log_handle) {
    fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] " . getmypid() . " Action: $action (POST: " . ($_POST['action'] ?? 'null') . ", GET: " . ($_GET['action'] ?? 'null') . ")\n");
}
error_log("Action: $action (from POST: " . ($_POST['action'] ?? 'null') . ", GET: " . ($_GET['action'] ?? 'null') . ")");

if($action === 'save_embedding_multiple') {
  // Simpan multiple face embeddings (5 embedding terpisah per user)
  $user_id = intval($_POST['user_id'] ?? 0);
  $embeddings = json_decode($_POST['embeddings'] ?? '[]', true);
  $embedding_count = intval($_POST['embedding_count'] ?? 0);

  if(!$user_id || !is_array($embeddings) || count($embeddings) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
  }

  // Check if user exists
  $user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id");
  if(mysqli_num_rows($user_check) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mahasiswa tidak ditemukan']);
    exit;
  }

  // Hapus embedding lama user (opsional, atau bisa disimpan untuk versi lama)
  // mysqli_query($conn, "DELETE FROM face_embeddings WHERE user_id = $user_id");

  $success_count = 0;
  $error_msg = '';

  // Simpan setiap embedding dengan embedding_index (1-5)
  foreach($embeddings as $index => $embedding) {
    $embedding_index = $index + 1; // 1-based index
    $embedding_json = json_encode($embedding);
    $embedding_escaped = mysqli_real_escape_string($conn, $embedding_json);
    
    $insert_query = "INSERT INTO face_embeddings (user_id, embedding_index, embedding, created_at)
                     VALUES ($user_id, $embedding_index, '$embedding_escaped', NOW())";
    
    if(mysqli_query($conn, $insert_query)) {
      $success_count++;
    } else {
      $error_msg .= "Embedding $embedding_index gagal. ";
    }
  }

  if($success_count === count($embeddings)) {
    // Semua embedding berhasil disimpan - TIDAK simpan attendance (hanya saat scan ulang)
    echo json_encode([
      'status' => 'success',
      'message' => "Semua $embedding_count embedding berhasil disimpan. Silakan scan kembali untuk absensi masuk.",
      'embedding_count' => $success_count
    ]);
  } else {
    echo json_encode([
      'status' => 'error',
      'message' => "Hanya $success_count dari $embedding_count embedding yang berhasil disimpan. $error_msg"
    ]);
  }
}

else if($action === 'save_embedding') {
  // Simpan face embedding untuk mahasiswa terdaftar
  $user_id = intval($_POST['user_id'] ?? 0);
  $embedding = $_POST['embedding'] ?? '';
  $status = $_POST['status'] ?? 'IN';
  $labor_id = intval($_POST['labor_id'] ?? 0);
  $confidence = floatval($_POST['confidence'] ?? 1.0);

  if(!$user_id || !$embedding || !$labor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
  }

  // VALIDASI WAKTU SERVER-SIDE (tidak bisa dimanipulasi dari client)
  // Cek apakah ada outstanding IN dari kemarin untuk menentukan apakah boleh OUT anytime
  $time_validation = validateAttendanceTime($status, $user_id, $labor_id, $conn);
  if(!$time_validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $time_validation['message']]);
    exit;
  }

  // VALIDASI DAILY LIMIT (1x masuk, 1x keluar per hari, exception jika lembur)
  $daily_limit_validation = validateDailyLimit($user_id, $status, $conn);
  if(!$daily_limit_validation['valid']) {
    echo json_encode(['status' => 'error', 'message' => $daily_limit_validation['message']]);
    exit;
  }

  // Check if user exists dan ambil nama user
  $user_check = mysqli_query($conn, "SELECT id, nama FROM users WHERE id = $user_id");
  if(mysqli_num_rows($user_check) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mahasiswa tidak ditemukan']);
    exit;
  }
  $user_data = mysqli_fetch_assoc($user_check);
  $user_nama = $user_data['nama'];
  $user_nama_escaped = mysqli_real_escape_string($conn, $user_nama);

  // Save to face_embeddings table
  $embedding_escaped = mysqli_real_escape_string($conn, $embedding);
  $insert_query = "
    INSERT INTO face_embeddings (user_id, embedding, created_at)
    VALUES ($user_id, '$embedding_escaped', NOW())
  ";

  if(mysqli_query($conn, $insert_query)) {
    $embedding_id = mysqli_insert_id($conn);

    // Also save to attendance_logs dengan stored_user_nama dan keterangan
    $keterangan = hitungKeterangan($status, date('Y-m-d H:i:s'), $labor_id, $conn);
    $keterangan_escaped = mysqli_real_escape_string($conn, $keterangan);
    
    $attendance_query = "
      INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
      VALUES ($user_id, $labor_id, '$status', $confidence, '$user_nama_escaped', '$keterangan_escaped', NOW())
    ";

    if(mysqli_query($conn, $attendance_query)) {
      echo json_encode([
        'status' => 'success',
        'message' => 'Wajah berhasil terdaftar dan absensi tercatat',
        'embedding_id' => $embedding_id
      ]);
    } else {
      // Embedding sudah disimpan, tapi attendance gagal
      echo json_encode([
        'status' => 'success',
        'message' => 'Wajah terdaftar tapi absensi gagal tercatat',
        'embedding_id' => $embedding_id,
        'warning' => 'Silakan mencoba ulang untuk absensi'
      ]);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan embedding: ' . mysqli_error($conn)]);
  }
}

else if($action === 'submit_attendance') {
  // Simpan attendance untuk user yang sudah terdaftar (wajah cocok)
  $user_id = intval($_POST['user_id'] ?? 0);
  $status = $_POST['status'] ?? 'IN';
  $labor_id = intval($_POST['labor_id'] ?? 0);
  $confidence = floatval($_POST['confidence'] ?? 0);

  error_log("=== SUBMIT_ATTENDANCE START ===");
  error_log("RAW POST: " . json_encode($_POST));
  error_log("user_id: $user_id (type: " . gettype($user_id) . ")");
  error_log("status: $status");
  error_log("labor_id: $labor_id");
  error_log("conn: " . ($conn ? "OK (" . get_class($conn) . ")" : "NULL"));
  error_log("conn->ping(): " . ($conn && $conn->ping() ? "OK" : "FAILED"));

  if(!$user_id || !$labor_id) {
    error_log("ERROR: Parameter tidak lengkap - user_id: $user_id, labor_id: $labor_id");
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
  }

  // VALIDASI WAKTU SERVER-SIDE (tidak bisa dimanipulasi dari client)
  // Menggunakan waktu WIB dari server dengan NTP sync
  // Cek apakah ada outstanding IN dari kemarin untuk menentukan apakah boleh OUT anytime
  error_log("Calling validateAttendanceTime with user_id=$user_id, labor_id=$labor_id, conn=" . ($conn ? "OK" : "NULL"));
  $time_validation = validateAttendanceTime($status, $user_id, $labor_id, $conn);
  error_log("Time validation result: " . json_encode($time_validation));
  if(!$time_validation['valid']) {
    error_log("ERROR: Time validation failed - " . $time_validation['message']);
    echo json_encode(['status' => 'error', 'message' => $time_validation['message']]);
    exit;
  }

  // VALIDASI DAILY LIMIT (1x masuk, 1x keluar per hari, exception jika lembur)
  $daily_limit_validation = validateDailyLimit($user_id, $status, $conn);
  error_log("Daily limit validation result: " . json_encode($daily_limit_validation));
  if(!$daily_limit_validation['valid']) {
    error_log("ERROR: Daily limit validation failed - " . $daily_limit_validation['message']);
    echo json_encode(['status' => 'error', 'message' => $daily_limit_validation['message']]);
    exit;
  }

  writeToLog("=== Proceeding after Daily Limit Validation ===");
  
  // Check if user exists dan ambil nama user
  writeToLog("About to query user data for user_id: $user_id");
  $user_check = mysqli_query($conn, "SELECT id, nama FROM users WHERE id = $user_id");
  
  if(!$user_check) {
    writeToLog("ERROR: User query failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Gagal query user: ' . mysqli_error($conn)]);
    exit;
  }
  
  writeToLog("User query executed, rows: " . mysqli_num_rows($user_check));
  if(mysqli_num_rows($user_check) === 0) {
    writeToLog("ERROR: User not found - user_id: $user_id");
    echo json_encode(['status' => 'error', 'message' => 'Mahasiswa tidak ditemukan']);
    exit;
  }
  
  $user_data = mysqli_fetch_assoc($user_check);
  $user_nama = $user_data['nama'];
  $user_nama_escaped = mysqli_real_escape_string($conn, $user_nama);
  writeToLog("User found: $user_nama (ID: $user_id)");

  // ENFORCE: Validasi status transition - cek last status TODAY
  writeToLog("About to check last status today...");
  $today = date('Y-m-d');
  $last_status_query = mysqli_query($conn, "
    SELECT status 
    FROM attendance_logs 
    WHERE user_id = $user_id AND DATE(created_at) = '$today'
    ORDER BY created_at DESC 
    LIMIT 1
  ");
  
  if(!$last_status_query) {
    writeToLog("ERROR: Last status query failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Gagal query status: ' . mysqli_error($conn)]);
    exit;
  }
  
  $lastStatus = null;
  if(mysqli_num_rows($last_status_query) > 0) {
    $status_row = mysqli_fetch_assoc($last_status_query);
    $lastStatus = $status_row['status'];
  }
  writeToLog("Last status today: " . ($lastStatus ?? 'NULL'));
  
  // Check outstanding IN dari tanggal manapun sebelum hari ini (bukan hanya kemarin)
  writeToLog("About to check outstanding IN (any past date)...");
  $today = date('Y-m-d');
  $outstanding_in_query = mysqli_query($conn, "
    SELECT COUNT(*) as cnt FROM attendance_logs 
    WHERE user_id = $user_id 
    AND status = 'IN'
    AND DATE(created_at) < '$today'
    AND id > COALESCE((
        SELECT MAX(id) FROM attendance_logs 
        WHERE user_id = $user_id 
        AND status = 'OUT'
        AND DATE(created_at) < '$today'
    ), 0)
  ");
  
  if(!$outstanding_in_query) {
    writeToLog("ERROR: Outstanding IN query failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Gagal query outstanding IN: ' . mysqli_error($conn)]);
    exit;
  }
  
  $result_in = mysqli_fetch_assoc($outstanding_in_query);
  $outstanding_in_count = $result_in ? intval($result_in['cnt']) : 0;
  
  $has_outstanding_in = ($outstanding_in_count > 0);
  writeToLog("Outstanding IN check (any past date) - outstanding_in_count: $outstanding_in_count, has_outstanding: " . ($has_outstanding_in ? 'YES' : 'NO'));
  
  // Validasi status transition dengan consideration untuk lembur lintas hari
  writeToLog("Checking status transition...");
  if($has_outstanding_in && $lastStatus === null && $status === 'OUT') {
    // Case: Ada outstanding IN kemarin, hari ini blm ada log sama sekali, OUT pertama kali (normal untuk close lembur)
    // This is allowed - OUT 1 to close yesterday's overtime
    writeToLog("ALLOWED: Outstanding IN, no log today, OUT first time (close overtime)");
  } else if($lastStatus === 'IN' && $status !== 'OUT') {
    writeToLog("ERROR: Status transition - last status IN, trying to do: $status");
    echo json_encode([
      'status' => 'error', 
      'message' => 'Anda sudah masuk hari ini. Silakan keluar terlebih dahulu sebelum masuk lagi.'
    ]);
    exit;
  } else if(($lastStatus === 'OUT' || ($lastStatus === null && !$has_outstanding_in)) && $status !== 'IN') {
    writeToLog("ERROR: Status transition - last status: " . ($lastStatus ?? 'NULL (no outstanding)') . ", trying to do: $status");
    echo json_encode([
      'status' => 'error', 
      'message' => 'Anda belum masuk hari ini. Silakan masuk terlebih dahulu sebelum keluar.'
    ]);
    exit;
  } else {
    writeToLog("ALLOWED: Status transition valid - lastStatus: " . ($lastStatus ?? 'NULL') . ", status: $status");
  }

  // Insert attendance log dengan stored_user_nama dan keterangan
  writeToLog("Calculating keterangan...");
  writeToLog("DEBUG keterangan logic:");
  writeToLog("  - status: $status (should be OUT)");
  writeToLog("  - has_outstanding_in: " . ($has_outstanding_in ? 'YES' : 'NO'));
  writeToLog("  - lastStatus: " . ($lastStatus ?? 'NULL'));
  writeToLog("  - condition check: status=OUT? " . ($status === 'OUT' ? 'Y' : 'N') . ", has_outstanding? " . ($has_outstanding_in ? 'Y' : 'N') . ", lastStatus=null? " . ($lastStatus === null ? 'Y' : 'N'));
  
  // Jika close lembur (OUT pertama dengan outstanding IN dari kemarin), keterangan = lembur
  if($status === 'OUT' && $has_outstanding_in && $lastStatus === null) {
    $keterangan = 'lembur';
    writeToLog("✅ MATCHED: Closing lembur lintas hari - set keterangan = lembur");
  } else {
    writeToLog("❌ NOT MATCHED: Will use hitungKeterangan()");
    $keterangan = hitungKeterangan($status, date('Y-m-d H:i:s'), $labor_id, $conn);
  }
  
  $keterangan_escaped = mysqli_real_escape_string($conn, $keterangan);
  writeToLog("Final Keterangan: $keterangan");
  writeToLog("Keterangan (escaped): [$keterangan_escaped]");
  writeToLog("Keterangan length: " . strlen($keterangan));
  writeToLog("Keterangan bytes: " . bin2hex($keterangan));
  
  $attendance_query = "
    INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
    VALUES ($user_id, $labor_id, '$status', $confidence, '$user_nama_escaped', '$keterangan_escaped', NOW())
  ";
  
  writeToLog("About to INSERT attendance - Query: " . str_replace("\n", " ", $attendance_query));
  writeToLog("Query length: " . strlen($attendance_query));

  if(mysqli_query($conn, $attendance_query)) {
    writeToLog("SUCCESS: Attendance recorded - attendance_id: " . mysqli_insert_id($conn));
    echo json_encode([
      'status' => 'success',
      'message' => 'Absensi berhasil tercatat',
      'attendance_id' => mysqli_insert_id($conn)
    ]);
  } else {
    writeToLog("ERROR: Insert failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan attendance: ' . mysqli_error($conn)]);
  }
  writeToLog("=== SUBMIT_ATTENDANCE END ===");
}

// TEST ENDPOINT: Simulate OUT at specific time (for testing only)
else if($action === 'test_submit_attendance_at_time') {
  writeToLog("=== TEST MODE: submit_attendance_at_time ===");
  $user_id = intval($_POST['user_id'] ?? 0);
  $status = $_POST['status'] ?? 'IN';
  $labor_id = intval($_POST['labor_id'] ?? 0);
  $test_time = $_POST['test_time'] ?? null; // Format: "16:30" atau "16:30:45"
  $confidence = floatval($_POST['confidence'] ?? 0);

  writeToLog("TEST - user_id: $user_id, status: $status, labor_id: $labor_id, test_time: $test_time");

  if(!$user_id || !$labor_id || !$test_time) {
    writeToLog("TEST - ERROR: Parameter tidak lengkap");
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap (user_id, labor_id, test_time required)']);
    exit;
  }

  // Validate test_time format
  if(!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $test_time)) {
    writeToLog("TEST - ERROR: Invalid time format. Use HH:MM or HH:MM:SS");
    echo json_encode(['status' => 'error', 'message' => 'Invalid time format. Use HH:MM or HH:MM:SS']);
    exit;
  }

  // Parse test time
  $time_parts = explode(':', $test_time);
  $test_hour = intval($time_parts[0]);
  $test_minute = intval($time_parts[1]);
  $test_second = isset($time_parts[2]) ? intval($time_parts[2]) : 0;

  writeToLog("TEST - Parsed time: hour=$test_hour, minute=$test_minute, second=$test_second");

  // Validate time menggunakan test time
  if($status === 'IN') {
    if($test_hour < 6) {
      writeToLog("TEST - REJECT: IN time too early (hour $test_hour < 6)");
      echo json_encode(['status' => 'error', 'message' => 'Test mode: IN harus jam 06:00 atau lebih']);
      exit;
    }
  } else if($status === 'OUT') {
    // Check outstanding IN (tanpa batasan tanggal - bukan hanya kemarin)
    $today_date = date('Y-m-d');
    $outstanding_in_test_query = mysqli_query($conn, "
      SELECT COUNT(*) as cnt FROM attendance_logs 
      WHERE user_id = $user_id 
      AND status = 'IN'
      AND DATE(created_at) < '$today_date'
      AND id > COALESCE((
          SELECT MAX(id) FROM attendance_logs 
          WHERE user_id = $user_id 
          AND status = 'OUT'
          AND DATE(created_at) < '$today_date'
      ), 0)
    ");
    $result = mysqli_fetch_assoc($outstanding_in_test_query);
    $outstanding_in_count_test = $result ? intval($result['cnt']) : 0;

    $today = date('Y-m-d');
    $today_out_query = mysqli_query($conn, "
      SELECT COUNT(*) as cnt FROM attendance_logs 
      WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'OUT'
    ");
    $result = mysqli_fetch_assoc($today_out_query);
    $today_out_count = $result ? intval($result['cnt']) : 0;

    $has_outstanding_in = ($outstanding_in_count_test > 0 && $today_out_count === 0);
    
    writeToLog("TEST - Outstanding IN check (any past date): outstanding_in=$outstanding_in_count_test, today_out=$today_out_count, has_outstanding=$has_outstanding_in");

    // Allow OUT anytime jika ada outstanding IN, atau jam >= 16 untuk normal OUT
    if(!$has_outstanding_in && $test_hour < 16) {
      writeToLog("TEST - REJECT: OUT time too early (hour $test_hour < 16) and no outstanding IN");
      echo json_encode(['status' => 'error', 'message' => 'Test mode: OUT harus jam 16:00 atau lebih (atau ada lembur yang belum diselesaikan)']);
      exit;
    }
  }

  // Check user exists
  $user_check = mysqli_query($conn, "SELECT id, nama FROM users WHERE id = $user_id");
  if(mysqli_num_rows($user_check) === 0) {
    writeToLog("TEST - ERROR: User not found");
    echo json_encode(['status' => 'error', 'message' => 'Mahasiswa tidak ditemukan']);
    exit;
  }
  $user_data = mysqli_fetch_assoc($user_check);
  $user_nama = $user_data['nama'];
  $user_nama_escaped = mysqli_real_escape_string($conn, $user_nama);

  // Hitung keterangan (jika close lembur, set lembur)
  $today = date('Y-m-d');
  $last_status_query = mysqli_query($conn, "
    SELECT status FROM attendance_logs 
    WHERE user_id = $user_id AND DATE(created_at) = '$today'
    ORDER BY created_at DESC LIMIT 1
  ");
  $lastStatus = mysqli_num_rows($last_status_query) > 0 ? mysqli_fetch_assoc($last_status_query)['status'] : null;

  if($status === 'OUT' && $has_outstanding_in && $lastStatus === null) {
    $keterangan = 'lembur';
  } else {
    $keterangan = hitungKeterangan($status, date('Y-m-d H:i:s'), $labor_id, $conn);
  }
  $keterangan_escaped = mysqli_real_escape_string($conn, $keterangan);

  writeToLog("TEST - Keterangan: $keterangan");
  writeToLog("TEST - Ready to INSERT");

  // Insert dengan current timestamp (bukan test time) - attendance tetap mencatat waktu real
  $attendance_query = "
    INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
    VALUES ($user_id, $labor_id, '$status', $confidence, '$user_nama_escaped', '$keterangan_escaped', NOW())
  ";

  if(mysqli_query($conn, $attendance_query)) {
    writeToLog("TEST - SUCCESS: Attendance recorded at test time $test_time");
    echo json_encode([
      'status' => 'success',
      'message' => "TEST MODE: Absensi berhasil tercatat dengan waktu simulasi $test_time",
      'attendance_id' => mysqli_insert_id($conn),
      'test_time' => $test_time
    ]);
  } else {
    writeToLog("TEST - ERROR: Insert failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . mysqli_error($conn)]);
  }
}

// AUTO OUT LEMBUR SYSTEM: Otomatis OUT semua users dengan outstanding IN yang sudah terlambat
else if($action === 'auto_out_system_lembur') {
  writeToLog("=== AUTO_OUT_SYSTEM_LEMBUR START ===");
  
  $current_time = date('H:i:s');
  $current_hour = intval(date('H'));
  $current_minute = intval(date('i'));
  $today = date('Y-m-d');
  
  writeToLog("Current time: $current_time (hour: $current_hour, minute: $current_minute), Today: $today");
  
  // Condition 1: Sudah melewati jam 06:00 (terlambat untuk IN)?
  // 06:00 adalah jam terlambat untuk IN - jika sudah lewat jam ini dan belum OUT, auto-OUT
  if($current_hour < 6 || ($current_hour === 6 && $current_minute < 00)) {
    writeToLog("SKIP: Masih sebelum jam 06:00 - users masih bisa OUT manual");
    echo json_encode([
      'status' => 'skip',
      'message' => 'Masih sebelum jam 06:00, auto OUT belum triggered',
      'current_time' => $current_time
    ]);
    exit;
  }
  
  writeToLog("Current time: $current_time >= 06:00, proceeding to find users with outstanding IN");
  
  // Find users dengan outstanding IN dari tanggal manapun sebelum hari ini yang belum di-OUT
  $query = "
    SELECT DISTINCT 
      u.id as user_id,
      u.nama,
      3 as labor_id
    FROM users u
    WHERE u.id IN (
      -- User yang punya IN sebelum hari ini yang ID-nya lebih besar dari OUT terakhir mereka
      SELECT DISTINCT al_in.user_id
      FROM attendance_logs al_in
      WHERE al_in.status = 'IN'
        AND DATE(al_in.created_at) < '$today'
        AND al_in.id > COALESCE((
            SELECT MAX(al_out.id) 
            FROM attendance_logs al_out 
            WHERE al_out.user_id = al_in.user_id 
              AND al_out.status = 'OUT'
              AND DATE(al_out.created_at) < '$today'
        ), 0)
    )
    AND u.id NOT IN (
      -- Kecualikan user yang sudah OUT hari ini
      SELECT user_id 
      FROM attendance_logs 
      WHERE DATE(created_at) = '$today' 
        AND status = 'OUT'
    )
  ";
  
  writeToLog("Query: " . str_replace("\n", " ", $query));
  
  $result = mysqli_query($conn, $query);
  if(!$result) {
    writeToLog("ERROR: Query failed - " . mysqli_error($conn));
    echo json_encode(['status' => 'error', 'message' => 'Query error']);
    exit;
  }
  
  $row_count = mysqli_num_rows($result);
  writeToLog("Query returned $row_count rows");
  
  $auto_out_count = 0;
  $auto_out_details = [];
  
  // Process each user dengan outstanding IN
  while($row = mysqli_fetch_assoc($result)) {
    $user_id = intval($row['user_id']);
    $user_nama = $row['nama'];
    $labor_id = 3; // Fixed labor_id
    
    writeToLog("Processing auto OUT for user_id: $user_id, nama: $user_nama");
    
    $user_nama_escaped = mysqli_real_escape_string($conn, $user_nama);
    $keterangan = 'lembur - submitted system otomatis';
    $keterangan_escaped = mysqli_real_escape_string($conn, $keterangan);
    
    $out_query = "
      INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
      VALUES ($user_id, $labor_id, 'OUT', 1.0, '$user_nama_escaped', '$keterangan_escaped', NOW())
    ";
    
    writeToLog("OUT Query: " . str_replace("\n", " ", $out_query));
    
    if(mysqli_query($conn, $out_query)) {
      $out_id = mysqli_insert_id($conn);
      writeToLog("SUCCESS: Auto OUT created - user_id: $user_id, id: $out_id");
      $auto_out_count++;
      $auto_out_details[] = [
        'user_id' => $user_id,
        'user_nama' => $user_nama,
        'attendance_id' => $out_id
      ];
    } else {
      writeToLog("ERROR: Failed to auto OUT user $user_id - " . mysqli_error($conn));
    }
  }
  
  writeToLog("=== AUTO_OUT_SYSTEM_LEMBUR END - Total auto OUT: $auto_out_count ===");
  
  echo json_encode([
    'status' => 'success',
    'message' => "Otomatis OUT $auto_out_count user(s) yang lembur tidak diselesaikan",
    'auto_out_count' => $auto_out_count,
    'details' => $auto_out_details
  ]);
}

else {
  echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Close log file
if ($log_handle) {
    fclose($log_handle);
}
?>