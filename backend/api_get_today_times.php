<?php
/**
 * Get first IN and last OUT times for today
 * Usage: GET /api_get_today_times.php?labor_id=3
 */

header('Content-Type: application/json');
include 'koneksi.php';

try {
    $labor_id = isset($_GET['labor_id']) ? intval($_GET['labor_id']) : 0;
    $today = date('Y-m-d');
    
    // Get first IN time
    $first_in_query = "
        SELECT created_at FROM attendance_logs 
        WHERE status = 'IN'
        AND DATE(created_at) = '$today'
        AND labor_id = $labor_id
        ORDER BY created_at ASC
        LIMIT 1
    ";
    $first_in_result = mysqli_query($conn, $first_in_query);
    $first_in = $first_in_result ? mysqli_fetch_assoc($first_in_result)['created_at'] : null;
    
    // Get last OUT time
    $last_out_query = "
        SELECT created_at FROM attendance_logs 
        WHERE status = 'OUT'
        AND DATE(created_at) = '$today'
        AND labor_id = $labor_id
        ORDER BY created_at DESC
        LIMIT 1
    ";
    $last_out_result = mysqli_query($conn, $last_out_query);
    $last_out = $last_out_result ? mysqli_fetch_assoc($last_out_result)['created_at'] : null;
    
    // Get labor schedule settings
    $labor_query = "
        SELECT jam_masuk_awal, jam_masuk_standar, jam_pulang_standar, toleransi_terlambat
        FROM labor 
        WHERE id = $labor_id
    ";
    $labor_result = mysqli_query($conn, $labor_query);
    $labor_settings = $labor_result ? mysqli_fetch_assoc($labor_result) : null;
    
    echo json_encode([
        'success' => true,
        'first_in' => $first_in ? date('H:i:s', strtotime($first_in)) : null,
        'last_out' => $last_out ? date('H:i:s', strtotime($last_out)) : null,
        'labor_settings' => $labor_settings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
