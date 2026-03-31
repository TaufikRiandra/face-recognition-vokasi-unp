<?php
/**
 * Get Current Schedule Configuration
 * Usage: GET /api_get_schedule.php
 */

header('Content-Type: application/json');
include 'koneksi.php';

try {
    $labor_id = 3; // Labor Tefa (fixed)
    
    $query = "SELECT jam_masuk_awal, jam_masuk_standar, jam_pulang_standar, jam_pulang_akhir, toleransi_terlambat 
              FROM labor 
              WHERE id = $labor_id";
    
    $result = mysqli_query($conn, $query);
    if (!$result || mysqli_num_rows($result) == 0) {
        // Return defaults if not found
        echo json_encode([
            'success' => true,
            'jam_masuk_awal' => '06:00',
            'jam_masuk_standar' => '09:00',
            'jam_pulang_standar' => '18:00',
            'jam_pulang_akhir' => '20:00',
            'toleransi_terlambat' => 15,
            'message' => 'Using default schedule'
        ]);
        exit;
    }
    
    $labor = mysqli_fetch_assoc($result);
    
    // Extract only HH:MM part for time input field
    $jam_masuk_awal = substr($labor['jam_masuk_awal'], 0, 5);
    $jam_masuk_standar = substr($labor['jam_masuk_standar'], 0, 5);
    $jam_pulang_standar = substr($labor['jam_pulang_standar'], 0, 5);
    $jam_pulang_akhir = substr($labor['jam_pulang_akhir'], 0, 5);
    
    echo json_encode([
        'success' => true,
        'jam_masuk_awal' => $jam_masuk_awal,
        'jam_masuk_standar' => $jam_masuk_standar,
        'jam_pulang_standar' => $jam_pulang_standar,
        'jam_pulang_akhir' => $jam_pulang_akhir,
        'toleransi_terlambat' => (int)$labor['toleransi_terlambat']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
