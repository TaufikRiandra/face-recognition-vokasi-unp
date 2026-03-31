<?php
/**
 * Export Users to PDF
 * Requires: TCPDF library
 * Usage: POST /api_export_users_pdf.php
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
    
    // Check if TCPDF is available
    $tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    if (!file_exists($tcpdf_path)) {
        // If TCPDF not available, return error with installation instructions
        throw new Exception("TCPDF library not found. Install with: composer require tecnickcom/tcpdf");
    }
    
    require_once($tcpdf_path);
    
    // Create PDF object
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document properties
    $pdf->SetCreator('Sistem Absensi');
    $pdf->SetAuthor('Sistem Absensi');
    $pdf->SetTitle('Daftar User');
    $pdf->SetSubject('Data User');
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Daftar User', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Tanggal: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(245, 158, 11); // Primary yellow color
    $pdf->SetTextColor(255, 255, 255);
    
    $w = array(15, 60, 40, 50);
    $header = array('No', 'Nama', 'NIM', 'Tanggal Dibuat');
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 248, 255); // Light background
    
    $fill = false;
    foreach ($users as $idx => $user) {
        $pdf->Cell($w[0], 6, $idx + 1, 'LR', 0, 'C', $fill);
        $pdf->Cell($w[1], 6, substr($user['nama'], 0, 25), 'LR', 0, 'L', $fill);
        $pdf->Cell($w[2], 6, $user['nim'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[3], 6, $user['created_at'], 'LR', 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
    
    // Close table
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    // Send PDF to browser
    $filename = 'Daftar_User_' . date('d-m-Y_His') . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' = download
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
