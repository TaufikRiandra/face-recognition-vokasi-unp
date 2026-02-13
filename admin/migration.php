<?php
session_start();
include '../backend/koneksi.php';

// Check if admin
if(!isset($_SESSION['admin_id'])) {
  header('Location: ../admin/login.php');
  exit;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = mysqli_query($conn, "SELECT username FROM admin WHERE id = $admin_id");
if(!$admin_query || mysqli_num_rows($admin_query) === 0) {
  session_destroy();
  header('Location: ../admin/login.php');
  exit;
}
$admin = mysqli_fetch_assoc($admin_query);

// Check if keterangan column exists
$check_column = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME='attendance_logs' AND COLUMN_NAME='keterangan'";
$check_result = mysqli_query($conn, $check_column);
$keterangan_exists = mysqli_num_rows($check_result) > 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Migration - Admin Panel</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #f97316;
      --primary-dark: #ea580c;
      --text-primary: #1a1a1a;
      --text-secondary: #666;
      --border: #e0e0e0;
      --bg-light: #fafafa;
    }
    
    body {
      background: linear-gradient(135deg, var(--bg-light) 0%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .container-custom {
      max-width: 800px;
    }
    
    .card {
      border: none;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-radius: 12px;
      margin-bottom: 20px;
    }
    
    .card-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 20px;
      border-radius: 12px 12px 0 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .card-header h2 {
      margin: 0;
      font-size: 24px;
      font-weight: 600;
    }
    
    .card-body {
      padding: 30px;
    }
    
    .status-box {
      background: #f0f4f8;
      border-left: 4px solid var(--primary);
      padding: 16px;
      border-radius: 8px;
      margin: 20px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .status-box.success {
      background: #d1fae5;
      border-left-color: #10b981;
      color: #065f46;
    }
    
    .status-box.error {
      background: #fee2e2;
      border-left-color: #ef4444;
      color: #991b1b;
    }
    
    .status-box.info {
      background: #dbeafe;
      border-left-color: #3b82f6;
      color: #1e40af;
    }
    
    .status-box i {
      font-size: 20px;
    }
    
    .status-content {
      flex: 1;
    }
    
    .status-content strong {
      display: block;
      margin-bottom: 4px;
    }
    
    .status-content p {
      margin: 0;
      font-size: 14px;
    }
    
    .btn-primary-custom {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      border: none;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-primary-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
      color: white;
      text-decoration: none;
    }
    
    .btn-primary-custom:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
    
    .migration-info {
      background: #fffbeb;
      border: 1px solid #fcd34d;
      border-radius: 8px;
      padding: 16px;
      margin: 20px 0;
      color: #92400e;
    }
    
    .migration-info h5 {
      margin-bottom: 12px;
      color: #d97706;
    }
    
    .migration-info ul {
      margin: 0;
      padding-left: 20px;
    }
    
    .migration-info li {
      margin: 6px 0;
      font-size: 14px;
    }
    
    .progress-area {
      margin-top: 20px;
      display: none;
    }
    
    .progress-area.show {
      display: block;
    }
    
    .log-output {
      background: #1a1a1a;
      color: #00ff00;
      padding: 16px;
      border-radius: 8px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      max-height: 300px;
      overflow-y: auto;
      margin-top: 20px;
    }
    
    .log-line {
      margin: 4px 0;
    }
    
    .log-success {
      color: #10b981;
    }
    
    .log-error {
      color: #ef4444;
    }
    
    .table-requirements {
      font-size: 14px;
      margin: 20px 0;
    }
    
    .table-requirements thead {
      background: #f3f4f6;
      font-weight: 600;
    }
    
    .table-requirements tbody tr:hover {
      background: #f9fafb;
    }
    
    .check-icon {
      color: #10b981;
      font-size: 18px;
    }
    
    .x-icon {
      color: #ef4444;
      font-size: 18px;
    }
  </style>
</head>
<body>
  <div class="container-custom">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-database"></i>
        <h2>Database Migration</h2>
      </div>
      
      <div class="card-body">
        <div class="status-box <?= $keterangan_exists ? 'success' : 'info' ?>">
          <i class="fas <?= $keterangan_exists ? 'fa-check-circle' : 'fa-info-circle' ?>"></i>
          <div class="status-content">
            <strong><?= $keterangan_exists ? '✓ Migration Done' : '⚠ Migration Pending' ?></strong>
            <p><?= $keterangan_exists ? 'Attendance keterangan column sudah ada di database' : 'Siap untuk menjalankan migration' ?></p>
          </div>
        </div>
        
        <div class="migration-info">
          <h5><i class="fas fa-info-circle"></i> Migration Details</h5>
          <p><strong>Tujuan:</strong> Menambahkan kolom "keterangan" untuk tracking attendance status (terlambat/lembur/tepat waktu)</p>
          <p><strong>Perubahan Database:</strong></p>
          <ul>
            <li><strong>attendance_logs:</strong> Tambah kolom keterangan (VARCHAR 50)</li>
            <li><strong>labor:</strong> Tambah jam_masuk_standar, jam_pulang_standar, toleransi_terlambat</li>
            <li><strong>TRIGGER:</strong> Auto-calculate keterangan saat insert attendance</li>
            <li><strong>Stored Procedure:</strong> Helper function untuk report</li>
          </ul>
        </div>
        
        <div class="table-requirements">
          <h5>Migration Checklist</h5>
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Item</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>attendance_logs.keterangan column</td>
                <td><i class="fas fa-<?= $keterangan_exists ? 'check-circle check-icon' : 'times-circle x-icon' ?>"></i></td>
              </tr>
              <tr>
                <td>labor.jam_masuk_standar column</td>
                <td><i class="fas fa-<?= $keterangan_exists ? 'check-circle check-icon' : 'times-circle x-icon' ?>"></i></td>
              </tr>
              <tr>
                <td>Trigger tr_attendance_keterangan_insert</td>
                <td><i class="fas fa-<?= $keterangan_exists ? 'check-circle check-icon' : 'times-circle x-icon' ?>"></i></td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <?php if(!$keterangan_exists): ?>
        <div style="margin-top: 30px;">
          <h5>Execute Migration</h5>
          <p style="color: var(--text-secondary);">Klik tombol di bawah untuk menjalankan migration SQL ke database Anda</p>
          
          <button class="btn-primary-custom" id="executeMigrationBtn" onclick="executeMigration()">
            <i class="fas fa-rocket"></i>
            Jalankan Migration
          </button>
          
          <div class="progress-area" id="progressArea">
            <div class="spinner-border text-warning" role="status" style="margin-right: 10px;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <span>Mengeksekusi migration...</span>
            
            <div class="log-output" id="logOutput">
              <div class="log-line">- Inisialisasi...</div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div style="margin-top: 30px;">
          <div class="status-box success">
            <i class="fas fa-check-circle"></i>
            <div class="status-content">
              <strong>Migration Sudah Berjalan</strong>
              <p>Semua perubahan database telah diterapkan. Sistem attendance tracking dengan keterangan (terlambat/lembur) sudah aktif.</p>
            </div>
          </div>
          
          <p style="margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
            <i class="fas fa-info-circle"></i>
            Dashboard, History, dan Export sekarang menampilkan kolom keterangan dengan status otomatis.
          </p>
          
          <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
            <h5 style="margin-bottom: 15px;">Data Lama Belum Terupdate?</h5>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
              Jika data attendance lama masih menunjukkan status salah, gunakan tombol di bawah untuk memperbaiki dan mengisi ulang keterangan.
            </p>
            <button class="btn-primary-custom" id="fixMigrationBtn" onclick="fixMigration()" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
              <i class="fas fa-wrench"></i>
              Perbaiki & Isi Ulang Data
            </button>
            
            <div class="progress-area" id="fixProgressArea">
              <div class="spinner-border text-info" role="status" style="margin-right: 10px;">
                <span class="visually-hidden">Loading...</span>
              </div>
              <span>Memproses perbaikan data...</span>
              
              <div class="log-output" id="fixLogOutput">
                <div class="log-line">- Inisialisasi...</div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
  
  <script>
    function executeMigration() {
      const btn = document.getElementById('executeMigrationBtn');
      const progressArea = document.getElementById('progressArea');
      const logOutput = document.getElementById('logOutput');
      
      // Disable button and show progress
      btn.disabled = true;
      progressArea.classList.add('show');
      logOutput.innerHTML = '<div class="log-line">- Starting migration...</div>';
      
      // AJAX request to execute migration
      fetch('../backend/execute_migration.php')
        .then(response => {
          // Check if response is actually JSON
          const contentType = response.headers.get('content-type');
          if(!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON. Got: ' + contentType);
          }
          return response.json();
        })
        .then(data => {
          const timestamp = new Date().toLocaleTimeString();
          let logLine = '';
          
          if(data.status === 'success') {
            logLine = `<div class="log-line log-success">[${timestamp}] ✓ Migration berhasil!</div>`;
            logLine += `<div class="log-line log-success">[${timestamp}] Executed: ${data.executed} statements</div>`;
            logLine += `<div class="log-line">- Refresh halaman untuk melihat perubahan...</div>`;
            
            logOutput.innerHTML += logLine;
            
            // Show success message after 2 seconds
            setTimeout(() => {
              location.reload();
            }, 2000);
          } else {
            logLine = `<div class="log-line log-error">[${timestamp}] ✗ Migration gagal!</div>`;
            logLine += `<div class="log-line log-error">[${timestamp}] Executed: ${data.executed}, Failed: ${data.failed}</div>`;
            logLine += `<div class="log-line log-error">[${timestamp}] ${data.message}</div>`;
            
            if(data.errors && data.errors.length > 0) {
              data.errors.forEach(err => {
                logLine += `<div class="log-line log-error">Error on statement ${err.statement_num}: ${err.error}</div>`;
              });
            }
            
            logOutput.innerHTML += logLine;
            btn.disabled = false;
          }
        })
        .catch(error => {
          const timestamp = new Date().toLocaleTimeString();
          let errorMsg = error.message || 'Unknown error';
          const logLine = `<div class="log-line log-error">[${timestamp}] ✗ Error: ${errorMsg}</div>`;
          logOutput.innerHTML += logLine;
          btn.disabled = false;
        });
    }

    function fixMigration() {
      const btn = document.getElementById('fixMigrationBtn');
      const progressArea = document.getElementById('fixProgressArea');
      const logOutput = document.getElementById('fixLogOutput');
      
      // Disable button and show progress
      btn.disabled = true;
      progressArea.classList.add('show');
      logOutput.innerHTML = '<div class="log-line">- Starting fix migration...</div>';
      
      // AJAX request to fix migration
      fetch('../backend/fix_migration.php')
        .then(response => {
          const contentType = response.headers.get('content-type');
          if(!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON. Got: ' + contentType);
          }
          return response.json();
        })
        .then(data => {
          const timestamp = new Date().toLocaleTimeString();
          let logLine = '';
          
          if(data.status === 'success') {
            logLine = `<div class="log-line log-success">[${timestamp}] ✓ Perbaikan berhasil!</div>`;
            logLine += `<div class="log-line log-success">[${timestamp}] ${data.message}</div>`;
            
            if(data.results && data.results.length > 0) {
              data.results.forEach(result => {
                logLine += `<div class="log-line log-success">[${timestamp}] ${result}</div>`;
              });
            }
            
            logLine += `<div class="log-line">- Refresh halaman untuk melihat perubahan...</div>`;
            logOutput.innerHTML += logLine;
            
            // Show success message after 2 seconds
            setTimeout(() => {
              location.reload();
            }, 2000);
          } else {
            logLine = `<div class="log-line log-error">[${timestamp}] ✗ Perbaikan gagal!</div>`;
            logLine += `<div class="log-line log-error">[${timestamp}] ${data.message}</div>`;
            
            if(data.results && data.results.length > 0) {
              data.results.forEach(result => {
                logLine += `<div class="log-line log-success">[${timestamp}] ${result}</div>`;
              });
            }
            
            logOutput.innerHTML += logLine;
            btn.disabled = false;
          }
        })
        .catch(error => {
          const timestamp = new Date().toLocaleTimeString();
          let errorMsg = error.message || 'Unknown error';
          const logLine = `<div class="log-line log-error">[${timestamp}] ✗ Error: ${errorMsg}</div>`;
          logOutput.innerHTML += logLine;
          btn.disabled = false;
        });
    }
  </script>
</body>
</html>
