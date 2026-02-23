<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

echo "\n" . str_repeat("=", 85) . "\n";
echo "FIXED OUT VALIDATION TEST\n";
echo str_repeat("=", 85) . "\n\n";

$test_user_id = 96;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Show records
echo "📝 RECORDS:\n";
$records = mysqli_query($conn, "
    SELECT DATE(created_at) as date, TIME(created_at) as time, status, keterangan 
    FROM attendance_logs 
    WHERE user_id = $test_user_id AND DATE(created_at) IN ('$yesterday', '$today')
    ORDER BY created_at ASC
");
$count = 0;
while($row = mysqli_fetch_assoc($records)) {
    $count++;
    echo "  $count. [{$row['date']} {$row['time']}] {$row['status']} - {$row['keterangan']}\n";
}

echo "\n" . str_repeat("-", 85) . "\n";
echo "SCENARIO: User just INed, now tries to OUT for work shift\n";
echo str_repeat("-", 85) . "\n\n";

echo "Testing: Can user OUT now?\n";
$out_validation = validateDailyLimit($test_user_id, 'OUT', $conn);

echo "Result: " . ($out_validation['valid'] ? '✅ ALLOWED' : '❌ REJECTED') . "\n";
echo "Message: " . $out_validation['message'] . "\n\n";

if($out_validation['valid']) {
    echo "✅ CORRECT! User can OUT for work shift\n";
    echo "   The auto-OUT for lembur is not counted as work shift OUT\n";
} else {
    echo "❌ ERROR: Should allow OUT!\n";
}

echo "\n" . str_repeat("=", 85) . "\n\n";
?>
