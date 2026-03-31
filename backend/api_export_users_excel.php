<?php
/**
 * Export Users to Excel (CSV format - Excel compatible)
 * Usage: POST /api_export_users_excel.php
 * No external libraries required - uses built-in CSV generation
 */

header('Content-Type: application/json');

include 'koneksi.php';

try {
    // Get all users from database
    $query = "SELECT id, nama, nim, created_at FROM users WHERE is_active = 1 ORDER BY nama ASC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . mysqli_error($conn));
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    // Create CSV content
    $filename = 'Daftar_User_' . date('d-m-Y_His') . '.csv';
    
    // Set headers for file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output BOM for proper UTF-8 encoding in Excel
    echo "\xEF\xBB\xBF";
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, array('No', 'Nama', 'NIM', 'Tanggal Dibuat'), ';');
    
    // Add data rows
    foreach ($users as $idx => $user) {
        fputcsv($output, array(
            $idx + 1,
            $user['nama'],
            $user['nim'],
            $user['created_at']
        ), ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
