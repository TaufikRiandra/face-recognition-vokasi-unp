<?php
include 'header.php';

// Check if this is an AJAX request
if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
  header('Content-Type: application/json');
  
  // Get filter parameters
  $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
  $selected_labor = isset($_GET['labor']) ? intval($_GET['labor']) : 0;
  $filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';

  // Build where clause
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

  // Get attendance logs
  $query = "
    SELECT 
      al.id,
      al.user_id,
      COALESCE(al.labor_id, 0) as labor_id,
      al.status,
      al.confidence_score,
      al.created_at,
      u.nama as user_nama,
      u.nim,
      l.nama as labor_nama
    FROM attendance_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN labor l ON al.labor_id = l.id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT 1000
  ";

  $result = mysqli_query($conn, $query);
  if(!$result) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
  }
  
  $attendance_logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
  
  // Generate table HTML
  if(count($attendance_logs) > 0) {
    $html = '<div class="results-info"><strong>' . count($attendance_logs) . '</strong> data ditemukan</div>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-hover">';
    $html .= '<thead><tr><th width="5%">No</th><th width="25%">Nama</th><th width="15%">Tipe</th><th width="15%">Labor</th><th width="12%">Status</th><th width="28%">Waktu</th></tr></thead><tbody>';
    
    foreach($attendance_logs as $index => $log) {
      $html .= '<tr>';
      $html .= '<td class="text-center">' . ($index + 1) . '</td>';
      $html .= '<td>';
      $html .= '<div class="visitor-name">';
      $html .= htmlspecialchars($log['user_nama']) . ' (' . htmlspecialchars($log['nim']) . ')';
      $html .= '</div>';
      $html .= '<div class="visitor-type">';
      $html .= '<i class="fas fa-graduation-cap"></i> Mahasiswa';
      $html .= '</div>';
      $html .= '</td>';
      
      $html .= '<td><span class="badge badge-mahasiswa">Mahasiswa</span></td>';
      
      $html .= '<td><span style="font-weight: 600; color: var(--primary);">' . htmlspecialchars($log['labor_nama'] ?? '-') . '</span></td>';
      
      if($log['status'] == 'IN') {
        $html .= '<td><span class="badge-in"><i class="fas fa-arrow-right-to-bracket"></i> Masuk</span></td>';
      } else {
        $html .= '<td><span class="badge-out"><i class="fas fa-arrow-right-from-bracket"></i> Keluar</span></td>';
      }
      
      $html .= '<td><div class="time-badge"><i class="fas fa-calendar"></i> ' . date('d-m-Y', strtotime($log['created_at'])) . '<br><i class="fas fa-clock"></i> ' . date('H:i:s', strtotime($log['created_at'])) . '</div></td>';
      $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
  } else {
    $html = '<div class="empty-state"><i class="fas fa-search"></i><p>Data tidak ditemukan</p><p style="font-size: 14px; font-weight: 400; margin: 0;">Tidak ada data yang sesuai dengan filter Anda. Coba ubah kriteria pencarian.</p></div>';
  }
  
  echo json_encode(['success' => true, 'html' => $html, 'count' => count($attendance_logs)]);
  exit;
}

// Get all labor for dropdown
$labor_query = mysqli_query($conn, "SELECT id, nama FROM labor ORDER BY nama");
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

  .table-card {
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
    background-color: var(--danger);
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
        <select id="laborSelect" class="form-select">
          <option value="0">Semua Labor</option>
          <?php foreach($labor_list as $labor): ?>
            <option value="<?= $labor['id'] ?>">
              <?= htmlspecialchars($labor['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">Tanggal</label>
        <input type="date" id="filterDate" class="form-control">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label" style="font-weight: 600; color: var(--text-primary); font-size: 13px;">&nbsp;</label>
        <a href="history.php" class="btn btn-secondary w-100">
          <i class="fas fa-redo"></i> Reset
        </a>
      </div>
    </div>
    <div class="filter-buttons">
      <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
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
function performSearch() {
  const search = document.getElementById('searchInput').value;
  const labor = document.getElementById('laborSelect').value;
  const filterDate = document.getElementById('filterDate').value;

  // Prepare query parameters
  const params = new URLSearchParams({
    ajax: '1',
    search: search,
    labor: labor,
    filter_date: filterDate
  });

  // Show loading state
  const container = document.getElementById('tableContainer');
  container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--text-secondary);">Memuat data...</p></div>';

  // Make AJAX request
  fetch('history.php?' + params.toString())
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        container.innerHTML = data.html;
      } else {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan</p><p style="font-size: 14px; font-weight: 400; margin: 0;">Error: ' + htmlEscape(data.error) + '</p></div>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan saat memuat data</p><p style="font-size: 14px; font-weight: 400; margin: 0;">Silakan coba lagi.</p></div>';
    });
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

document.getElementById('filterDate').addEventListener('change', function() {
  performSearch();
});
</script>
</body>
</html>
