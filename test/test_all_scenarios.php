<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

echo "\n" . str_repeat("=", 85) . "\n";
echo "COMPREHENSIVE LEMBUR + OUT VALIDATION TEST\n";
echo str_repeat("=", 85) . "\n\n";

$test_user_id = 96;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get counts
$y_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'"))['cnt'] ?? 0);
$y_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'"))['cnt'] ?? 0);
$t_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'IN'"))['cnt'] ?? 0);
$t_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT' AND keterangan NOT LIKE '%lembur%' AND keterangan NOT LIKE '%system%'"))['cnt'] ?? 0);
$t_out_lembur = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT' AND (keterangan LIKE '%lembur%' OR keterangan LIKE '%system%')"))['cnt'] ?? 0);

echo "📊 CURRENT STATE:\n";
echo "  Yesterday: IN=$y_in, OUT=$y_out\n";
echo "  Today: IN=$t_in, OUT (lembur)=$t_out_lembur, OUT (work)=$t_out\n\n";

// Test 1: OUT validation
echo str_repeat("-", 85) . "\n";
echo "TEST 1: Can user OUT now? (for work shift)\n";
echo str_repeat("-", 85) . "\n";
$out_val = validateDailyLimit($test_user_id, 'OUT', $conn);
echo "Result: " . ($out_val['valid'] ? '✅ ALLOWED' : '❌ REJECTED') . "\n";
echo "Message: {$out_val['message']}\n";
if($out_val['valid']) {
    echo "✓ User can OUT for work shift\n";
} else {
    echo "✗ ERROR: Should allow OUT!\n";
}

// Test 2: IN validation (should reject if already IN)
echo "\n" . str_repeat("-", 85) . "\n";
echo "TEST 2: Can user IN again? (should reject - already IN)\n";
echo str_repeat("-", 85) . "\n";
$in_val = validateDailyLimit($test_user_id, 'IN', $conn);
echo "Result: " . ($in_val['valid'] ? '✅ ALLOWED' : '❌ REJECTED') . "\n";
echo "Message: {$in_val['message']}\n";
if(!$in_val['valid']) {
    echo "✓ Correctly rejects IN (already IN 1x today)\n";
} else {
    echo "✗ ERROR: Should reject IN!\n";
}

echo "\n" . str_repeat("=", 85) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 85) . "\n\n";

if($out_val['valid'] && !$in_val['valid']) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "\nBehavior:\n";
    echo "  - Auto-OUT lembur doesn't count as work shift OUT\n";
    echo "  - User can OUT for work shift (after IN)\n";
    echo "  - User cannot IN again (already IN 1x)\n";
    echo "  - Rule: 1x IN, 1x OUT per day ✓\n";
} else {
    echo "⚠️ Some tests failed\n";
}

echo "\n" . str_repeat("=", 85) . "\n\n";
?>
