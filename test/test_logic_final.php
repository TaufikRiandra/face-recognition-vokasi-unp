<?php
error_reporting(E_ALL);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

echo "\n" . str_repeat("=", 85) . "\n";
echo "LEMBUR + OUT VALIDATION TEST\n";
echo str_repeat("=", 85) . "\n\n";

$test_user_id = 96;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

function get_counts() {
    global $test_user_id, $today, $yesterday, $conn;
    $y_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'"))['cnt'] ?? 0);
    $y_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'"))['cnt'] ?? 0);
    $t_in = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'IN'"))['cnt'] ?? 0);
    $t_out = intval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance_logs WHERE user_id = $test_user_id AND DATE(created_at) = '$today' AND status = 'OUT'"))['cnt'] ?? 0);
    return compact('y_in', 'y_out', 't_in', 't_out');
}

$counts = get_counts();
extract($counts);

echo "📊 CURRENT STATE:\n";
echo "  Yesterday: IN=$y_in, OUT=$y_out\n";
echo "  Today: IN=$t_in, OUT=$t_out\n\n";

$total_out = $y_out + $t_out;
$has_lembur = ($y_in > 0 && $total_out === 0);

echo "Lembur Status:\n";
if($has_lembur) {
    echo "  ❌ Outstanding lembur: YES\n";
    echo "  Next steps:\n";
    echo "    1. User should OUT (to close lembur from yesterday)\n";
    echo "    2. Then IN (to work today)\n";
    echo "    3. Then OUT (work shift OUT)\n";
} else {
    echo "  ✅ Outstanding lembur: NO\n";
}

echo "\n" . str_repeat("-", 85) . "\n";
echo "TESTING: Can user OUT right now?\n";
echo str_repeat("-", 85) . "\n\n";

$out_validation = validateDailyLimit($test_user_id, 'OUT', $conn);

echo "Result: " . ($out_validation['valid'] ? '✅ ALLOWED' : '❌ REJECTED') . "\n";
echo "Message: " . $out_validation['message'] . "\n\n";

// Analyze the result
if($t_in === 0) {
    echo "Analysis: Belum ada IN hari ini\n";
    if($has_lembur && $out_validation['valid']) {
        echo "  ✅ CORRECT: Can OUT to close lembur\n";
    } elseif($total_out >= 1 && $out_validation['valid']) {
        echo "  ✅ CORRECT: Can OUT again (already has OUT)\n";
    } elseif($total_out === 0 && $out_validation['valid']) {
        echo "  ✅ CORRECT: Can OUT (first OUT of today)\n";
    }
} else {
    echo "Analysis: Ada IN hari ini (work shift)\n";
    if($t_out === 0 && $out_validation['valid']) {
        echo "  ✅ CORRECT: Can OUT after IN (work shift OUT)\n";
    } elseif($t_out >= 1 && !$out_validation['valid']) {
        echo "  ✅ CORRECT: Cannot OUT 2x (already OUT once)\n";
    }
}

echo "\n" . str_repeat("=", 85) . "\n";
echo "RECOMMENDATION\n";
echo str_repeat("=", 85) . "\n\n";

if($t_in === 0 && $has_lembur && $out_validation['valid']) {
    echo "User should submit OUT first (to close yesterday's lembur)\n";
    echo "Then IN for today's work shift\n";
    echo "Then OUT for work shift\n";
} elseif($t_in === 0 && !$has_lembur && $out_validation['valid']) {
    echo "User can submit OUT\n";
} elseif($t_in >= 1 && $t_out === 0 && $out_validation['valid']) {
    echo "User has IN today, should submit OUT now (for work shift)\n";
} elseif($t_in >= 1 && $t_out >= 1 && !$out_validation['valid']) {
    echo "User already IN and OUT for today\n";
    echo "To work again, must IN first (new shift)\n";
}

echo "\n" . str_repeat("=", 85) . "\n\n";
?>
