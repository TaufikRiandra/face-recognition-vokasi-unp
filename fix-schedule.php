<?php
/**
 * Fix schedule configuration for Labor Tefa (ID 3)
 * Change from: 09:30 entry, 18:30 exit, 1 min tolerance
 * To: 09:00 entry, 16:00 exit, 15 min tolerance
 */

// Connect to database
$conn = new mysqli("localhost", "root", "", "absensi_labor");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update Labor Tefa (ID 3) schedule
$update_query = "UPDATE labor 
                 SET jam_masuk_standar='09:00:00', 
                     jam_pulang_standar='16:00:00', 
                     toleransi_terlambat=15 
                 WHERE id=3";

if ($conn->query($update_query) === TRUE) {
    echo "✓ Schedule fixed successfully for Labor Tefa (ID 3):\n";
    echo "  - Entry time: 09:00 (deadline for on-time arrival)\n";
    echo "  - Late tolerance: 15 minutes (late mark after 09:15)\n";
    echo "  - Exit time: 16:00 (4:00 PM - normal working hours end)\n";
    echo "  - Overtime starts: After 16:00 + tolerance (16:15)\n";
    
    // Verify the update
    $verify_query = "SELECT * FROM labor WHERE id=3";
    $result = $conn->query($verify_query);
    $labor = $result->fetch_assoc();
    
    echo "\n✓ Database verification:\n";
    echo "  jam_masuk_standar: " . $labor['jam_masuk_standar'] . "\n";
    echo "  jam_pulang_standar: " . $labor['jam_pulang_standar'] . "\n";
    echo "  toleransi_terlambat: " . $labor['toleransi_terlambat'] . " minutes\n";
} else {
    echo "✗ Error updating schedule: " . $conn->error;
}

$conn->close();
?>
