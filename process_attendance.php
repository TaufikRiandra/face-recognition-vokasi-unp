<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$action = $_POST['action'] ?? '';

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

    // Also save to attendance_logs dengan stored_user_nama
    $attendance_query = "
      INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, created_at)
      VALUES ($user_id, $labor_id, '$status', $confidence, '$user_nama_escaped', NOW())
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

  if(!$user_id || !$labor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
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

  // ENFORCE: Validasi status transition - cek last status TODAY
  $today = date('Y-m-d');
  $last_status_query = mysqli_query($conn, "
    SELECT status 
    FROM attendance_logs 
    WHERE user_id = $user_id AND DATE(created_at) = '$today'
    ORDER BY created_at DESC 
    LIMIT 1
  ");
  
  $lastStatus = null;
  if($last_status_query && mysqli_num_rows($last_status_query) > 0) {
    $status_row = mysqli_fetch_assoc($last_status_query);
    $lastStatus = $status_row['status'];
  }
  
  // Validasi: Jika lastStatus = IN, maka status harus OUT
  // Jika lastStatus = OUT atau NULL, maka status harus IN
  if($lastStatus === 'IN' && $status !== 'OUT') {
    echo json_encode([
      'status' => 'error', 
      'message' => 'Anda sudah masuk hari ini. Silakan keluar terlebih dahulu sebelum masuk lagi.'
    ]);
    exit;
  } else if(($lastStatus === 'OUT' || $lastStatus === null) && $status !== 'IN') {
    echo json_encode([
      'status' => 'error', 
      'message' => 'Anda belum masuk hari ini. Silakan masuk terlebih dahulu sebelum keluar.'
    ]);
    exit;
  }

  // Insert attendance log dengan stored_user_nama
  $attendance_query = "
    INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, created_at)
    VALUES ($user_id, $labor_id, '$status', $confidence, '$user_nama_escaped', NOW())
  ";

  if(mysqli_query($conn, $attendance_query)) {
    echo json_encode([
      'status' => 'success',
      'message' => 'Absensi berhasil tercatat',
      'attendance_id' => mysqli_insert_id($conn)
    ]);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan attendance: ' . mysqli_error($conn)]);
  }
}

else {
  echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>

