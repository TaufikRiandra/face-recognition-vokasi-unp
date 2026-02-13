<?php
include '../asset/header.php';
include '../backend/helpers_attendance.php';

// Get today's date
$today = date('Y-m-d');

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

// Get selected labor filter from GET parameter
//$selected_labor = isset($_GET['labor']) ? intval($_GET['labor']) : 0;

// Force gunakan labor spesifik (jangan dari GET parameter)
$selected_labor = 3; // Ubah ke ID labor yang ingin

// Pagination setup
$items_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if($current_page < 1) $current_page = 1;

// Build query with optional labor filter (with table alias for main query)
$where_clause = "DATE(al.created_at) = '$today'";
if($selected_labor > 0) {
  $where_clause .= " AND al.labor_id = $selected_labor";
}

// Same where clause but without table alias for statistics queries
$where_clause_stats = "DATE(created_at) = '$today'";
if($selected_labor > 0) {
  $where_clause_stats .= " AND labor_id = $selected_labor";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM attendance_logs al WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_records / $items_per_page);

// Validate current page
if($current_page > $total_pages && $total_pages > 0) {
  $current_page = $total_pages;
}

// Calculate offset
$offset = ($current_page - 1) * $items_per_page;

// Get today's attendance logs with user details (with LIMIT and OFFSET)
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
  WHERE $where_clause
  ORDER BY al.created_at DESC
  LIMIT $items_per_page OFFSET $offset
";

$result = mysqli_query($conn, $query);
if(!$result) {
  die("Query error: " . mysqli_error($conn));
}
$attendance_logs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count statistics with labor filter
$total_in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance_logs WHERE $where_clause_stats AND status='IN'"))['total'] ?? 0;
$total_out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance_logs WHERE $where_clause_stats AND status='OUT'"))['total'] ?? 0;
$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM attendance_logs WHERE $where_clause_stats AND user_id IS NOT NULL"))['total'] ?? 0;

// Get unique visitors today
$unique_visitors_query = "
  SELECT 
    COUNT(DISTINCT user_id) as total_unik
  FROM attendance_logs 
  WHERE $where_clause_stats
";
$total_unik = mysqli_fetch_assoc(mysqli_query($conn, $unique_visitors_query))['total_unik'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi Pengunjung Labor</title>
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

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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

  .page-header {
    margin-bottom: 40px;
    animation: slideInDown 0.4s ease;
  }

  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
  }

  .page-subtitle {
    font-size: 14px;
    color: var(--text-secondary);
  }

  .welcome-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    transition: all 0.2s;
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
    margin-bottom: 0;
  }

  .welcome-card p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
  }

  .stat-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    transition: all 0.2s;
    animation: slideInUp 0.5s ease backwards;
  }

  .stat-card:nth-child(1) { animation-delay: 0.2s; }
  .stat-card:nth-child(2) { animation-delay: 0.3s; }
  .stat-card:nth-child(3) { animation-delay: 0.4s; }
  .stat-card:nth-child(4) { animation-delay: 0.5s; }

  .stat-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 20px;
  }

  .stat-card:nth-child(1) .stat-icon {
    background-color: #fef3c7;
    color: #92400e;
  }

  .stat-card:nth-child(2) .stat-icon {
    background-color: #fee2e2;
    color: #dc2626;
  }

  .stat-card:nth-child(3) .stat-icon {
    background-color: #dbeafe;
    color: #1e40af;
  }

  .stat-card:nth-child(4) .stat-icon {
    background-color: #dcfce7;
    color: #16a34a;
  }

  .stat-title {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 6px;
    font-weight: 500;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
  }

  .table-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    margin-bottom: 40px;
    animation: slideInUp 0.5s ease 0.6s both;
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

  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
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

  .form-select {
    border-color: var(--border-color);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    background-color: var(--bg-primary);
    cursor: pointer;
    transition: all 0.2s;
  }

  .form-select:hover {
    border-color: var(--primary);
  }

  .form-select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
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
    text-decoration: none;
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
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
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

  @media (max-width: 1200px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 768px) {
    .main-content {
      margin-left: 0;
      padding: 20px;
    }

    .welcome-card h2 {
      font-size: 24px;
    }

    .stats-grid {
      grid-template-columns: 1fr;
    }

    .page-title {
      font-size: 24px;
    }

    .table {
      font-size: 13px;
    }

    .table thead th,
    .table tbody td {
      padding: 10px 8px;
    }
  }

  /* Modal Styles */
  .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
  }

  .modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: slideInDown 0.3s ease;
    position: relative;
  }

  .modal-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
  }

  .modal-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
  }

  .modal-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
  }

  .modal-form-group {
    margin-bottom: 20px;
  }

  .modal-form-group label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
    margin-bottom: 8px;
    display: block;
  }

  .modal-form-group input {
    width: 100%;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: var(--text-primary);
    background-color: var(--bg-primary);
    transition: all 0.2s;
  }

  .modal-form-group input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
  }

  .modal-footer {
    display: flex;
    gap: 10px;
    margin-top: 25px;
  }

  .modal-btn {
    flex: 1;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .modal-btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
  }

  .modal-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }

  .modal-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .modal-btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 2px solid var(--border-color);
  }

  .modal-btn-secondary:hover {
    border-color: var(--primary);
  }

  .form-error {
    color: var(--danger);
    font-size: 12px;
    margin-top: 5px;
    display: none;
  }

  .form-error.show {
    display: block;
  }
</style>
</head>

<body>

<div class="main-content">

  <div class="welcome-card">
    <h2><i class="fa-solid fa-users-viewfinder"></i> Absensi Pengunjung Labor</h2>
    <p>Pantau dan kelola kehadiran pengunjung labor menggunakan teknologi face recognition</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-sign-in-alt"></i></div>
      <div class="stat-title">Total Masuk</div>
      <div class="stat-value"><?= $total_in ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-sign-out-alt"></i></div>
      <div class="stat-title">Total Keluar</div>
      <div class="stat-value"><?= $total_out ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-title">Pengunjung Unik</div>
      <div class="stat-value"><?= $total_unik ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
      <div class="stat-title">Total Kehadiran</div>
      <div class="stat-value"><?= $total_mahasiswa ?></div>
    </div>
  </div>

  <div class="table-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
      <h4 style="margin: 0;"><i class="fas fa-list"></i> Daftar Absensi Hari Ini</h4>
      <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <select id="laborFilter" class="form-select" style="width: auto; min-width: 200px;" disabled>
          <?php foreach($labor_list as $labor): ?>
            <option value="<?= $labor['id'] ?>" selected>
              <?= htmlspecialchars($labor['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button id="btnTambahUser" class="btn btn-primary" style="white-space: nowrap;">
          <i class="fas fa-user-plus"></i> Tambah User
        </button>
        <a href="manage_users.php" class="btn btn-primary" style="white-space: nowrap;">
          <i class="fas fa-users-cog"></i> Kelola User
        </a>
        <a href="history.php" class="btn btn-primary" style="white-space: nowrap;">
          <i class="fas fa-history"></i> Riwayat
        </a>
        <a href="face-capture.php" class="btn btn-primary" style="white-space: nowrap;">
          <i class="fas fa-plus-circle"></i> Input Absensi Baru
        </a>
      </div>
    </div>

    <?php if(count($attendance_logs) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th width="4%">No</th>
              <th width="24%">Nama</th>
              <th width="11%">NIM</th>
              <th width="10%">Tipe</th>
              <th width="10%">Status</th>
              <th width="17%">Keterangan</th>
              <th width="24%">Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($attendance_logs as $index => $log): ?>
              <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td>
                  <div class="visitor-name">
                    <?php if($log['user_nama']): ?>
                      <?= htmlspecialchars($log['user_nama']) ?>
                    <?php else: ?>
                      <span style="color: #ef4444; font-style: italic;"><?= htmlspecialchars($log['stored_user_nama'] ?? 'Unknown') ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="visitor-type">
                    <i class="fas fa-graduation-cap"></i> Mahasiswa
                  </div>
                </td>
                <td>
                  <?php if($log['user_nama']): ?>
                    <span style="font-weight: 600; color: var(--text-secondary);"><?= htmlspecialchars($log['nim']) ?></span>
                  <?php else: ?>
                    <span style="color: #ef4444; font-style: italic;">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-mahasiswa">Mahasiswa</span>
                </td>
                <td>
                  <?php if($log['status'] == 'IN'): ?>
                    <span class="badge-in">
                      <i class="fas fa-arrow-right-to-bracket"></i> Masuk
                    </span>
                  <?php else: ?>
                    <span class="badge-out">
                      <i class="fas fa-arrow-right-from-bracket"></i> Keluar
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= getKeteranganHTML($log['keterangan'] ?? 'normal') ?>
                </td>
                <td>
                  <div class="time-badge">
                    <i class="fas fa-clock"></i> 
                    <?= date('H:i:s', strtotime($log['created_at'])) ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
        <div class="pagination-info">
          Halaman <strong><?= $current_page ?></strong> dari <strong><?= $total_pages ?></strong> | Total: <strong><?= $total_records ?></strong> data
        </div>
        <div class="pagination-controls">
          <?php if($current_page > 1): ?>
            <a href="?page=1" class="pagination-btn pagination-btn-first" title="Halaman Pertama">
              <i class="fas fa-chevron-left"></i> Pertama
            </a>
            <a href="?page=<?= $current_page - 1 ?>" class="pagination-btn pagination-btn-prev" title="Halaman Sebelumnya">
              <i class="fas fa-chevron-left"></i> Sebelumnya
            </a>
          <?php else: ?>
            <button class="pagination-btn pagination-btn-disabled" disabled>
              <i class="fas fa-chevron-left"></i> Pertama
            </button>
            <button class="pagination-btn pagination-btn-disabled" disabled>
              <i class="fas fa-chevron-left"></i> Sebelumnya
            </button>
          <?php endif; ?>

          <div class="pagination-pages">
            <?php
            $max_pages_display = 5;
            $start_page = max(1, $current_page - floor($max_pages_display / 2));
            $end_page = min($total_pages, $start_page + $max_pages_display - 1);
            
            if($end_page - $start_page + 1 < $max_pages_display) {
              $start_page = max(1, $end_page - $max_pages_display + 1);
            }
            
            if($start_page > 1) {
              echo '<span class="pagination-ellipsis">...</span>';
            }
            
            for($i = $start_page; $i <= $end_page; $i++) {
              if($i == $current_page) {
                echo '<span class="pagination-page-active">' . $i . '</span>';
              } else {
                echo '<a href="?page=' . $i . '" class="pagination-page">' . $i . '</a>';
              }
            }
            
            if($end_page < $total_pages) {
              echo '<span class="pagination-ellipsis">...</span>';
            }
            ?>
          </div>

          <?php if($current_page < $total_pages): ?>
            <a href="?page=<?= $current_page + 1 ?>" class="pagination-btn pagination-btn-next" title="Halaman Berikutnya">
              Berikutnya <i class="fas fa-chevron-right"></i>
            </a>
            <a href="?page=<?= $total_pages ?>" class="pagination-btn pagination-btn-last" title="Halaman Terakhir">
              Terakhir <i class="fas fa-chevron-right"></i>
            </a>
          <?php else: ?>
            <button class="pagination-btn pagination-btn-disabled" disabled>
              Berikutnya <i class="fas fa-chevron-right"></i>
            </button>
            <button class="pagination-btn pagination-btn-disabled" disabled>
              Terakhir <i class="fas fa-chevron-right"></i>
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Tidak ada data absensi</p>
        <p style="font-size: 14px; font-weight: 400; margin: 0;">Belum ada pengunjung yang tercatat hari ini. Klik tombol "Input Absensi Baru" untuk memulai.</p>
      </div>
    <?php endif; ?>
  </div></div>

</div>

<!-- Modal Tambah User -->
<div id="modalTambahUser" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-icon">
        <i class="fas fa-user-plus"></i>
      </div>
      <h3>Tambah User Baru</h3>
    </div>

    <form id="formTambahUser">
      <div class="modal-form-group">
        <label for="inputNama">Nama Lengkap</label>
        <input type="text" id="inputNama" name="nama" placeholder="Masukkan nama lengkap" required>
        <div class="form-error" id="errorNama"></div>
      </div>

      <div class="modal-form-group">
        <label for="inputNIM">NIM</label>
        <input type="text" id="inputNIM" name="nim" placeholder="Masukkan NIM" required>
        <div class="form-error" id="errorNIM"></div>
      </div>

      <div class="modal-footer">
        <button type="button" id="btnBatalUser" class="modal-btn modal-btn-secondary">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" id="btnSimpanUser" class="modal-btn modal-btn-primary">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Modal Management
const modalTambahUser = document.getElementById('modalTambahUser');
const formTambahUser = document.getElementById('formTambahUser');
const btnTambahUser = document.getElementById('btnTambahUser');
const btnBatalUser = document.getElementById('btnBatalUser');
const btnSimpanUser = document.getElementById('btnSimpanUser');
const inputNama = document.getElementById('inputNama');
const inputNIM = document.getElementById('inputNIM');

let formHasChanged = false;
let isSubmitting = false;

// Track form changes
function setupFormChangeTracking() {
  formTambahUser.addEventListener('input', function() {
    formHasChanged = (inputNama.value.trim() || inputNIM.value.trim());
  });
}

// Open modal
function openModalTambahUser() {
  formHasChanged = false;
  formTambahUser.reset();
  clearAllErrors();
  modalTambahUser.classList.add('active');
  inputNama.focus();
  setupFormChangeTracking();
}

// Close modal with conditions
function closeModalTambahUser() {
  if(formHasChanged && !isSubmitting) {
    // Show browser confirmation
    const confirmation = confirm('Formulir belum disimpan. Apakah Anda yakin ingin menutup?');
    if(!confirmation) return;
  }
  modalTambahUser.classList.remove('active');
  formTambahUser.reset();
  formHasChanged = false;
  clearAllErrors();
}

// Clear all errors
function clearAllErrors() {
  document.getElementById('errorNama').textContent = '';
  document.getElementById('errorNama').classList.remove('show');
  document.getElementById('errorNIM').textContent = '';
  document.getElementById('errorNIM').classList.remove('show');
}

// Validate form
function validateForm() {
  clearAllErrors();
  let isValid = true;

  const nama = inputNama.value.trim();
  const nim = inputNIM.value.trim();

  if(!nama) {
    document.getElementById('errorNama').textContent = 'Nama tidak boleh kosong';
    document.getElementById('errorNama').classList.add('show');
    isValid = false;
  }

  if(!nim) {
    document.getElementById('errorNIM').textContent = 'NIM tidak boleh kosong';
    document.getElementById('errorNIM').classList.add('show');
    isValid = false;
  }

  return isValid;
}

// Submit form
formTambahUser.addEventListener('submit', async function(e) {
  e.preventDefault();

  if(!validateForm()) return;

  isSubmitting = true;
  btnSimpanUser.disabled = true;
  btnSimpanUser.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

  const formData = new FormData(formTambahUser);

  try {
    const response = await fetch('../backend/api_add_user.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if(data.success) {
      // Reset form and close modal
      formHasChanged = false;
      closeModalTambahUser();
      clearAllErrors();
      
      // Show success notification
      showSuccessNotification(data.message);
      
      // Reset button
      btnSimpanUser.disabled = false;
      btnSimpanUser.innerHTML = '<i class="fas fa-save"></i> Simpan';
      isSubmitting = false;
    } else {
      // Show error
      if(data.error.includes('NIM')) {
        document.getElementById('errorNIM').textContent = data.error;
        document.getElementById('errorNIM').classList.add('show');
      } else {
        alert('Error: ' + data.error);
      }
      isSubmitting = false;
      btnSimpanUser.disabled = false;
      btnSimpanUser.innerHTML = '<i class="fas fa-save"></i> Simpan';
    }
  } catch(error) {
    console.error('Error:', error);
    alert('Terjadi kesalahan saat menambah user');
    isSubmitting = false;
    btnSimpanUser.disabled = false;
    btnSimpanUser.innerHTML = '<i class="fas fa-save"></i> Simpan';
  }
});

// Show success notification
function showSuccessNotification(message) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 2000;
    animation: slideInRight 0.3s ease;
  `;
  notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// Event listeners
btnTambahUser.addEventListener('click', openModalTambahUser);
btnBatalUser.addEventListener('click', closeModalTambahUser);

// Close modal when clicking outside (with confirmation)
modalTambahUser.addEventListener('click', function(e) {
  if(e.target === modalTambahUser) {
    closeModalTambahUser();
  }
});

// Prevent closing modal with Escape key if form has changes
document.addEventListener('keydown', function(e) {
  if(e.key === 'Escape' && modalTambahUser.classList.contains('active')) {
    closeModalTambahUser();
  }
});

// Labor filter dropdown - DISABLED (labor sudah fixed)
// document.getElementById('laborFilter').addEventListener('change', function() {
//   const laborId = this.value;
//   if(laborId === '0') {
//     window.location.href = window.location.pathname;
//   } else {
//     window.location.href = window.location.pathname + '?labor=' + laborId;
//   }
// });

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
  @keyframes slideInRight {
    from { opacity: 0; transform: translateX(100px); }
    to { opacity: 1; transform: translateX(0); }
  }
`;
document.head.appendChild(style);
</script>

<?php include '../asset/footer.php'; ?>
