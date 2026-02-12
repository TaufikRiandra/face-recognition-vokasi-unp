<?php
session_start();
include 'koneksi.php';

if(!isset($_SESSION['admin_id'])) {
  die("Unauthorized access");
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$selected_labor = isset($_GET['labor']) ? intval($_GET['labor']) : 3;
$filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';
$export_type = isset($_GET['export']) ? $_GET['export'] : 'excel';

$where_parts = [];
if($search != '') {
  $where_parts[] = "(u.nama LIKE '%$search%' OR u.nim LIKE '%$search%')";
}
if($selected_labor > 0) {
  $where_parts[] = "al.labor_id = $selected_labor";
}
if($filter_date != '') {
  $where_parts[] = "DATE(al.created_at) = '$filter_date'";
}

$where_clause = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

$query = "SELECT al.id, al.user_id, COALESCE(al.labor_id, 0) as labor_id, al.status, al.confidence_score, al.created_at, al.stored_user_nama, u.nama as user_nama, u.nim, l.nama as labor_nama FROM attendance_logs al LEFT JOIN users u ON al.user_id = u.id LEFT JOIN labor l ON al.labor_id = l.id $where_clause ORDER BY al.created_at DESC";

$result = mysqli_query($conn, $query);
if(!$result) {
  die("Query error: " . mysqli_error($conn));
}

$attendance_logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
$timestamp = date('Y-m-d_H-i-s');
$filter_text = $filter_date ? date('d-m-Y', strtotime($filter_date)) : date('d-m-Y');

if($export_type === 'pdf') {
  exportToPDF($attendance_logs, $filter_text, $timestamp, $filter_date);
} else {
  exportToExcel($attendance_logs, $filter_text, $timestamp, $filter_date);
}

function exportToPDF($data, $filter_text, $timestamp, $filter_date) {
  global $conn;
  $labor_id = intval($_GET['labor'] ?? 3);
  
  $labor_result = mysqli_query($conn, "SELECT nama FROM labor WHERE id = $labor_id");
  $labor_data = mysqli_fetch_assoc($labor_result);
  $labor_name = htmlspecialchars($labor_data ? $labor_data['nama'] : 'Unknown');
  
  $filter_date_display = $filter_date ? date('d-m-Y', strtotime($filter_date)) : 'Semua Data';
  $current_date = date('d-m-Y H:i:s');
  $total = count($data);
  
  $table_rows = '';
  foreach($data as $index => $log) {
    $row_num = $index + 1;
    
    if($log['user_nama']) {
      $nama_display = htmlspecialchars($log['user_nama']) . '<br><small style="color:#999;">NIM: ' . htmlspecialchars($log['nim']) . '</small>';
    } else {
      $nama_display = '<span style="color:#d32f2f;">deleted user<br>(' . htmlspecialchars($log['stored_user_nama'] ?? 'Unknown') . ')</span>';
    }
    
    $status_text = $log['status'] === 'IN' ? 'Masuk' : 'Keluar';
    $status_color = $log['status'] === 'IN' ? '#4caf50' : '#f44336';
    $status_display = '<span style="color:' . $status_color . ';font-weight:bold;">' . $status_text . '</span>';
    
    $tanggal = date('d-m-Y', strtotime($log['created_at']));
    $waktu = date('H:i:s', strtotime($log['created_at']));
    $labor_nama = htmlspecialchars($log['labor_nama'] ?? '-');
    
    $table_rows .= '<tr><td style="border:1px solid #ccc;padding:8px;text-align:center;">' . $row_num . '</td>';
    $table_rows .= '<td style="border:1px solid #ccc;padding:8px;">' . $nama_display . '</td>';
    $table_rows .= '<td style="border:1px solid #ccc;padding:8px;">' . $status_display . '</td>';
    $table_rows .= '<td style="border:1px solid #ccc;padding:8px;">' . $tanggal . '</td>';
    $table_rows .= '<td style="border:1px solid #ccc;padding:8px;">' . $waktu . '</td>';
    $table_rows .= '<td style="border:1px solid #ccc;padding:8px;">' . $labor_nama . '</td></tr>';
  }
  
  $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Riwayat Absensi</title><style>@media print{body{margin:0}}body{font-family:Calibri,Arial,sans-serif;font-size:11pt;margin:1in;line-height:1.5;color:#333}.header{text-align:center;margin-bottom:20px;border-bottom:3px solid #2B579A;padding-bottom:15px}.header h1{font-size:16pt;font-weight:bold;margin:0 0 5px 0;color:#2B579A}.header p{margin:5px 0 0 0;color:#666;font-size:10pt}.info-box{margin-bottom:20px;font-size:11pt;background-color:#f9f9f9;padding:12px;border-left:4px solid #2B579A}.info-row{margin:6px 0}.info-label{font-weight:bold;display:inline-block;width:150px;color:#2B579A}table{width:100%;border-collapse:collapse;margin-top:20px;font-size:10pt}th{background-color:#2B579A;color:white;padding:10px;text-align:left;border:1px solid #000;font-weight:bold}tr:nth-child(even){background-color:#f5f5f5}.signature-section{margin-top:80px;display:flex;justify-content:space-between;page-break-inside:avoid}.signature-box{width:45%}.signature-line{height:70px;border-bottom:2px solid #000;margin-bottom:10px}.signature-label{font-size:11pt;font-weight:bold;text-align:center}.signature-info{font-size:9pt;text-align:center;color:#666;margin-top:5px}.footer{margin-top:30px;text-align:center;font-size:9pt;color:#999;border-top:1px solid #ccc;padding-top:15px}</style></head><body><div class="header"><h1>Laporan Riwayat Absensi Pengunjung Labor</h1><p>Sistem Absensi Face Recognition</p><p>Dicetak: ' . $current_date . '</p></div><div class="info-box"><div class="info-row"><span class="info-label">Labor:</span> <strong>' . $labor_name . '</strong></div><div class="info-row"><span class="info-label">Tanggal Data:</span> <strong>' . $filter_date_display . '</strong></div><div class="info-row"><span class="info-label">Total Record:</span> <strong>' . $total . ' entries</strong></div></div><table><thead><tr><th style="width:5%;">No</th><th style="width:25%;">Nama / NIM</th><th style="width:10%;">Status</th><th style="width:12%;">Tanggal</th><th style="width:12%;">Waktu</th><th style="width:15%;">Labor</th></tr></thead><tbody>' . $table_rows . '</tbody></table><div class="signature-section"><div class="signature-box"><div class="signature-label">Disiapkan oleh</div><div class="signature-line"></div><div class="signature-info">Nama & Tanggal</div></div><div class="signature-box"><div class="signature-label">Diketahui oleh</div><div class="signature-line"></div><div class="signature-info">Nama & Tanggal</div></div></div><div class="footer"><p>Dokumen ini secara otomatis dihasilkan oleh Sistem Absensi Face Recognition</p><p>&copy; ' . date('Y') . ' - Hak Cipta Dilindungi</p></div></body></html>';
  
  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: inline; filename="Riwayat_Absensi_' . $timestamp . '.html"');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  
  echo $html;
  exit;
}

function exportToExcel($data, $filter_text, $timestamp, $filter_date) {
  global $conn;
  $labor_id = intval($_GET['labor'] ?? 3);
  
  $labor_result = mysqli_query($conn, "SELECT nama FROM labor WHERE id = $labor_id");
  $labor_data = mysqli_fetch_assoc($labor_result);
  $labor_name = $labor_data ? $labor_data['nama'] : 'Unknown';
  
  $output = "Laporan Riwayat Absensi Pengunjung Labor\n";
  $output .= "Labor," . $labor_name . "\n";
  $output .= "Tanggal Filter," . ($filter_date ? date('d-m-Y', strtotime($filter_date)) : 'Semua Data') . "\n";
  $output .= "Tanggal Export," . date('d-m-Y H:i:s') . "\n";
  $output .= "Total Data," . count($data) . "\n";
  $output .= "\nNo,Nama,NIM,Status,Tanggal,Waktu,Labor\n";
  
  foreach($data as $index => $log) {
    $no = $index + 1;
    $nama = $log['user_nama'] ? $log['user_nama'] : 'deleted user (' . ($log['stored_user_nama'] ?? 'Unknown') . ')';
    $nim = $log['nim'] ?? '';
    $status = $log['status'] === 'IN' ? 'Masuk' : 'Keluar';
    $tanggal = date('d-m-Y', strtotime($log['created_at']));
    $waktu = date('H:i:s', strtotime($log['created_at']));
    $labor_nama = $log['labor_nama'] ?? '-';
    
    $nama = '"' . str_replace('"', '""', $nama) . '"';
    $nim = '"' . str_replace('"', '""', $nim) . '"';
    $labor_nama = '"' . str_replace('"', '""', $labor_nama) . '"';
    
    $output .= "$no,$nama,$nim,$status,$tanggal,$waktu,$labor_nama\n";
  }
  
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="Riwayat_Absensi_' . $timestamp . '.csv"');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  
  echo $output;
  exit;
}
?>
