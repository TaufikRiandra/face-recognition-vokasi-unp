<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

$output = [];
$output[] = "=== VALIDATION FIX TEST ===\n";
$output[] = "Test Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test user
$test_user_id = 96;
$test_labor_id = 3;

// Get current state
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Check yesterday records
$y_in_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'");
$y_in = mysqli_fetch_assoc($y_in_query);
$yesterday_in_count = intval($y_in['cnt'] ?? 0);

$y_out_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'");
$y_out = mysqli_fetch_assoc($y_out_query);
$yesterday_out_count = intval($y_out['cnt'] ?? 0);

// Check today records
$t_in_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'IN'");
$t_in = mysqli_fetch_assoc($t_in_query);
$today_in_count = intval($t_in['cnt'] ?? 0);

$has_outstanding = ($yesterday_in_count > 0 && $yesterday_out_count === 0);

$output[] = "Current State:\n";
$output[] = "  Yesterday: IN=$yesterday_in_count, OUT=$yesterday_out_count\n";
$output[] = "  Today: IN=$today_in_count\n";
$output[] = "  Has Outstanding Lembur: " . ($has_outstanding ? 'YES' : 'NO') . "\n\n";

// Now test the validation
$output[] = "Testing validateDailyLimit for IN:\n";
$result = validateDailyLimit($test_user_id, 'IN', $conn);

$output[] = "  Validation Result: " . ($result['valid'] ? 'ALLOWED' : 'REJECTED') . "\n";
$output[] = "  Message: " . $result['message'] . "\n\n";

if($has_outstanding && !$result['valid'] && strpos($result['message'], 'lembur') !== false) {
    $output[] = "✅ TEST PASSED: Validation correctly rejected IN due to outstanding lembur\n";
} elseif($has_outstanding && $result['valid']) {
    $output[] = "❌ TEST FAILED: Validation allowed IN when outstanding lembur exists!\n";
} elseif(!$has_outstanding && $result['valid']) {
    $output[] = "✅ TEST PASSED: Validation correctly allowed IN (no outstanding lembur)\n";
} else {
    $output[] = "⚠️ TEST AMBIGUOUS: Outstanding=$has_outstanding, Result=" . ($result['valid'] ? 'ALLOW' : 'REJECT') . "\n";
}

// Write to file
$file = fopen('backend/validation_test_result.txt', 'w');
foreach($output as $line) {
    fwrite($file, $line);
}
fclose($file);

// Also echo for debugging
echo implode('', $output);
?>
