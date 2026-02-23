<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

$action = $_GET['action'] ?? '';
$user_id = intval($_GET['user_id'] ?? 0);
$labor_id = intval($_GET['labor_id'] ?? 3);

header('Content-Type: application/json');

if($action === 'setup_test') {
    // Setup: Clear old test data and insert fresh lembur record
    $response = [];
    
    // Delete all lembur records from yesterday for this user
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    $delete = mysqli_query($conn, "DELETE FROM attendance_logs WHERE user_id = $user_id AND DATE(created_at) = '$yesterday'");
    $response['deleted_yesterday'] = mysqli_affected_rows($conn);
    
    // Insert a fake IN record for yesterday at 23:00 with keterangan='lembur'
    $yesterday_late = $yesterday . ' 23:00:00';
    $insert = mysqli_query($conn, "
        INSERT INTO attendance_logs (user_id, labor_id, status, keterangan, created_at, confidence_score, stored_user_nama) 
        VALUES ($user_id, $labor_id, 'IN', 'lembur', '$yesterday_late', 0.95, 'Test User')
    ");
    
    if($insert) {
        $response['status'] = 'success';
        $response['message'] = "Setup complete: Created lembur IN for $yesterday at 23:00";
        $response['lembur_created'] = true;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to insert lembur record';
    }
    
    echo json_encode($response);
    
} else if($action === 'test_validation') {
    // Test: Try to validate IN for today when user has outstanding lembur
    $response = [];
    
    // Run the validation
    $validation = validateDailyLimit($user_id, 'IN', $conn);
    
    $response['validation_result'] = $validation;
    if($validation['valid']) {
        $response['test_result'] = '❌ FAILED - Validation allowed IN when it should reject for outstanding lembur';
    } else {
        if(strpos($validation['message'], 'lembur') !== false) {
            $response['test_result'] = '✅ PASSED - Validation correctly rejected IN due to outstanding lembur';
        } else {
            $response['test_result'] = '⚠️ REJECTED but wrong reason: ' . $validation['message'];
        }
    }
    
    echo json_encode($response);
    
} else if($action === 'check_status') {
    // Check current attendance status
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $response = [];
    
    // Yesterday count
    $y_in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'"));
    $y_out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'"));
    
    // Today count
    $t_in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'IN'"));
    $t_out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'OUT'"));
    
    $response['status'] = 'success';
    $response['yesterday'] = ['in' => intval($y_in['cnt']), 'out' => intval($y_out['cnt'])];
    $response['today'] = ['in' => intval($t_in['cnt']), 'out' => intval($t_out['cnt'])];
    $response['has_outstanding_lembur'] = (intval($y_in['cnt']) > 0 && intval($y_out['cnt']) === 0);
    
    echo json_encode($response);
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Usage: ?action=setup_test|test_validation|check_status&user_id=96&labor_id=3'
    ]);
}
?>
