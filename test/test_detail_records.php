<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';

$test_user_id = 96;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

echo "\n" . str_repeat("=", 85) . "\n";
echo "DETAIL RECORDS\n";
echo str_repeat("=", 85) . "\n\n";

$records = mysqli_query($conn, "
    SELECT id, DATE(created_at) as date, TIME(created_at) as time, status, keterangan 
    FROM attendance_logs 
    WHERE user_id = $test_user_id AND DATE(created_at) IN ('$yesterday', '$today')
    ORDER BY created_at ASC
");

echo "All Records:\n";
$row_num = 1;
while($row = mysqli_fetch_assoc($records)) {
    echo "$row_num. [{$row['date']} {$row['time']}] {$row['status']} - {$row['keterangan']}\n";
    $row_num++;
}

echo "\n" . str_repeat("-", 85) . "\n";
echo "ANALYSIS\n";
echo str_repeat("-", 85) . "\n\n";

// Get counts
$y_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'"))['cnt'] ?? 0);
$y_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'"))['cnt'] ?? 0);
$t_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'IN'"))['cnt'] ?? 0);
$t_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT'"))['cnt'] ?? 0);

echo "Summary:\n";
echo "  Yesterday: IN=$y_in, OUT=$y_out\n";
echo "  Today: IN=$t_in, OUT=$t_out\n\n";

// Check what's in today's OUT record
$out_records = mysqli_query($conn, "
    SELECT keterangan FROM attendance_logs 
    WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT'
");

echo "Today's OUT Records detail:\n";
while($out = mysqli_fetch_assoc($out_records)) {
    echo "  - Keterangan: '{$out['keterangan']}'\n";
}

echo "\n" . str_repeat("=", 85) . "\n";
echo "UNDERSTANDING THE SITUATION\n";
echo str_repeat("=", 85) . "\n\n";

if($y_in > 0 && $y_out === 0) {
    echo "✓ User has outstanding lembur from yesterday (IN but no OUT yesterday)\n";
    if($t_out > 0) {
        echo "✓ There is an OUT record today\n";
        
        // Check if it's auto-OUT
        $auto_out = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT' AND keterangan LIKE '%system%'
        "));
        
        if(intval($auto_out['cnt']) > 0) {
            echo "  → This is AUTO-OUT (from system)\n";
            echo "  → System automatically closed the lembur\n";
        } else {
            echo "  → This might be USER OUT (or normal work OUT)\n";
        }
    }
}

if($t_in >= 1) {
    echo "✓ User INs today (work shift has started)\n";
    
    if($t_out >= 1) {
        echo "✓ User OUTs today (work shift is closed)\n";
        echo "\nCurrent situation: User completed work (IN → OUT)\n";
        echo "They can now:\n";
        echo "  - IN again for next shift\n";
        echo "  - Cannot OUT again without IN\n";
    }
}

echo "\n" . str_repeat("=", 85) . "\n\n";
?>
