<?php
session_start();
include '../../config/database.php';

if(!isset($_SESSION['admin_id'])){
  header("Location: ../login.php");
  exit;
}

$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);

if(!$a){
  die("Admin tidak ditemukan");
}

// Get today's date
$today = date('Y-m-d');

// Get all labor for dropdown
$labor_query = mysqli_query($conn, "SELECT id, nama FROM labor ORDER BY nama");
$labor_list = mysqli_fetch_all($labor_query, MYSQLI_ASSOC);

// Get selected labor filter from GET parameter
$selected_labor = isset($_GET['labor']) ? intval($_GET['labor']) : 0;

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

// Get today's attendance logs with user details
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
  WHERE $where_clause
  ORDER BY al.created_at DESC
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
    margin-left: 250px;
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
</style>
</head>

<body>

<?php include '../../assets/admin_sidebar.php'; ?>

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
        <select id="laborFilter" class="form-select" style="width: auto; min-width: 200px;">
          <option value="0">Semua Labor</option>
          <?php foreach($labor_list as $labor): ?>
            <option value="<?= $labor['id'] ?>" <?= $selected_labor == $labor['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($labor['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
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
              <th width="5%">No</th>
              <th width="30%">Nama</th>
              <th width="15%">Tipe</th>
              <th width="15%">Status</th>
              <th width="35%">Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($attendance_logs as $index => $log): ?>
              <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td>
                  <div class="visitor-name">
                    <?= htmlspecialchars($log['user_nama']) . ' (' . htmlspecialchars($log['nim']) . ')' ?>
                  </div>
                  <div class="visitor-type">
                    <i class="fas fa-graduation-cap"></i> Mahasiswa
                  </div>
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
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Tidak ada data absensi</p>
        <p style="font-size: 14px; font-weight: 400; margin: 0;">Belum ada pengunjung yang tercatat hari ini. Klik tombol "Input Absensi Baru" untuk memulai.</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Labor filter dropdown
document.getElementById('laborFilter').addEventListener('change', function() {
  const laborId = this.value;
  if(laborId === '0') {
    window.location.href = window.location.pathname;
  } else {
    window.location.href = window.location.pathname + '?labor=' + laborId;
  }
});

// Load sidebar state from localStorage
if(localStorage.getItem('sidebarCollapsed')==='true') {
  document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});
</script>
</body>
</html>
