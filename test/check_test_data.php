<?php
require_once 'backend/koneksi.php';

echo "=== CHECKING TEST DATA ===\n";

// Check recent records
$query = mysqli_query($conn, "
    SELECT id, user_id, status, keterangan, DATE(created_at) as date, TIME(created_at) as time 
    FROM attendance_logs 
    WHERE user_id IN (95, 96) 
    ORDER BY created_at DESC 
    LIMIT 20
");

echo "\nRecent Attendance Records:\n";
while($row = mysqli_fetch_assoc($query)) {
    echo "ID: {$row['id']}, User: {$row['user_id']}, Status: {$row['status']}, Keterangan: {$row['keterangan']}, Date: {$row['date']}, Time: {$row['time']}\n";
}

// Check today
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

echo "\n=== TODAY ($today) ===\n";
$query = mysqli_query($conn, "
    SELECT user_id, status, COUNT(*) as cnt 
    FROM attendance_logs 
    WHERE DATE(created_at) = '$today' AND user_id IN (95, 96)
    GROUP BY user_id, status
");
while($row = mysqli_fetch_assoc($query)) {
    echo "User {$row['user_id']}: {$row['status']} x{$row['cnt']}\n";
}

echo "\n=== YESTERDAY ($yesterday) ===\n";
$query = mysqli_query($conn, "
    SELECT user_id, status, COUNT(*) as cnt 
    FROM attendance_logs 
    WHERE DATE(created_at) = '$yesterday' AND user_id IN (95, 96)
    GROUP BY user_id, status
");
while($row = mysqli_fetch_assoc($query)) {
    echo "User {$row['user_id']}: {$row['status']} x{$row['cnt']}\n";
}

echo "\n";
?>
