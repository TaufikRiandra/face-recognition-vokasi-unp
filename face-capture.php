<?php
include 'header.php';

// Get all labor for dropdown
// $labor_query = mysqli_query($conn, "SELECT id, nama FROM labor ORDER BY nama");
// $labor_list = mysqli_fetch_all($labor_query, MYSQLI_ASSOC);

// Tentukan labor yang ingin digunakan (bisa dirubah)
$default_labor_id = 3; // Ubah angka ini untuk ganti labor
$labor_query = mysqli_query($conn, "SELECT id, nama FROM labor WHERE id = $default_labor_id");
$labor_list = mysqli_fetch_all($labor_query, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Input Absensi dengan Face Recognition</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script async src="https://docs.opencv.org/4.6.0/opencv.js"></script>

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  
  :root {
    --primary: #f59e0b;
    --primary-light: #fbbf24;
    --primary-dark: #d97706;
    --secondary: #fb923c;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e0e0e0;
    --hover-bg: #f9f9f9;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
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
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
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
    margin-bottom: 0;
  }

  .welcome-card p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
  }

  .capture-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    margin-bottom: 40px;
    animation: slideInDown 0.5s ease 0.2s both;
  }

  .capture-card h4 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .video-wrapper {
    position: relative;
    width: 100%;
    max-width: 500px;
    margin: 0 auto 25px;
    border-radius: 10px;
    overflow: hidden;
    border: 3px solid var(--border-color);
    background: #000;
  }

  #video {
    width: 100%;
    height: auto;
    transform: scaleX(-1);
    display: block;
  }

  #canvas-overlay {
    position: absolute;
    top: 0;
    left: 0;
    transform: scaleX(-1);
  }

  .controls-section {
    background: var(--bg-secondary);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
  }

  .control-group {
    margin-bottom: 15px;
  }

  .control-group:last-child {
    margin-bottom: 0;
  }

  .control-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
    display: block;
  }

  .form-select {
    border-color: var(--border-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    background-color: var(--bg-primary);
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
  }

  .form-select:hover {
    border-color: var(--primary);
  }

  .form-select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
  }

  .button-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    color: white;
  }

  .btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 2px solid var(--border-color);
  }

  .btn-secondary:hover {
    border-color: var(--primary);
    background: var(--bg-primary);
  }

  .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .status-section {
    background: var(--bg-secondary);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary);
  }

  .status-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
  }

  .status-text {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
  }

  .status-text.loading {
    color: var(--warning);
    animation: pulse 1.5s infinite;
  }

  .status-text.success {
    color: var(--success);
  }

  .status-text.error {
    color: var(--danger);
  }

  .status-text.unknown {
    color: var(--danger);
  }

  .status-icon {
    margin-right: 8px;
  }

  .confidence-display {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: var(--bg-primary);
    border-radius: 6px;
    border: 1px solid var(--border-color);
    margin-top: 10px;
  }

  .confidence-bar {
    flex: 1;
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
  }

  .confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--danger), var(--warning), var(--success));
    width: 0%;
    transition: width 0.3s ease;
  }

  .confidence-text {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    min-width: 50px;
    text-align: right;
  }

  /* User Info Card */
  .user-info-card {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(217, 119, 6, 0.05) 100%);
    border: 2px solid var(--primary);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    display: none;
    animation: slideInUp 0.4s ease;
  }

  .user-info-card.show {
    display: block;
  }

  .user-info-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
  }

  .user-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 24px;
  }

  .user-header-info h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 4px 0;
  }

  .user-header-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
  }

  .user-detail-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-top: 1px solid rgba(245, 158, 11, 0.2);
  }

  .user-detail-row:first-child {
    border-top: none;
    padding-top: 0;
  }

  .user-detail-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    width: 80px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .user-detail-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    flex: 1;
  }

  /* Toast Message */
  .toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;
    pointer-events: none;
  }

  .toast-message {
    background: white;
    border-left: 4px solid var(--success);
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInRight 0.3s ease;
    pointer-events: auto;
    max-width: 350px;
  }

  .toast-message.error {
    border-left-color: var(--danger);
  }

  .toast-message.info {
    border-left-color: var(--primary);
  }

  .toast-message i {
    font-size: 18px;
    color: var(--success);
    min-width: 20px;
  }

  .toast-message.error i {
    color: var(--danger);
  }

  .toast-message.info i {
    color: var(--primary);
  }

  .toast-text {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    flex: 1;
  }

  .toast-close {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 18px;
    padding: 0;
    display: flex;
    align-items: center;
  }

  .toast-close:hover {
    color: var(--text-primary);
  }

  @keyframes slideInRight {
    from {
      opacity: 0;
      transform: translateX(20px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* Loading Button Animation */
  .btn.loading {
    pointer-events: none;
  }

  .btn.loading .btn-text {
    display: none;
  }

  .btn.loading .btn-loader {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-loader {
    display: none;
  }

  .btn-loader i {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .modal-custom {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
  }

  .modal-custom.show {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content-custom {
    background-color: var(--bg-primary);
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: slideInDown 0.3s ease;
  }

  .modal-header-custom {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
  }

  .modal-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--warning) 0%, var(--primary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
  }

  .modal-header-custom h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
  }

  .modal-header-custom p {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 5px 0 0 0;
  }

  .modal-body-custom {
    margin-bottom: 25px;
    padding: 15px 0;
  }

  .modal-body-custom p {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 15px;
  }

  .modal-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
  }

  .option-button {
    padding: 20px;
    border: 2px solid var(--border-color);
    background: var(--bg-primary);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    color: var(--text-primary);
  }

  .option-button:hover {
    border-color: var(--primary);
    background: rgba(245, 158, 11, 0.05);
    transform: translateY(-2px);
  }

  .option-button.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
  }

  .option-icon {
    font-size: 24px;
    color: var(--primary);
    margin-bottom: 8px;
    display: block;
  }

  .option-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .option-desc {
    font-size: 12px;
    color: var(--text-secondary);
  }

  .modal-footer-custom {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .btn-modal {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
  }

  .btn-modal-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
  }

  .btn-modal-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }

  .btn-modal-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-modal-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 2px solid var(--border-color);
  }

  .btn-modal-secondary:hover {
    border-color: var(--text-secondary);
  }

  /* Search Modal Styles */
  .search-input-wrapper {
    position: relative;
    margin-bottom: 20px;
  }

  .search-input-wrapper input {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
  }

  .search-input-wrapper input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
  }

  .search-input-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    font-size: 14px;
  }

  .user-list {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    background: var(--bg-primary);
  }

  .user-item {
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .user-item:last-child {
    border-bottom: none;
  }

  .user-item:hover {
    background-color: rgba(245, 158, 11, 0.05);
    border-left: 4px solid var(--primary);
    padding-left: 11px;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    flex-shrink: 0;
  }

  .user-info {
    flex: 1;
  }

  .user-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
  }

  .user-nim {
    font-size: 12px;
    color: var(--text-secondary);
  }

  .user-list-empty {
    padding: 30px;
    text-align: center;
    color: var(--text-secondary);
  }

  .user-list-empty i {
    font-size: 32px;
    opacity: 0.3;
    margin-bottom: 10px;
    display: block;
  }

  .loading-spinner {
    text-align: center;
    padding: 20px;
    color: var(--primary);
  }

  .loading-spinner i {
    font-size: 24px;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Registration Instruction Toast */
  .registration-instruction-toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
    color: white;
    max-width: 380px;
    z-index: 3000;
    animation: slideInBottom 0.4s ease;
    pointer-events: auto;
  }

  @keyframes slideInBottom {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .instruction-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    font-weight: 700;
    font-size: 16px;
  }

  .instruction-header i {
    font-size: 20px;
  }

  .instruction-body {
    margin-bottom: 15px;
    background: rgba(255, 255, 255, 0.15);
    padding: 15px;
    border-radius: 8px;
  }

  .instruction-body p {
    margin: 0 0 8px 0;
    font-size: 15px;
    line-height: 1.4;
    font-weight: 500;
  }

  .instruction-body strong {
    font-weight: 700;
    color: #ffd700;
  }

  .instruction-body small {
    font-size: 12px;
    opacity: 0.9;
  }

  .instruction-footer {
    display: flex;
    gap: 10px;
  }

  .btn-instruction {
    flex: 1;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 14px;
    transition: all 0.2s;
    border: 2px solid rgba(255, 255, 255, 0.3);
    pointer-events: auto;
  }

  .btn-lanjutkan {
    background: rgba(255, 255, 255, 0.25);
    color: white;
  }

  .btn-lanjutkan:hover {
    background: rgba(255, 255, 255, 0.35);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
  }

  .btn-batalkan {
    background: rgba(255, 59, 48, 0.9);
    color: white;
  }

  .btn-batalkan:hover {
    background: rgba(255, 59, 48, 1);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
  }

  @media (max-width: 1200px) {
    .modal-options {
      grid-template-columns: 1fr;
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

    .video-wrapper {
      max-width: 100%;
    }

    .button-group {
      flex-direction: column;
    }

    .btn {
      width: 100%;
      justify-content: center;
    }

    .modal-content-custom {
      width: 95%;
      max-width: 100%;
    }
  }
</style>
</head>

<body>

<div class="main-content">

  <div class="welcome-card">
    <h2><i class="fa-solid fa-users-viewfinder"></i> Input Absensi Baru</h2>
    <p>Gunakan face recognition untuk mencatat kehadiran pengunjung labor secara otomatis</p>
  </div>

  <div class="capture-card">
    <h4><i class="fas fa-video"></i> Pemindaian Wajah</h4>

    <div class="controls-section">
      <div class="control-group">
        <label class="control-label"><i class="fas fa-building"></i> Pilih Labor</label>
        <select id="laborSelect" class="form-select" disabled>
          <?php foreach($labor_list as $labor): ?>
            <option value="<?= $labor['id'] ?>" selected><?= htmlspecialchars($labor['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="control-group">
        <label class="control-label"><i class="fas fa-check-circle"></i> Status Absensi</label>
        <div style="display: flex; gap: 10px;">
          <button class="btn btn-secondary" id="statusIn" onclick="setStatus('IN')" style="flex: 1;">
            <i class="fas fa-arrow-right-to-bracket"></i> Masuk (IN)
          </button>
          <button class="btn btn-secondary" id="statusOut" onclick="setStatus('OUT')" style="flex: 1;">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar (OUT)
          </button>
        </div>
      </div>
    </div>

    <div class="video-wrapper">
      <video id="video" autoplay muted playsinline></video>
      <canvas id="canvas-overlay"></canvas>
    </div>

    <div class="status-section">
      <div class="status-label">Status Deteksi</div>
      <div class="status-text" id="detectionStatus">
        <span class="status-icon"><i class="fas fa-hourglass-half"></i></span>
        <span id="statusText">Memuat model...</span>
      </div>
      <div id="confidenceDisplay" class="confidence-display" style="display: none;">
        <div class="confidence-bar">
          <div class="confidence-fill" id="confidenceFill"></div>
        </div>
        <div class="confidence-text" id="confidencePercent">0%</div>
      </div>
    </div>

    <!-- Registration Capture Status -->
    <div id="registrationStatusSection" class="status-section" style="display: none; border-left-color: var(--secondary);">
      <div class="status-label"><i class="fas fa-camera"></i> Status Pengambilan Gambar Wajah</div>
      <div class="status-text" id="registrationStatus">
        <span class="status-icon"><i class="fas fa-hourglass-half"></i></span>
        <span id="registrationStatusText">Persiapan...</span>
      </div>
      <div id="captureProgressDisplay" class="confidence-display" style="margin-top: 15px;">
        <div class="confidence-bar">
          <div class="confidence-fill" id="captureProgressFill" style="width: 0%; background: linear-gradient(90deg, var(--secondary), var(--primary));"></div>
        </div>
        <div class="confidence-text" id="captureProgressPercent">0%</div>
      </div>
    </div>

    <!-- User Info Card -->
    <div id="userInfoCard" class="user-info-card">
      <div class="user-info-header">
        <div class="user-avatar-large" id="userAvatarLarge">U</div>
        <div class="user-header-info">
          <h3 id="userNameDisplay">-</h3>
          <p id="userRoleDisplay">Mahasiswa</p>
        </div>
      </div>
      <div class="user-detail-row">
        <div class="user-detail-label"><i class="fas fa-id-card"></i> NIM</div>
        <div class="user-detail-value" id="userNimDisplay">-</div>
      </div>
    </div>

    <div class="button-group">
      <button class="btn btn-primary" id="captureBtn" onclick="captureFace()" disabled>
        <span class="btn-text"><i class="fas fa-check"></i> Konfirmasi Absen</span>
        <span class="btn-loader"><i class="fas fa-spinner"></i> Menyimpan...</span>
      </button>
      <button class="btn btn-secondary" id="cancelBtn" onclick="resetCapture()">
        <i class="fas fa-redo"></i> Ulang
      </button>
      <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

</div>

<!-- Modal untuk Pilih Mahasiswa Terdaftar -->
<div id="existingStudentModal" class="modal-custom">
  <div class="modal-content-custom">
    <div class="modal-header-custom">
      <div class="modal-icon">
        <i class="fas fa-user-graduate"></i>
      </div>
      <div>
        <h3>Pilih Mahasiswa Terdaftar</h3>
        <p>Cari dan pilih mahasiswa dari database</p>
      </div>
    </div>

    <div class="modal-body-custom">
      <div class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="studentSearchInput" placeholder="Cari berdasarkan Nama atau NIM..." onkeyup="searchStudents()">
      </div>

      <div id="studentListContainer" class="user-list">
        <div class="user-list-empty">
          <i class="fas fa-users"></i>
          <p>Mulai mengetik untuk mencari mahasiswa</p>
        </div>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button class="btn-modal btn-modal-secondary" onclick="closeExistingStudentModal()">
        <i class="fas fa-times"></i> Batal
      </button>
      <button class="btn-modal btn-modal-primary" id="confirmStudentBtn" onclick="confirmStudentSelection()" disabled>
        <i class="fas fa-check"></i> Pilih
      </button>
    </div>
  </div>
</div>



<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const video = document.getElementById('video');
  const statusText = document.getElementById('statusText');
  const detectionStatus = document.getElementById('detectionStatus');
  const captureBtn = document.getElementById('captureBtn');
  const confidenceDisplay = document.getElementById('confidenceDisplay');
  const userInfoCard = document.getElementById('userInfoCard');

  let isModelLoaded = false;
  let selectedStatus = null;
  let selectedLabor = null;
  let currentDescriptor = null;
  let currentConfidence = 0;
  let selectedUserType = null;
  let selectedStudent = null;
  let currentUserData = null;
  let multipleEmbeddings = []; // Untuk menampung multiple frames saat register
  let isCapturingMultipleFrames = false;
  let lastStatusUpdateTime = 0; // Untuk throttle status update
  const STATUS_UPDATE_INTERVAL = 7000; // Update status setiap 5 detik
  let lastUserAttendanceStatus = null; // Track last IN/OUT status
  
  // Timing untuk pemindaian wajah
  let lastFaceDetectTime = 0; // Waktu terakhir wajah terdeteksi
  const FACE_DETECTION_DURATION = 2000; // 2 detik untuk konfirmasi absen
  const DESCRIPTOR_RESET_INTERVAL = 7000; // 5 detik untuk reset descriptor
  let descriptorResetTimer = null;
  
  // Throttle untuk API calls
  let lastCompareTime = 0;
  const COMPARE_THROTTLE = 500; // Compare setiap 500ms max
  let autoSubmitScheduled = false; // Prevent duplicate submit
  let lastErrorMessageTime = 0; // Track last error message time untuk show multiple error
  const ERROR_MESSAGE_THROTTLE = 2000; // Show error message setiap 2 detik
  
  // Flag untuk disable absensi setelah register
  let isAbsensiDisabled = false; // Prevent auto-submit setelah register
  
  // Flag untuk pause capture saat tunggu user approval
  let isCapturingPaused = false; // Pause setiap frame dan tunggu user lanjutkan

  // Load Face API Models
  async function loadModels() {
    const MODEL_URL = './models/';
    
    console.log("Memuat model dari:", MODEL_URL);
    
    try {
      await Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
      ]);
      isModelLoaded = true;
      updateStatus('ready', 'Siap untuk pemindaian wajah. Arahkan wajah Anda ke kamera.', false);
      startVideo();
    } catch (err) {
      console.error("Gagal load model:", err);
      updateStatus('error', 'Gagal memuat model face recognition. Periksa koneksi atau refresh halaman.', false);
    }
  }

  // Mulai Webcam
  function startVideo() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
      .then(stream => {
        video.srcObject = stream;
      })
      .catch(err => {
        console.error("Error webcam:", err);
        updateStatus('error', 'Izin kamera ditolak. Periksa pengaturan privasi browser Anda.', false);
      });
  }

  // Event listener: Deteksi wajah real-time
  video.addEventListener('play', () => {
    if(!isModelLoaded) return;

    const canvas = faceapi.createCanvasFromMedia(video);
    document.getElementById('canvas-overlay').replaceWith(canvas);
    canvas.id = 'canvas-overlay';
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    
    // Create preprocessing canvas untuk CLAHE
    const preprocessCanvas = document.createElement('canvas');
    preprocessCanvas.width = video.clientWidth;
    preprocessCanvas.height = video.clientHeight;
    preprocessCanvas.style.display = 'none';
    document.body.appendChild(preprocessCanvas);
    
    const displaySize = { width: video.clientWidth, height: video.clientHeight };
    faceapi.matchDimensions(canvas, displaySize);

    // OPTIMASI: Frame skipping & async handling untuk mencegah blocking
    let isDetectionInProgress = false;
    let frameCounter = 0;
    const FRAME_SKIP = 2; // Proses setiap 3 frame (skip 2)

    function runDetectionLoop() {
      // Jika deteksi masih berjalan, skip frame ini
      if(isDetectionInProgress || !isModelLoaded) {
        setTimeout(runDetectionLoop, 16); // ~60fps
        return;
      }

      // Frame skipping: hanya proses frame tertentu
      frameCounter++;
      if(frameCounter % (FRAME_SKIP + 1) !== 0) {
        setTimeout(runDetectionLoop, 16);
        return;
      }

      isDetectionInProgress = true;
      
      (async () => {
        try {
          // Apply CLAHE preprocessing
          let detectionInput = video;
          if(typeof cv !== 'undefined') {
            try {
              // Draw video frame ke preprocessing canvas
              const ctx = preprocessCanvas.getContext('2d');
              ctx.drawImage(video, 0, 0, preprocessCanvas.width, preprocessCanvas.height);
              
              // Konversi ke OpenCV Mat
              let src = cv.imread(preprocessCanvas);
              let gray = new cv.Mat();
              cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
              
              // Apply CLAHE (Contrast Limited Adaptive Histogram Equalization)
              let clahe = cv.createCLAHE(2.0, new cv.Size(8, 8));
              let dst = new cv.Mat();
              clahe.apply(gray, dst);
              
              // Convert back ke canvas
              cv.imshow(preprocessCanvas, dst);
              detectionInput = preprocessCanvas;
              
              // Cleanup
              src.delete();
              gray.delete();
              dst.delete();
            } catch(e) {
              console.warn('CLAHE preprocessing failed, using original video:', e);
              detectionInput = video;
            }
          }
          
          const detections = await faceapi.detectAllFaces(detectionInput)
            .withFaceLandmarks()
            .withFaceDescriptors();
          
          const resizedDetections = faceapi.resizeResults(detections, displaySize);
          
          canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
          faceapi.draw.drawDetections(canvas, resizedDetections);

          // Throttle status update - hanya update setiap 5 detik
          const currentTime = Date.now();
          const shouldUpdateStatus = (currentTime - lastStatusUpdateTime) >= STATUS_UPDATE_INTERVAL;

          // Validasi: hanya 1 wajah yang boleh terdeteksi
          if(resizedDetections.length > 1) {
            // Multiple faces detected - jangan proses
            currentDescriptor = null;
            currentConfidence = 0;
            currentUserData = null;
            confidenceDisplay.style.display = 'none';
            userInfoCard.classList.remove('show');
            captureBtn.disabled = true;
            if(shouldUpdateStatus) {
              updateStatus('error', `⚠ Terdeteksi ${resizedDetections.length} wajah! Hanya 1 wajah yang diizinkan.`, false);
              lastStatusUpdateTime = currentTime;
            }
          } else if(resizedDetections.length === 1) {
            const detection = resizedDetections[0];
            currentDescriptor = detection.descriptor;
            
            // Catat waktu deteksi wajah
            if(lastFaceDetectTime === 0) {
              lastFaceDetectTime = currentTime;
            }
            
            // THROTTLE: Bandingkan dengan database hanya setiap 500ms
            if((currentTime - lastCompareTime) >= COMPARE_THROTTLE) {
              compareWithDatabase(Array.from(detection.descriptor), currentTime);
              lastCompareTime = currentTime;
            }
            
            // AUTO-NOTIFY & INSTAN AUTO-SUBMIT ketika confidence > 40%
            if(currentConfidence > 0.4 && selectedStatus && !autoSubmitScheduled) {
              // Check apakah status valid (sesuai allowedStatus)
              const isStatusValid = (selectedStatus === currentUserData?.allowedStatus);
              
              if(isStatusValid && !isAbsensiDisabled) {
                // Status valid & absensi aktif - langsung auto-submit tanpa notification
                autoSubmitScheduled = true;
                setButtonLoading(true);
                submitAttendance();
              } else {
                // Status TIDAK valid - tampilkan error message setiap 2 detik
                const currentTime = Date.now();
                if((currentTime - lastErrorMessageTime) >= ERROR_MESSAGE_THROTTLE) {
                  const selectedStatusName = selectedStatus === 'IN' ? 'Masuk' : 'Keluar';
                  const allowedStatusName = selectedStatus === 'IN' ? 'Keluar' : 'Masuk';
                  const lastStatusText = selectedStatus === 'IN' ? 'sudah masuk' : 'belum masuk hari ini';
                  showToast(`❌ Error: Absen ${selectedStatusName} tidak diizinkan! Anda ${lastStatusText}. Silakan ${allowedStatusName}.`, 'error');
                  lastErrorMessageTime = currentTime;
                }
              }
            } else if(shouldUpdateStatus) {
              updateStatus('detected', 'Wajah terdeteksi! Tunggu hasil perbandingan...', true);
              lastStatusUpdateTime = currentTime;
            }
          } else {
            // Reset descriptor setelah 5 detik tanpa deteksi
            if(lastFaceDetectTime > 0 && (currentTime - lastFaceDetectTime) >= DESCRIPTOR_RESET_INTERVAL) {
              currentDescriptor = null;
              currentConfidence = 0;
              currentUserData = null;
              confidenceDisplay.style.display = 'none';
              userInfoCard.classList.remove('show');
              lastFaceDetectTime = 0;
            }
            
            captureBtn.disabled = true;
            if(shouldUpdateStatus) {
              updateStatus('searching', 'Mencari wajah... Pastikan wajah terlihat jelas.', true);
              lastStatusUpdateTime = currentTime;
            }
          }
        } catch(err) {
          console.error('Detection error:', err);
        } finally {
          // Tandai deteksi selesai agar frame berikutnya bisa diproses
          isDetectionInProgress = false;
          // Jadwalkan deteksi loop berikutnya
          setTimeout(runDetectionLoop, 16); // ~60fps target
        }
      })();
    }

    // Mulai detection loop
    runDetectionLoop();
  });

  // Perbandingan dengan Database
  function compareWithDatabase(descriptor, currentTime) {
    // Kirim ke server untuk perbandingan
    fetch('backend/api_face_compare.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        descriptor: descriptor
      })
    })
    .then(response => response.json())
    .then(data => {
      if(data.status === 'found') {
        currentConfidence = data.confidence;
        updateConfidenceDisplay(data.confidence);
        
        // Fetch user data dan tampilkan
        fetch('backend/api_get_user_data.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: data.user_id
          })
        })
        .then(res => res.json())
        .then(userData => {
          if(userData.status === 'success') {
            currentUserData = userData.user;
            lastUserAttendanceStatus = userData.user.lastStatus; // Store last IN/OUT status
            displayUserInfo(userData.user);
            updateStatus('matched', `Cocok: ${userData.user.nama}`, false);
            
            // AUTO-DETECT & AUTO-SET STATUS: Selalu gunakan allowedStatus dari server
            const autoStatus = userData.user.allowedStatus;
            // OTOMATIS set status sesuai allowedStatus - tidak perlu manual pilih
            setStatus(autoStatus);
            // Sekarang selectedStatus sudah auto-set, enable button
            captureBtn.disabled = false;
            updateButtonText('Konfirmasi Absen');
          }
        })
        .catch(err => console.error('Error fetching user data:', err));
        
      } else if(data.status === 'unknown') {
        currentConfidence = 0;
        currentUserData = null;
        confidenceDisplay.style.display = 'none';
        userInfoCard.classList.remove('show');
        updateStatus('unknown', 'Wajah tidak dikenali', false);
        captureBtn.disabled = false;
        updateButtonText('Daftarkan Wajah'); // Change button text untuk unknown face
      } else {
        captureBtn.disabled = true;
      }
    })
    .catch(err => {
      console.error('Error:', err);
    });
  }

  // Display User Info
  function displayUserInfo(user) {
    const initials = user.nama.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    
    document.getElementById('userAvatarLarge').textContent = initials;
    document.getElementById('userNameDisplay').textContent = user.nama;
    document.getElementById('userNimDisplay').textContent = user.nim || '-';
    
    userInfoCard.classList.add('show');
  }

  // Update UI Status
  function updateStatus(type, message, isLoading) {
    statusText.textContent = message;
    detectionStatus.className = 'status-text ' + type;
    
    const icons = {
      'ready': '<i class="fas fa-check-circle"></i>',
      'searching': '<i class="fas fa-hourglass-half"></i>',
      'detected': '<i class="fas fa-camera"></i>',
      'matched': '<i class="fas fa-user-check"></i>',
      'unknown': '<i class="fas fa-question-circle"></i>',
      'error': '<i class="fas fa-exclamation-circle"></i>'
    };
    
    detectionStatus.innerHTML = '<span class="status-icon">' + icons[type] + '</span><span id="statusText">' + message + '</span>';
  }

  // Update Confidence Bar (Hidden)
  function updateConfidenceDisplay(confidence) {
    // Confidence display hidden - tidak ditampilkan lagi
  }

  // Set Status Absensi
  function setStatus(status) {
    selectedStatus = status;
    document.getElementById('statusIn').classList.toggle('btn-primary', status === 'IN');
    document.getElementById('statusIn').classList.toggle('btn-secondary', status !== 'IN');
    document.getElementById('statusOut').classList.toggle('btn-primary', status === 'OUT');
    document.getElementById('statusOut').classList.toggle('btn-secondary', status !== 'OUT');
  }

  // Capture Face
  function captureFace() {
    if(!selectedStatus) {
      showToast('Pilih status absensi terlebih dahulu (Masuk/Keluar)', 'error');
      return;
    }
    if(!selectedLabor) {
      showToast('Pilih labor terlebih dahulu', 'error');
      return;
    }
    if(!currentDescriptor) {
      showToast('Wajah tidak terdeteksi. Coba lagi!', 'error');
      return;
    }

    // Cek apakah wajah cocok atau tidak
    if(currentConfidence > 0) {
      // Wajah cocok - langsung simpan dengan loading
      setButtonLoading(true);
      submitAttendance();
    } else {
      // Wajah tidak cocok - langsung tampilkan modal cari mahasiswa
      showExistingStudentModal();
    }
  }

  // Submit Attendance
  function submitAttendance() {
    const formData = new FormData();
    formData.append('action', 'submit_attendance');
    formData.append('status', selectedStatus);
    formData.append('labor_id', selectedLabor);
    formData.append('descriptor', JSON.stringify(Array.from(currentDescriptor)));
    formData.append('confidence', currentConfidence);
    formData.append('user_id', currentUserData?.id || null);

    fetch('backend/process_attendance.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      setButtonLoading(false);
      
      if(data.status === 'success') {
        const statusText = selectedStatus === 'IN' ? 'Masuk' : 'Keluar';
        showToast(`✓ Absen ${statusText} berhasil! - ${currentUserData.nama}`, 'success');
        
        // Reset descriptor setelah 4s untuk ready next scan
        resetCaptureWithDelay(4000);
      } else {
        showToast('❌ Gagal menyimpan absensi: ' + data.message, 'error');
      }
    })
    .catch(err => {
      console.error('Error:', err);
      setButtonLoading(false);
      showToast('Terjadi kesalahan saat menyimpan', 'error');
    });
  }

  // Reset Capture with Delay
  function resetCaptureWithDelay(delay = 4000) {
    setTimeout(() => {
      resetCapture();
    }, delay);
  }

  // Reset Capture
  function resetCapture() {
    // Reset semua descriptor dan data wajah
    currentDescriptor = null;
    currentConfidence = 0;
    selectedUserType = null;
    currentUserData = null;
    lastUserAttendanceStatus = null;
    lastFaceDetectTime = 0; // Reset timing deteksi wajah
    autoSubmitScheduled = false; // Reset auto-submit flag
    lastCompareTime = 0; // Reset throttle timer
    lastErrorMessageTime = 0; // Reset error message timer
    selectedStatus = null; // RESET selectedStatus agar auto-change
    
    // Reset capture registration
    isCapturingMultipleFrames = false;
    isCapturingPaused = false;
    multipleEmbeddings = [];
    frameCount = 0;
    
    // Clear descriptor reset timer
    if(descriptorResetTimer) {
      clearTimeout(descriptorResetTimer);
      descriptorResetTimer = null;
    }
    
    // Reset status buttons (unpilih semua)
    document.getElementById('statusIn').classList.add('btn-secondary');
    document.getElementById('statusIn').classList.remove('btn-primary');
    document.getElementById('statusOut').classList.add('btn-secondary');
    document.getElementById('statusOut').classList.remove('btn-primary');
    
    captureBtn.disabled = true;
    setButtonLoading(false);
    updateButtonText('Konfirmasi Absen'); // Reset button text ke default
    confidenceDisplay.style.display = 'none';
    userInfoCard.classList.remove('show');
    lastStatusUpdateTime = 0; // Reset throttle timer
    updateStatus('searching', 'Mencari wajah... Arahkan wajah Anda ke kamera.', true);
  }

  function proceedSelection() {
    document.getElementById('userSelectionModal').classList.remove('show');
    
    if(selectedUserType === 'existing') {
      // Tampilkan modal pilih mahasiswa
      showExistingStudentModal();
    }
  }

  // Set Button Loading State
  function setButtonLoading(isLoading) {
    if(isLoading) {
      captureBtn.classList.add('loading');
      captureBtn.disabled = true;
    } else {
      captureBtn.classList.remove('loading');
      captureBtn.disabled = false;
    }
  }

  // Show Toast Message
  function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-message ${type}`;
    
    const iconMap = {
      'success': '<i class="fas fa-check-circle"></i>',
      'error': '<i class="fas fa-exclamation-circle"></i>',
      'info': '<i class="fas fa-info-circle"></i>'
    };
    
    toast.innerHTML = `
      ${iconMap[type] || iconMap['info']}
      <span class="toast-text">${message}</span>
      <button class="toast-close" onclick="this.parentElement.remove();">
        <i class="fas fa-times"></i>
      </button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
      toast.style.animation = 'slideInRight 0.3s ease reverse';
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 4000);
  }

//

  // Load labor selection - set default automatically
  document.addEventListener('DOMContentLoaded', function() {
    const laborSelect = document.getElementById('laborSelect');
    if(laborSelect.options.length > 0) {
      selectedLabor = laborSelect.options[0].value;
      laborSelect.value = selectedLabor;
    }
  });

  // Keep change listener for manual changes
  document.getElementById('laborSelect').addEventListener('change', function() {
    selectedLabor = this.value;
  });

  // Load labor selection
  //document.getElementById('laborSelect').addEventListener('change', function() {
  //  selectedLabor = this.value;
  //});

//

  // Existing Student Modal Functions
  function showExistingStudentModal() {
    document.getElementById('existingStudentModal').classList.add('show');
    selectedStudent = null;
    document.getElementById('studentSearchInput').value = '';
    document.getElementById('studentListContainer').innerHTML = '<div class="user-list-empty"><i class="fas fa-users"></i><p>Mulai mengetik untuk mencari mahasiswa</p></div>';
    document.getElementById('confirmStudentBtn').disabled = true;
    document.getElementById('studentSearchInput').focus();
  }

  function closeExistingStudentModal() {
    document.getElementById('existingStudentModal').classList.remove('show');
    selectedStudent = null;
    resetCaptureWithDelay(7000);
  }

  // Guest Registration Modal Functions (REMOVED - No guest support)

  // Guest Face Registration (REMOVED - No guest support)

  function searchStudents() {
    const query = document.getElementById('studentSearchInput').value.trim();
    const container = document.getElementById('studentListContainer');

    if(query.length < 1) {
      container.innerHTML = '<div class="user-list-empty"><i class="fas fa-users"></i><p>Mulai mengetik untuk mencari mahasiswa</p></div>';
      return;
    }

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i> Mencari...</div>';

    // Fetch students dari server
    fetch('backend/api_search_students.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        query: query
      })
    })
    .then(response => response.json())
    .then(data => {
      if(data.status === 'success' && data.students.length > 0) {
        let html = '';
        data.students.forEach(student => {
          const initials = student.nama.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
          html += `
            <div class="user-item" onclick="selectStudent(${student.id}, '${student.nama.replace(/'/g, "\\'")}', '${student.nim}')">
              <div class="user-avatar">${initials}</div>
              <div class="user-info">
                <div class="user-name">${student.nama}</div>
                <div class="user-nim">NIM: ${student.nim}</div>
              </div>
            </div>
          `;
        });
        container.innerHTML = html;
      } else {
        container.innerHTML = '<div class="user-list-empty"><i class="fas fa-search"></i><p>Tidak ada mahasiswa yang cocok</p></div>';
      }
    })
    .catch(err => {
      console.error('Error:', err);
      container.innerHTML = '<div class="user-list-empty"><i class="fas fa-exclamation-circle"></i><p>Terjadi kesalahan saat mencari</p></div>';
    });
  }

  function selectStudent(userId, userName, userNim) {
    selectedStudent = {
      id: userId,
      nama: userName,
      nim: userNim
    };

    // Highlight selected item
    document.querySelectorAll('.user-item').forEach(item => {
      item.style.backgroundColor = '';
    });
    event.currentTarget.style.backgroundColor = 'rgba(245, 158, 11, 0.1)';

    // Enable confirm button
    document.getElementById('confirmStudentBtn').disabled = false;
  }

  function confirmStudentSelection() {
    if(!selectedStudent) {
      showToast('Pilih mahasiswa terlebih dahulu', 'error');
      return;
    }

    // Mulai capture multiple frames
    startMultipleFrameCapture(selectedStudent.id);
  }

  // Capture Multiple Frames untuk Registration (Simpan 5 Embedding Terpisah)
  let frameCount = 0;
  const FRAMES_TO_CAPTURE = 5; // Capture 5 frames - simpan terpisah bukan rata-rata
  let registrationPreprocessCanvas = null; // Canvas untuk CLAHE preprocessing saat registrasi

  function startMultipleFrameCapture(userId) {
    document.getElementById('existingStudentModal').classList.remove('show');
    isCapturingMultipleFrames = true;
    multipleEmbeddings = [];
    frameCount = 0;

    // Create preprocessing canvas untuk registration
    const video = document.getElementById('video');
    registrationPreprocessCanvas = document.createElement('canvas');
    registrationPreprocessCanvas.width = video.clientWidth;
    registrationPreprocessCanvas.height = video.clientHeight;
    registrationPreprocessCanvas.style.display = 'none';
    document.body.appendChild(registrationPreprocessCanvas);

    // Tampilkan registration status section
    document.getElementById('registrationStatusSection').style.display = 'block';
    
    showToast('📸 Memulai capture wajah optimal... Jangan gerakkan kepala', 'info');
    captureBtn.disabled = true;
    
    // Tampilkan countdown 3 detik sebelum mulai capture
    let countdown = 3;
    updateRegistrationStatus('preparing', `Persiapan... ${countdown} detik`, 0);
    
    const countdownInterval = setInterval(() => {
      countdown--;
      if(countdown > 0) {
        updateRegistrationStatus('preparing', `Persiapan... ${countdown} detik`, (3 - countdown) * 33);
      } else {
        clearInterval(countdownInterval);
        updateRegistrationStatus('preparing', 'Mulai pengambilan gambar...', 100);
        
        // Tunggu 3 detik sebelum mulai capture
        setTimeout(() => {
          captureMultipleFramesLoop(userId);
        }, 3000);
      }
    }, 1000);
  }

  // Update Registration Status Display
  function updateRegistrationStatus(type, message, progress) {
    const statusElement = document.getElementById('registrationStatus');
    const progressFill = document.getElementById('captureProgressFill');
    const progressPercent = document.getElementById('captureProgressPercent');
    
    const icons = {
      'preparing': '<i class="fas fa-hourglass-start"></i>',
      'capturing': '<i class="fas fa-camera"></i>',
      'processing': '<i class="fas fa-spinner"></i>',
      'success': '<i class="fas fa-check-circle"></i>',
      'error': '<i class="fas fa-exclamation-circle"></i>'
    };
    
    statusElement.innerHTML = '<span class="status-icon">' + icons[type] + '</span><span id="registrationStatusText">' + message + '</span>';
    
    if(progress !== undefined) {
      progressFill.style.width = progress + '%';
      progressPercent.textContent = Math.round(progress) + '%';
    }
  }

  function captureMultipleFramesLoop(userId) {
    // Jika pause, jangan lanjutkan capture
    if(isCapturingPaused) {
      return;
    }

    if(!isCapturingMultipleFrames || frameCount >= FRAMES_TO_CAPTURE) {
      if(frameCount >= FRAMES_TO_CAPTURE) {
        // Selesai capture, rata-rata embeddings
        finalizeMultipleFrameCapture(userId);
      }
      return;
    }

    // Capture current frame dengan CLAHE preprocessing
    const video = document.getElementById('video');
    let detectionInput = video; // Default ke video tanpa preprocessing

    // Apply CLAHE preprocessing jika OpenCV available
    if(typeof cv !== 'undefined' && registrationPreprocessCanvas) {
      try {
        // Draw video frame ke preprocessing canvas
        const ctx = registrationPreprocessCanvas.getContext('2d');
        ctx.drawImage(video, 0, 0, registrationPreprocessCanvas.width, registrationPreprocessCanvas.height);
        
        // Konversi ke OpenCV Mat
        let src = cv.imread(registrationPreprocessCanvas);
        let gray = new cv.Mat();
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
        
        // Apply CLAHE (Contrast Limited Adaptive Histogram Equalization)
        let clahe = cv.createCLAHE(2.0, new cv.Size(8, 8));
        let dst = new cv.Mat();
        clahe.apply(gray, dst);
        
        // Convert back ke canvas
        cv.imshow(registrationPreprocessCanvas, dst);
        detectionInput = registrationPreprocessCanvas; // Gunakan canvas yang sudah dipreprocess
        
        // Cleanup
        src.delete();
        gray.delete();
        dst.delete();
      } catch(e) {
        console.warn('CLAHE preprocessing failed during registration, using original video:', e);
        detectionInput = video; // Fallback ke video asli
      }
    }

    // Deteksi wajah dari input yang sudah dipreprocess
    faceapi.detectSingleFace(detectionInput)
      .withFaceLandmarks()
      .withFaceDescriptor()
      .then(detection => {
        if(detection) {
          multipleEmbeddings.push(Array.from(detection.descriptor));
          frameCount++;
          
          const progress = Math.round((frameCount / FRAMES_TO_CAPTURE) * 100);
          updateRegistrationStatus('capturing', `📸 Mengambil gambar ${frameCount}/${FRAMES_TO_CAPTURE}`, progress);
          
          // Pause capture dan tampilkan instruksi
          isCapturingPaused = true;
          showCaptureInstructionToast(frameCount);
        } else {
          // Wajah tidak terdeteksi, coba lagi
          updateRegistrationStatus('capturing', '⚠ Wajah tidak terdeteksi, coba lagi...', (frameCount / FRAMES_TO_CAPTURE) * 100);
          setTimeout(() => {
            captureMultipleFramesLoop(userId);
          }, 300);
        }
      })
      .catch(err => {
        console.error('Error capturing frame:', err);
        setTimeout(() => {
          captureMultipleFramesLoop(userId);
        }, 300);
      });
  }

  // Tampilkan instruksi untuk setiap frame capture
  function showCaptureInstructionToast(frameNumber) {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    
    // Instruksi berbeda untuk setiap frame
    let instruction = '';
    switch(frameNumber) {
      case 1:
        instruction = '✓ Gambar 1 berhasil! Sekarang ubah sudut kepala ke <strong>KIRI</strong>';
        break;
      case 2:
        instruction = '✓ Gambar 2 berhasil! Sekarang ubah sudut kepala ke <strong>KANAN</strong>';
        break;
      case 3:
        instruction = '✓ Gambar 3 berhasil! Ambil dengan <strong>AKSESORIS</strong> (seperti kacamata jika ada)';
        break;
      case 4:
        instruction = '✓ Gambar 4 berhasil! Ubah <strong>EKSPRESI WAJAH</strong> (senyum atau serius)';
        break;
      case 5:
        instruction = '✓ Gambar 5 berhasil! <strong>Posisi natural</strong> untuk penyelesaian';
        break;
    }
    
    toast.className = 'registration-instruction-toast';
    toast.innerHTML = `
      <div class="instruction-header">
        <i class="fas fa-video"></i>
        <span>Intruksi Capture Wajah Optimal</span>
      </div>
      <div class="instruction-body">
        <p>${instruction}</p>
        <small>Gambar ${frameNumber}/${FRAMES_TO_CAPTURE}</small>
      </div>
      <div class="instruction-footer">
        <button class="btn-instruction btn-lanjutkan" onclick="resumeCaptureFrame()">
          <i class="fas fa-check"></i> Lanjutkan
        </button>
        <button class="btn-instruction btn-batalkan" onclick="cancelCaptureProcess()">
          <i class="fas fa-times"></i> Batalkan
        </button>
      </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove setelah 60 detik jika user tidak action
    const timeout = setTimeout(() => {
      toast.remove();
    }, 60000);
    
    // Remove timeout jika user klik tombol
    toast.addEventListener('click', () => {
      clearTimeout(timeout);
    });
  }

  // Resume capture frame berikutnya
  function resumeCaptureFrame() {
    // Remove current toast
    const activeToast = document.querySelector('.registration-instruction-toast');
    if(activeToast) {
      activeToast.remove();
    }
    
    // Resume capture
    isCapturingPaused = false;
    
    // Lanjut ke frame berikutnya setelah 400ms
    setTimeout(() => {
      captureMultipleFramesLoop(selectedStudent.id);
    }, 400);
  }

  // Cancel capture process
  function cancelCaptureProcess() {
    // Remove current toast
    const activeToast = document.querySelector('.registration-instruction-toast');
    if(activeToast) {
      activeToast.remove();
    }
    
    // Cancel capturing
    isCapturingMultipleFrames = false;
    isCapturingPaused = false;
    multipleEmbeddings = [];
    frameCount = 0;
    
    // Hide registration status
    document.getElementById('registrationStatusSection').style.display = 'none';
    
    // Show cancel message
    showToast('❌ Proses capture dibatalkan. Silakan coba lagi.', 'error');
    
    // Reset capture
    setTimeout(() => {
      resetCapture();
    }, 1000);
  }

  function finalizeMultipleFrameCapture(userId) {
    if(multipleEmbeddings.length === 0) {
      updateRegistrationStatus('error', '❌ Gagal capture wajah. Coba lagi!', 0);
      showToast('❌ Gagal capture wajah. Coba lagi!', 'error');
      isCapturingMultipleFrames = false;
      
      // Hide registration status section setelah 2 detik
      setTimeout(() => {
        document.getElementById('registrationStatusSection').style.display = 'none';
        resetCapture();
      }, 2000);
      return;
    }

    // Update status ke processing
    updateRegistrationStatus('processing', `⏳ Memproses ${multipleEmbeddings.length} gambar wajah...`, 100);
    
    // Simpan semua 5 embedding terpisah (bukan rata-rata)
    showToast(`✓ Capture berhasil! ${multipleEmbeddings.length} embeddings akan disimpan terpisah`, 'success');
    
    // Save dengan multiple embeddings
    saveFaceEmbeddingMultiple(userId, multipleEmbeddings);
    
    isCapturingMultipleFrames = false;
  }

  // Save Face Embedding - Multiple Embeddings Terpisah Per User
  function saveFaceEmbeddingMultiple(userId, embeddings) {
    const formData = new FormData();
    formData.append('action', 'save_embedding_multiple');
    formData.append('user_id', userId);
    formData.append('embeddings', JSON.stringify(embeddings)); // Array of 5 embeddings
    formData.append('embedding_count', embeddings.length);

    fetch('backend/process_attendance.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if(data.status === 'success') {
        updateRegistrationStatus('success', `✓ Wajah optimal terdaftar untuk ${selectedStudent.nama}!`, 100);
        showToast(`✓ Wajah optimal terdaftar untuk ${selectedStudent.nama}! (${embeddings.length} embedding terpisah disimpan)`, 'success');
        document.getElementById('existingStudentModal').classList.remove('show');
        
        // Disable absensi selama 5 detik setelah register
        isAbsensiDisabled = true;
        setTimeout(() => {
          isAbsensiDisabled = false; // Aktifkan kembali absensi
        }, 7000);
        
        setTimeout(() => {
          document.getElementById('registrationStatusSection').style.display = 'none';
          // Refresh halaman setelah 2 detik
          location.reload();
        }, 2000);
      } else {
        updateRegistrationStatus('error', `❌ Gagal menyimpan wajah: ${data.message}`, 0);
        showToast('❌ Gagal menyimpan wajah: ' + data.message, 'error');
        setTimeout(() => {
          document.getElementById('registrationStatusSection').style.display = 'none';
          resetCapture();
        }, 2000);
      }
    })
    .catch(err => {
      console.error('Error:', err);
      updateRegistrationStatus('error', 'Terjadi kesalahan saat menyimpan', 0);
      showToast('Terjadi kesalahan saat menyimpan', 'error');
      setTimeout(() => {
        document.getElementById('registrationStatusSection').style.display = 'none';
        resetCapture();
      }, 2000);
    });
  }

  // Initialize
  window.addEventListener('load', () => {
    loadModels();
  });
</script>
</body>
</html>
