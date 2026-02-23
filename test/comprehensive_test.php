<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

$output = [];
$output[] = "\n" . str_repeat("=", 70) . "\n";
$output[] = "COMPREHENSIVE LEMBUR VALIDATION TEST\n";
$output[] = str_repeat("=", 70) . "\n";
$output[] = "Test Date: " . date('Y-m-d H:i:s') . "\n\n";

$test_user_id = 96;

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

$t_out_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT'");
$t_out = mysqli_fetch_assoc($t_out_query);
$today_out_count = intval($t_out['cnt'] ?? 0);

$has_outstanding = ($yesterday_in_count > 0 && $yesterday_out_count === 0);

$output[] = "SCENARIO STATE:\n";
$output[] = "  Yesterday ($yesterday): IN=$yesterday_in_count, OUT=$yesterday_out_count\n";
$output[] = "  Today ($today): IN=$today_in_count, OUT=$today_out_count\n";
$output[] = "  Outstanding Lembur: " . ($has_outstanding ? 'YES ✓' : 'NO') . "\n\n";

// TEST 1: Try to IN when there's outstanding lembur
$output[] = str_repeat("-", 70) . "\n";
$output[] = "TEST 1: Try to IN when outstanding lembur exists\n";
$output[] = str_repeat("-", 70) . "\n";

$result_in = validateDailyLimit($test_user_id, 'IN', $conn);
$output[] = "Result: " . ($result_in['valid'] ? '✓ ALLOWED' : '✗ REJECTED') . "\n";
$output[] = "Message: " . $result_in['message'] . "\n";

if($has_outstanding && !$result_in['valid'] && strpos($result_in['message'], 'lembur') !== false) {
    $output[] = "✅ PASS: Correctly blocked IN due to outstanding lembur\n";
} elseif($has_outstanding && $result_in['valid']) {
    $output[] = "❌ FAIL: Should have blocked IN!\n";
} else {
    $output[] = "⚠️ STATE MISMATCH: Outstanding=$has_outstanding, Result=" . ($result_in['valid'] ? 'ALLOW' : 'REJECT') . "\n";
}

// TEST 2: Try to OUT when there's outstanding lembur
$output[] = "\n" . str_repeat("-", 70) . "\n";
$output[] = "TEST 2: Try to OUT to close outstanding lembur\n";
$output[] = str_repeat("-", 70) . "\n";

$result_out = validateDailyLimit($test_user_id, 'OUT', $conn);
$output[] = "Result: " . ($result_out['valid'] ? '✓ ALLOWED' : '✗ REJECTED') . "\n";
$output[] = "Message: " . $result_out['message'] . "\n";

if($has_outstanding && $result_out['valid']) {
    $output[] = "✅ PASS: Correctly allowed OUT to close lembur\n";
} elseif($has_outstanding && !$result_out['valid']) {
    $output[] = "❌ FAIL: Should have allowed OUT to close lembur!\n";
}

// TEST 3: Check what happens if no outstanding lembur - try OUT
$output[] = "\n" . str_repeat("-", 70) . "\n";
$output[] = "TEST 3: Scenario without outstanding lembur\n";
$output[] = str_repeat("-", 70) . "\n";

if(!$has_outstanding) {
    $output[] = "No outstanding lembur - testing normal OUT validation...\n";
    $result_out_normal = validateDailyLimit($test_user_id, 'OUT', $conn);
    $output[] = "OUT Result: " . ($result_out_normal['valid'] ? '✓ ALLOWED' : '✗ REJECTED') . "\n";
    $output[] = "Message: " . $result_out_normal['message'] . "\n";
} else {
    $output[] = "Currently has outstanding lembur - skipping normal scenario test\n";
    $output[] = "To test normal scenario, manually OUT to close lembur first\n";
}

// SUMMARY
$output[] = "\n" . str_repeat("=", 70) . "\n";
$output[] = "TEST SUMMARY\n";
$output[] = str_repeat("=", 70) . "\n";

if($has_outstanding && !$result_in['valid'] && strpos($result_in['message'], 'lembur') !== false && $result_out['valid']) {
    $output[] = "✅ ALL CRITICAL TESTS PASSED!\n";
    $output[] = "   ✓ IN correctly blocked when outstanding lembur\n";
    $output[] = "   ✓ OUT correctly allowed to close lembur\n";
    $output[] = "\nThe lembur lintas hari validation is working correctly!\n";
} else {
    $output[] = "⚠️ Some tests didn't pass as expected\n";
}

$output[] = "\n";

// Write to file and output
file_put_contents('backend/validation_test_result.txt', implode('', $output));
echo implode('', $output);
?>
