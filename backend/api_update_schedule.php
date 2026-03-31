<?php
/**
 * Update Schedule Configuration
 * Usage: POST /api_update_schedule.php
 * 
 * Expected JSON:
 * {
 *   "jam_masuk_awal": "06:00",
 *   "jam_masuk_standar": "09:00",
 *   "jam_pulang_standar": "18:00",
 *   "jam_pulang_akhir": "20:00",
 *   "toleransi_terlambat": 15
 * }
 */

header('Content-Type: application/json');
include 'koneksi.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate input - jam_masuk_standar dan jam_pulang_standar wajib
    if (!isset($data['jam_masuk_standar']) || !isset($data['jam_pulang_standar'])) {
        throw new Exception("Missing required fields: jam_masuk_standar and jam_pulang_standar");
    }
    
    $jam_masuk_awal = $data['jam_masuk_awal'] ?? '06:00';
    $jam_masuk_standar = $data['jam_masuk_standar'];
    $jam_pulang_standar = $data['jam_pulang_standar'];
    $jam_pulang_akhir = $data['jam_pulang_akhir'] ?? '20:00';
    $toleransi = isset($data['toleransi_terlambat']) ? intval($data['toleransi_terlambat']) : 15;
    
    // Validate format (expecting HH:MM)
    if (!preg_match('/^\d{2}:\d{2}$/', $jam_masuk_awal)) {
        throw new Exception("Invalid jam_masuk_awal format. Use HH:MM");
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $jam_masuk_standar) || !preg_match('/^\d{2}:\d{2}$/', $jam_pulang_standar) || !preg_match('/^\d{2}:\d{2}$/', $jam_pulang_akhir)) {
        throw new Exception("Invalid time format. Use HH:MM");
    }
    
    if ($toleransi < 0 || $toleransi > 120) {
        throw new Exception("Tolerance must be between 0 and 120 minutes");
    }
    
    // Convert to HH:MM:SS format for database
    $jam_masuk_awal_db = $jam_masuk_awal . ':00';
    $jam_masuk_standar_db = $jam_masuk_standar . ':00';
    $jam_pulang_standar_db = $jam_pulang_standar . ':00';
    $jam_pulang_akhir_db = $jam_pulang_akhir . ':00';
    
    // Update labor table (Labor Tefa - ID 3)
    $labor_id = 3;
    $query = "UPDATE labor 
              SET jam_masuk_awal = '$jam_masuk_awal_db',
                  jam_masuk_standar = '$jam_masuk_standar_db',
                  jam_pulang_standar = '$jam_pulang_standar_db',
                  jam_pulang_akhir = '$jam_pulang_akhir_db',
                  toleransi_terlambat = $toleransi
              WHERE id = $labor_id";
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception("Database update failed: " . mysqli_error($conn));
    }
    
    // Log the change  
    $log_file = __DIR__ . '/../logs/schedule_changes.log';
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    $log_entry = date('Y-m-d H:i:s') . " | Updated schedule: START=$jam_masuk_awal_db, IN_STANDARD=$jam_masuk_standar_db, OUT_STANDARD=$jam_pulang_standar_db, OUT_FINAL=$jam_pulang_akhir_db, Tolerance=$toleransi min\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Jadwal kerja berhasil diperbarui',
        'data' => [
            'jam_masuk_awal' => $jam_masuk_awal_db,
            'jam_masuk_standar' => $jam_masuk_standar_db,
            'jam_pulang_standar' => $jam_pulang_standar_db,
            'jam_pulang_akhir' => $jam_pulang_akhir_db,
            'toleransi_terlambat' => $toleransi
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
