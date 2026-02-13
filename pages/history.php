<?php
// Check if this is an AJAX request FIRST (jangan include header untuk AJAX)
if(!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
  include '../asset/header.php';
  include '../backend/helpers_attendance.php';
}

// AJAX Request Handler
if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
  // Include connection only (skip header output)
  include '../backend/koneksi.php';
  include '../backend/helpers_attendance.php';
  header('Content-Type: application/json');
  
  // Get filter parameters
  $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
  $selected_labor = isset($_GET['labor']) ? intval($_GET['labor']) : 3;
  $filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';
  $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
  $date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
  
  // Pagination setup
  $items_per_page = 20;
  $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
  if($current_page < 1) $current_page = 1;

  // Build where clause
  $where_parts = [];

  if($search != '') {
    $where_parts[] = "(u.nama LIKE '%$search%' OR u.nim LIKE '%$search%')";
  }

  if($selected_labor > 0) {
    $where_parts[] = "al.labor_id = $selected_labor";
  }

  // Support both single date filter and date range
  if($filter_date != '') {
    $where_parts[] = "DATE(al.created_at) = '$filter_date'";
  } else if($date_from != '' || $date_to != '') {
    if($date_from != '' && $date_to != '') {
      $where_parts[] = "DATE(al.created_at) BETWEEN '$date_from' AND '$date_to'";
    } else if($date_from != '') {
      $where_parts[] = "DATE(al.created_at) >= '$date_from'";
    } else if($date_to != '') {
      $where_parts[] = "DATE(al.created_at) <= '$date_to'";
    }
  }

  $where_clause = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";
  
  // Get total count for pagination
  $count_query = "SELECT COUNT(*) as total FROM attendance_logs al LEFT JOIN users u ON al.user_id = u.id LEFT JOIN labor l ON al.labor_id = l.id $where_clause";
  $count_result = mysqli_query($conn, $count_query);
  $total_records = mysqli_fetch_assoc($count_result)['total'] ?? 0;
  $total_pages = ceil($total_records / $items_per_page);
  
  // Validate current page
  if($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
  }
  
  // Calculate offset
  $offset = ($current_page - 1) * $items_per_page;

  // Get attendance logs with pagination
  $query = "
    SELECT 
      al.id,
      al.user_id,
      COALESCE(al.labor_id, 0) as labor_id,
      al.status,
      al.confidence_score,
      al.keterangan,
      al.created_at,
      al.stored_user_nama,
      u.nama as user_nama,
      u.nim,
      l.nama as labor_nama
    FROM attendance_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN labor l ON al.labor_id = l.id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT $items_per_page OFFSET $offset
  ";

  $result = mysqli_query($conn, $query);
  if(!$result) {
    $error_msg = mysqli_error($conn);
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
  }
  
  $attendance_logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
  
  // Generate table HTML
  $html = '';
  if(count($attendance_logs) > 0) {
    $html .= '<div class="results-info"><strong>' . count($attendance_logs) . '</strong> data dari <strong>' . $total_records . '</strong> total data</div>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-hover">';
    $html .= '<thead><tr><th width="4%">No</th><th width="20%">Nama</th><th width="11%">NIM</th><th width="11%">Tipe</th><th width="12%">Labor</th><th width="10%">Status</th><th width="15%">Keterangan</th><th width="17%">Waktu</th></tr></thead><tbody>';
    
    $start_num = ($current_page - 1) * $items_per_page + 1;
    
    foreach($attendance_logs as $index => $log) {
      $html .= '<tr>';
      $html .= '<td class="text-center">' . ($start_num + $index) . '</td>';
      $html .= '<td>';
      $html .= '<div class="visitor-name">';
      if($log['user_nama']) {
        $html .= htmlspecialchars($log['user_nama']);
      } else {
        $html .= '<span style="color: #ef4444; font-style: italic;">' . htmlspecialchars($log['stored_user_nama'] ?? 'Unknown') . '</span>';
      }
      $html .= '</div>';
      $html .= '<div class="visitor-type">';
      $html .= '<i class="fas fa-graduation-cap"></i> Mahasiswa';
      $html .= '</div>';
      $html .= '</td>';
      
      // Kolom NIM terpisah
      $html .= '<td>';
      if($log['user_nama']) {
        $html .= '<span style="font-weight: 600; color: var(--text-secondary);">' . htmlspecialchars($log['nim']) . '</span>';
      } else {
        $html .= '<span style="color: #ef4444; font-style: italic;">-</span>';
      }
      $html .= '</td>';
      
      $html .= '<td><span class="badge badge-mahasiswa">Mahasiswa</span></td>';
      
      $html .= '<td><span style="font-weight: 600; color: var(--primary);">' . htmlspecialchars($log['labor_nama'] ?? '-') . '</span></td>';
      
      if($log['status'] == 'IN') {
        $html .= '<td><span class="badge-in"><i class="fas fa-arrow-right-to-bracket"></i> Masuk</span></td>';
      } else {
        $html .= '<td><span class="badge-out"><i class="fas fa-arrow-right-from-bracket"></i> Keluar</span></td>';
      }
      
      $html .= '<td>' . getKeteranganHTML($log['keterangan'] ?? 'normal') . '</td>';
      
      $html .= '<td><div class="time-badge"><i class="fas fa-calendar"></i> ' . date('d-m-Y', strtotime($log['created_at'])) . '<br><i class="fas fa-clock"></i> ' . date('H:i:s', strtotime($log['created_at'])) . '</div></td>';
      $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
  } else {
    $html = '<div class="empty-state"><i class="fas fa-search"></i><p>Data tidak ditemukan</p><p style="font-size: 14px; font-weight: 400; margin: 0;">Tidak ada data yang sesuai dengan filter Anda. Coba ubah kriteria pencarian.</p></div>';
  }
  
  // Generate pagination HTML
  $pagination_html = '';
  if($total_pages > 1) {
    $pagination_html .= '<div class="pagination-container">';
    $pagination_html .= '<div class="pagination-info">Halaman <strong>' . $current_page . '</strong> dari <strong>' . $total_pages . '</strong> | Total: <strong>' . $total_records . '</strong> data</div>';
    $pagination_html .= '<div class="pagination-controls">';
    
    // First & Previous buttons
    if($current_page > 1) {
      $pagination_html .= '<button class="pagination-btn pagination-btn-first" onclick="goToPage(1)" title="Halaman Pertama"><i class="fas fa-chevron-left"></i> Pertama</button>';
      $pagination_html .= '<button class="pagination-btn pagination-btn-prev" onclick="goToPage(' . ($current_page - 1) . ')" title="Halaman Sebelumnya"><i class="fas fa-chevron-left"></i> Sebelumnya</button>';
    } else {
      $pagination_html .= '<button class="pagination-btn pagination-btn-disabled" disabled><i class="fas fa-chevron-left"></i> Pertama</button>';
      $pagination_html .= '<button class="pagination-btn pagination-btn-disabled" disabled><i class="fas fa-chevron-left"></i> Sebelumnya</button>';
    }
    
    // Page numbers
    $pagination_html .= '<div class="pagination-pages">';
    $max_pages_display = 5;
    $start_page = max(1, $current_page - floor($max_pages_display / 2));
    $end_page = min($total_pages, $start_page + $max_pages_display - 1);
    if($end_page - $start_page + 1 < $max_pages_display) {
      $start_page = max(1, $end_page - $max_pages_display + 1);
    }
    
    if($start_page > 1) {
      $pagination_html .= '<span class="pagination-ellipsis">...</span>';
    }
    
    for($i = $start_page; $i <= $end_page; $i++) {
      if($i == $current_page) {
        $pagination_html .= '<span class="pagination-page-active">' . $i . '</span>';
      } else {
        $pagination_html .= '<button class="pagination-page" onclick="goToPage(' . $i . ')">' . $i . '</button>';
      }
    }
    
    if($end_page < $total_pages) {
      $pagination_html .= '<span class="pagination-ellipsis">...</span>';
    }
    $pagination_html .= '</div>';
    
    // Next & Last buttons
    if($current_page < $total_pages) {
      $pagination_html .= '<button class="pagination-btn pagination-btn-next" onclick="goToPage(' . ($current_page + 1) . ')" title="Halaman Berikutnya">Berikutnya <i class="fas fa-chevron-right"></i></button>';
      $pagination_html .= '<button class="pagination-btn pagination-btn-last" onclick="goToPage(' . $total_pages . ')" title="Halaman Terakhir">Terakhir <i class="fas fa-chevron-right"></i></button>';
    } else {
      $pagination_html .= '<button class="pagination-btn pagination-btn-disabled" disabled>Berikutnya <i class="fas fa-chevron-right"></i></button>';
      $pagination_html .= '<button class="pagination-btn pagination-btn-disabled" disabled>Terakhir <i class="fas fa-chevron-right"></i></button>';
    }
    
    $pagination_html .= '</div></div>';
  }
  
  echo json_encode([
    'success' => true,
    'html' => $html,
    'pagination' => $pagination_html,
    'count' => count($attendance_logs),
    'total' => $total_records,
    'current_page' => $current_page,
    'total_pages' => $total_pages
  ]);
  exit;
}

// Get all labor for dropdown
//$labor_query = mysqli_query($conn, "SELECT id, nama FROM labor ORDER BY nama");
//if(!$labor_query) {
//  die("Error loading labor list: " . mysqli_error($conn));
//}
//$labor_list = mysqli_fetch_all($labor_query, MYSQLI_ASSOC);


// Tentukan labor yang ingin digunakan
$default_labor_id = 3; // Ubah ke ID labor yang ingin
$labor_query = mysqli_query($conn, "SELECT id, nama FROM labor WHERE id = $default_labor_id");
if(!$labor_query) {
  die("Error loading labor list: " . mysqli_error($conn));
}
$labor_list = mysqli_fetch_all($labor_query, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Absensi Pengunjung Labor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  
  :root {
    --primary: #f59e0b;
    --primary-light: #fbbf24;
    --primary-dark: #d97706;
    --secondary: #fb923c;
    --success: #10b981;
    --danger: #ef4444;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e0e0e0;
    --hover-bg: #f9f9f9;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  @keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    animation: fadeIn 0.3s ease;
  }

  .main-content {
    margin-left: 10px;
    padding: 40px 30px;
    min-height: 100vh;
    animation: fadeIn 0.3s ease;
  }

  .welcome-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    margin-bottom: 30px;
    animation: slideInDown 0.5s ease 0.1s both;
  }

  .welcome-card h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .welcome-card h2 i {
    color: var(--primary);
  }

  .welcome-card p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
  }

  .filter-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    margin-bottom: 30px;
    animation: slideInUp 0.5s ease 0.2s both;
  }

  .filter-card h5 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--text-primary);
  }

  .form-control,
  .form-select {
    border-color: var(--border-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: var(--text-primary);
    background-color: var(--bg-primary);
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
  }

  .filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    color: white;
  }

  .btn-secondary {
    background-color: var(--border-color);
    border: none;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-secondary:hover {
    background-color: #d1d5db;
  }

  /* Export Buttons */
  .btn-export {
    border: none;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
  }

  .btn-export-excel {
    background: linear-gradient(135deg, #207245 0%, #165C31 100%);
  }

  .btn-export-excel:hover {
    background: linear-gradient(135deg, #165C31 0%, #0F4620 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(32, 114, 69, 0.3);
    color: white;
  }

  .btn-export-pdf {
    background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
  }

  .btn-export-pdf:hover {
    background: linear-gradient(135deg, #991B1B 0%, #7C2D12 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    color: white;
  }
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    animation: slideInUp 0.5s ease 0.4s both;
  }

  .table-card h4 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }

  .table thead {
    background-color: var(--bg-secondary);
    border-bottom: 2px solid var(--border-color);
  }

  .table thead th {
    font-weight: 600;
    color: var(--text-primary);
    padding: 14px;
    font-size: 13px;
    border: none;
    text-align: left;
  }

  .table tbody td {
    padding: 14px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
  }

  .table tbody tr:hover {
    background-color: var(--hover-bg);
  }

  .badge-in {
    background-color: var(--success);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
  }

  .badge-out {
    background-color: var(--success);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
  }

  .visitor-name {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 4px;
  }

  .visitor-type {
    font-size: 12px;
    color: var(--text-secondary);
  }

  .confidence-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
  }

  .time-badge {
    background-color: var(--bg-secondary);
    padding: 6px 12px;
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 13px;
  }

  .badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
  }

  .badge-mahasiswa {
    background-color: #dbeafe;
    color: #0c4a6e;
  }

  .badge-tamu {
    background-color: #e9d5ff;
    color: #5b21b6;
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
  }

  .empty-state i {
    font-size: 48px;
    color: var(--primary);
    opacity: 0.3;
    margin-bottom: 20px;
  }

  .empty-state p {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 10px;
  }

  .search-input {
    position: relative;
  }

  .search-input i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
  }

  .results-info {
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 15px;
  }

  /* Pagination Styles */
  .pagination-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid var(--border-color);
  }

  .pagination-info {
    font-size: 14px;
    color: var(--text-secondary);
    font-weight: 500;
  }

  .pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
  }

  .pagination-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 2px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-primary);
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
  }

  .pagination-btn:hover:not(.pagination-btn-disabled) {
    border-color: var(--primary);
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    transform: translateY(-2px);
  }

  .pagination-btn.pagination-btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: var(--text-secondary);
  }

  .pagination-pages {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .pagination-page, .pagination-page-active {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 2px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-primary);
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
  }

  .pagination-page:hover {
    border-color: var(--primary);
    background: rgba(245, 158, 11, 0.05);
  }

  .pagination-page-active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-color: var(--primary);
    cursor: default;
  }

  .pagination-ellipsis {
    color: var(--text-secondary);
    font-weight: 600;
    padding: 0 4px;
  }

  @media (max-width: 768px) {
    .main-content {
      margin-left: 0;
      padding: 20px;
    }

    .welcome-card h2 {
      font-size: 24px;
    }

    .filter-buttons {
      flex-direction: column;
    }

    .filter-buttons button,
    .filter-buttons a {
      width: 100%;
    }

    .table {
      font-size: 13px;
    }

    .table thead th,
    .table tbody td {
      padding: 10px 8px;
    }
  }
</style>
</head>

<body>
  
<div class="main-content">

  <div class="welcome-card">
    <h2><i class="fas fa-history"></i> Riwayat Absensi Pengunjung</h2>
    <p>Cari dan lihat riwayat absensi pengunjung labor dengan berbagai filter</p>
  </div>

  <div class="filter-card">
    <h5><i class="fas fa-filter"></i> Filter Data</h5>
    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">Cari Nama / NIM</label>
        <div class="search-input">
          <input type="text" id="searchInput" class="form-control" placeholder="Ketik nama atau NIM...">
          <i class="fas fa-search"></i>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">Labor</label>
        <select id="laborSelect" class="form-select" disabled>
          <?php foreach($labor_list as $labor): ?>
            <option value="<?= $labor['id'] ?>" selected>
              <?= htmlspecialchars($labor['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">Dari Tanggal</label>
        <input type="date" id="filterDateFrom" class="form-control">
      </div>
      <div class="col-md-2 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">Sampai Tanggal</label>
        <input type="date" id="filterDateTo" class="form-control">
      </div>
      <div class="col-md-2 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">&nbsp;</label>
        <a href="history.php" class="btn btn-secondary w-100">
          <i class="fas fa-redo"></i> Reset
        </a>
      </div>
    </div>
    <div class="filter-buttons">
      <a href="dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <button class="btn btn-export btn-export-excel" onclick="exportToExcel()">
        <i class="fas fa-file-excel"></i> Export Excel
      </button>
      <button class="btn btn-export btn-export-pdf" onclick="exportToPDF()">
        <i class="fas fa-file-pdf"></i> Export PDF
      </button>
    </div>
  </div>

  <div class="table-card">
    <h4><i class="fas fa-list"></i> Hasil Pencarian</h4>
    <div id="tableContainer">
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Silakan gunakan filter untuk melihat data</p>
        <p style="font-size: 14px; font-weight: 400; margin: 0;">Masukkan kriteria pencarian untuk menampilkan hasil.</p>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

// AJAX Search Function
function performSearch(page = 1) {
  const search = document.getElementById('searchInput').value;
  const labor = document.getElementById('laborSelect').value;
  const filterDateFrom = document.getElementById('filterDateFrom').value;
  const filterDateTo = document.getElementById('filterDateTo').value;

  // Prepare query parameters
  const params = new URLSearchParams({
    ajax: '1',
    search: search,
    labor: labor,
    date_from: filterDateFrom,
    date_to: filterDateTo,
    page: page
  });

  // Show loading state
  const container = document.getElementById('tableContainer');
  container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--text-secondary);">Memuat data...</p></div>';

  // Make AJAX request
  fetch('history.php?' + params.toString())
    .then(response => {
      if(!response.ok) {
        throw new Error('HTTP error, status: ' + response.status);
      }
      return response.text();
    })
    .then(text => {
      try {
        const data = JSON.parse(text);
        if(data.success) {
          container.innerHTML = data.html + (data.pagination || '');
        } else {
          console.error('Error response:', data);
          container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan</p><p style="font-size: 14px; font-weight: 400; margin: 0; white-space: pre-wrap; text-align: left; font-family: monospace; color: #ef4444;">' + htmlEscape(data.error || 'Unknown error') + '</p></div>';
        }
      } catch(parseError) {
        console.error('JSON Parse Error:', parseError);
        console.error('Response was:', text);
        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan parsing</p><p style="font-size: 14px; font-weight: 400; margin: 0; white-space: pre-wrap; text-align: left; font-family: monospace; color: #ef4444;">Invalid JSON response</p></div>';
      }
    })
    .catch(error => {
      console.error('Fetch Error:', error);
      container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan saat memuat data</p><p style="font-size: 14px; font-weight: 400; margin: 0; color: #ef4444;">' + error.message + '</p></div>';
    });
}

// Go to specific page
function goToPage(page) {
  performSearch(page);
}

// Export to Excel
function exportToExcel() {
  const search = document.getElementById('searchInput').value;
  const labor = document.getElementById('laborSelect').value;
  const filterDateFrom = document.getElementById('filterDateFrom').value;
  const filterDateTo = document.getElementById('filterDateTo').value;
  const button = event.target;
  const originalText = button.innerHTML;
  
  // Prepare query parameters
  const params = new URLSearchParams({
    export: 'excel',
    search: search,
    labor: labor,
    date_from: filterDateFrom,
    date_to: filterDateTo
  });
  
  // Show loading message
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat file...';
  button.disabled = true;
  
  // Download the file
  window.location.href = '../backend/api_export_history.php?' + params.toString();
  
  // Reset button immediately
  setTimeout(() => {
    button.innerHTML = originalText;
    button.disabled = false;
  }, 500);
}

// Export to PDF
function exportToPDF() {
  const search = document.getElementById('searchInput').value;
  const labor = document.getElementById('laborSelect').value;
  const filterDateFrom = document.getElementById('filterDateFrom').value;
  const filterDateTo = document.getElementById('filterDateTo').value;
  const button = event.target;
  const originalText = button.innerHTML;
  
  // Prepare query parameters
  const params = new URLSearchParams({
    export: 'pdf',
    search: search,
    labor: labor,
    date_from: filterDateFrom,
    date_to: filterDateTo
  });
  
  // Show loading message
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat file...';
  button.disabled = true;
  
  // Download the file
  const link = document.createElement('a');
  link.href = '../backend/api_export_history.php?' + params.toString();
  link.target = '_blank';
  link.click();
  
  // Reset button immediately
  setTimeout(() => {
    button.innerHTML = originalText;
    button.disabled = false;
  }, 500);
}

// Helper function to escape HTML
function htmlEscape(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Add event listeners for real-time search
document.getElementById('searchInput').addEventListener('keyup', function() {
  performSearch();
});

document.getElementById('laborSelect').addEventListener('change', function() {
  performSearch();
});

document.getElementById('filterDateFrom').addEventListener('change', function() {
  performSearch();
});

document.getElementById('filterDateTo').addEventListener('change', function() {
  performSearch();
});

// AUTO-TRIGGER search saat page load
document.addEventListener('DOMContentLoaded', function() {
  performSearch();
});
</script>

<?php include '../asset/footer.php'; ?>
