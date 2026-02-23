<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST: AUTO-OUT LEMBUR SCENARIO\n";
echo str_repeat("=", 80) . "\n\n";

$test_user_id = 96;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get current state
$y_in_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'");
$y_in = intval(mysqli_fetch_assoc($y_in_q)['cnt'] ?? 0);

$y_out_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'");
$y_out = intval(mysqli_fetch_assoc($y_out_q)['cnt'] ?? 0);

$t_in_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'IN'");
$t_in = intval(mysqli_fetch_assoc($t_in_q)['cnt'] ?? 0);

$t_out_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT'");
$t_out = intval(mysqli_fetch_assoc($t_out_q)['cnt'] ?? 0);

echo "📊 CURRENT DATABASE STATE:\n";
echo "   Yesterday ($yesterday): IN=$y_in, OUT=$y_out\n";
echo "   Today ($today): IN=$t_in, OUT=$t_out\n\n";

// Show detailed records
$detail_q = mysqli_query($conn, "
    SELECT DATE(created_at) as date, TIME(created_at) as time, status, keterangan 
    FROM attendance_logs 
    WHERE user_id = $test_user_id AND DATE(created_at) IN ('$yesterday', '$today')
    ORDER BY created_at DESC
");

if(mysqli_num_rows($detail_q) > 0) {
    echo "📝 DETAIL RECORDS:\n";
    while($row = mysqli_fetch_assoc($detail_q)) {
        echo "   [{$row['date']} {$row['time']}] {$row['status']} - {$row['keterangan']}\n";
    }
    echo "\n";
}

// Test IN validation
echo "🧪 TESTING IN VALIDATION:\n";
$in_validation = validateDailyLimit($test_user_id, 'IN', $conn);
echo "   Result: " . ($in_validation['valid'] ? '✅ ALLOWED' : '❌ REJECTED') . "\n";
echo "   Message: " . $in_validation['message'] . "\n\n";

// Interpretation
echo "📋 INTERPRETATION:\n";
if($y_in > 0) {
    if($t_out > 0) {
        echo "   ✅ Lembur dari kemarin SUDAH ditutup (OUT di hari ini oleh auto-OUT)!\n";
        echo "   ✅ User SEKARANG BISA IN untuk bekerja hari ini\n";
        if($in_validation['valid']) {
            echo "   ✅ VALIDATION PASSED - Sistem bekerja dengan benar!\n";
        } else {
            echo "   ❌ VALIDATION FAILED - Ada bug, seharusnya allowed!\n";
        }
    } else {
        echo "   ⏳ Lembur dari kemarin BELUM ditutup (auto-OUT belum jalan atau gagal)\n";
        echo "   ⏳ User TIDAK BISA IN sampai lembur ditutup\n";
        if(!$in_validation['valid']) {
            echo "   ✅ VALIDATION CORRECT - Menolak karena lembur belum tutup\n";
        }
    }
} else {
    echo "   ℹ️ Tidak ada lembur dari kemarin\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
?>
