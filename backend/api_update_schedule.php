<?php
/**
 * Update Schedule Configuration
 * Usage: POST /api_update_schedule.php
 * 
 * Expected JSON:
 * {
 *   "jam_masuk_standar": "09:00",
 *   "jam_pulang_standar": "16:00",
 *   "toleransi_terlambat": 15
 * }
 */

header('Content-Type: application/json');
include 'koneksi.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate input
    if (!isset($data['jam_masuk_standar']) || !isset($data['jam_pulang_standar']) || !isset($data['toleransi_terlambat'])) {
        throw new Exception("Missing required fields");
    }
    
    $jam_masuk = $data['jam_masuk_standar'];
    $jam_pulang = $data['jam_pulang_standar'];
    $toleransi = intval($data['toleransi_terlambat']);
    
    // Validate format
    if (!preg_match('/^\d{2}:\d{2}$/', $jam_masuk) || !preg_match('/^\d{2}:\d{2}$/', $jam_pulang)) {
        throw new Exception("Invalid time format");
    }
    
    if ($toleransi < 0 || $toleransi > 120) {
        throw new Exception("Tolerance must be between 0 and 120 minutes");
    }
    
    // Convert to HH:MM:SS format
    $jam_masuk_db = $jam_masuk . ':00';
    $jam_pulang_db = $jam_pulang . ':00';
    
    // Update labor table (Labor Tefa - ID 3)
    $labor_id = 3;
    $query = "UPDATE labor 
              SET jam_masuk_standar = '$jam_masuk_db',
                  jam_pulang_standar = '$jam_pulang_db',
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
    
    $log_entry = date('Y-m-d H:i:s') . " | Updated schedule: IN=$jam_masuk_db, OUT=$jam_pulang_db, Tolerance=$toleransi min\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule updated successfully',
        'data' => [
            'jam_masuk_standar' => $jam_masuk_db,
            'jam_pulang_standar' => $jam_pulang_db,
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
?>
