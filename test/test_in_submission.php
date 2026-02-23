<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/koneksi.php';
require_once 'backend/helpers_attendance.php';

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST: SIMULATING USER ATTEMPTING TO IN (Like Clicking Button)\n";
echo str_repeat("=", 80) . "\n\n";

$test_user_id = 96;
$test_labor_id = 3;

// Simulate POST request
$_POST['action'] = 'submit_attendance';
$_POST['user_id'] = $test_user_id;
$_POST['labor_id'] = $test_labor_id;
$_POST['status'] = 'IN';
$_POST['confidence'] = 0.95;

// Capture output by starting output buffering
ob_start();

// Now let's trace through what would happen
echo "🔍 TRACING SUBMIT ATTENDANCE PROCESS:\n\n";

$user_id = intval($_POST['user_id'] ?? 0);
$labor_id = intval($_POST['labor_id'] ?? 0);
$status = $_POST['status'] ?? '';
$confidence = floatval($_POST['confidence'] ?? 0);

echo "1️⃣ Input Parameters:\n";
echo "   User ID: $user_id\n";
echo "   Labor ID: $labor_id\n";
echo "   Status: $status\n";
echo "   Confidence: $confidence\n\n";

echo "2️⃣ Checking Daily Limit Validation:\n";
$daily_limit_validation = validateDailyLimit($user_id, $status, $conn);
echo "   Validation Result: " . ($daily_limit_validation['valid'] ? '✅ VALID' : '❌ INVALID') . "\n";
echo "   Message: " . $daily_limit_validation['message'] . "\n\n";

if($daily_limit_validation['valid']) {
    echo "3️⃣ Attempting to Insert Attendance Record...\n";
    
    // Get user data
    $user_check = mysqli_query($conn, "SELECT id, nama FROM users WHERE id = $user_id");
    if(mysqli_num_rows($user_check) > 0) {
        $user = mysqli_fetch_assoc($user_check);
        echo "   User Found: {$user['nama']} (ID: {$user['id']})\n\n";
        
        // Simulate the record insertion
        $confidence_score = $confidence * 100;
        $current_time = date('Y-m-d H:i:s');
        
        echo "4️⃣ Would Insert:\n";
        echo "   user_id: $user_id\n";
        echo "   labor_id: $labor_id\n";
        echo "   status: $status\n";
        echo "   confidence: $confidence_score%\n";
        echo "   timestamp: $current_time\n\n";
        
        echo "✅ SUCCESS! Would return:\n";
        echo "   Status: 'success'\n";
        echo "   Message: 'Absensi berhasil tercatat'\n";
    } else {
        echo "❌ User not found\n";
    }
} else {
    echo "❌ VALIDATION FAILED!\n";
    echo "   Error Message: {$daily_limit_validation['message']}\n";
    echo "   The user CANNOT IN at this time\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONCLUSION:\n";
echo str_repeat("=", 80) . "\n";

if($daily_limit_validation['valid']) {
    echo "✅ User CAN NOW IN successfully!\n";
    echo "   The auto-OUT system closed the lembur\n";
    echo "   Validation now allows IN for today\n";
} else {
    echo "❌ User STILL CANNOT IN!\n";
    echo "   Something is wrong with the fix\n";
}

echo "\n";
?>
