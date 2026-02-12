<?php
include 'header.php';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola User - Sistem Absensi</title>
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
    --info: #3b82f6;
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

  .card {
    background: var(--bg-primary);
    border-radius: 10px;
    border: 2px solid var(--border-color);
    padding: 30px;
    margin-bottom: 30px;
    animation: slideInDown 0.5s ease 0.2s both;
  }

  .card h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .search-wrapper {
    position: relative;
    margin-bottom: 20px;
  }

  .search-wrapper input {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
  }

  .search-wrapper input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
  }

  .search-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    font-size: 14px;
  }

  .user-list {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .user-item {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    transition: all 0.2s;
  }

  .user-item:hover {
    background-color: var(--hover-bg);
  }

  .user-item:last-child {
    border-bottom: none;
  }

  .user-info {
    flex: 1;
  }

  .user-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 15px;
    margin-bottom: 4px;
  }

  .user-nim {
    font-size: 13px;
    color: var(--text-secondary);
  }

  .user-date {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 2px;
  }

  .user-actions {
    display: flex;
    gap: 8px;
  }

  .btn-action {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
  }

  .btn-edit {
    background: var(--info);
    color: white;
  }

  .btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }

  .btn-delete {
    background: var(--danger);
    color: white;
  }

  .btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
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

  .loading-spinner {
    text-align: center;
    padding: 40px;
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

  .result-info {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 15px;
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    color: white;
  }

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

  .action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
  }

  .pagination-btn {
    min-width: 40px;
    height: 40px;
    padding: 8px 12px;
    border: 1px solid var(--primary);
    background: white;
    color: var(--primary);
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 14px;
  }

  .pagination-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(245, 158, 11, 0.2);
  }

  .pagination-btn.active {
    background: var(--primary);
    color: white;
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-info {
    font-size: 14px;
    color: #666;
    white-space: nowrap;
  }

  .users-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    overflow: hidden;
  }

  .users-table thead {
    background: var(--secondary);
  }

  .users-table thead th {
    padding: 14px 12px;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
  }

  .users-table thead th:last-child {
  }

  .users-table tbody tr {
    transition: all 0.25s ease;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  }

  .users-table tbody tr:hover {
    background-color: var(--hover-bg);
    box-shadow: inset 0 0 12px rgba(245, 158, 11, 0.12);
  }

  .users-table tbody tr:last-child {
    border-bottom: none;
  }

  .users-table td {
    padding: 14px 12px;
    font-size: 13px;
    color: var(--text-primary);
  }

  .users-table td:last-child {
  }

  .users-table .table-nama {
    font-weight: 600;
    text-align: left;
  }

  .users-table .table-nim {
    color: var(--text-secondary);
    font-family: monospace;
    text-align: center;
  }

  .users-table .table-date {
    color: var(--text-secondary);
    font-size: 12px;
    text-align: center;
  }

  .users-table .table-time {
    color: var(--text-secondary);
    font-size: 12px;
    text-align: center;
  }

  .users-table .table-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  .table-empty {
    text-align: center;
    padding: 40px;
    color: var(--text-secondary);
  }

  @media (max-width: 768px) {
    .main-content {
      margin-left: 0;
      padding: 20px;
    }

    .welcome-card h2 {
      font-size: 24px;
    }

    .user-item {
      flex-direction: column;
      align-items: flex-start;
    }

    .user-actions {
      width: 100%;
    }

    .btn-action {
      flex: 1;
      justify-content: center;
    }
  }
</style>
</head>

<body>

<div class="main-content">

  <div class="welcome-card">
    <h2><i class="fas fa-users-cog"></i> Kelola User</h2>
    <p>Kelola data user dan pencarian berdasarkan nama atau NIM</p>
  </div>

  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0;"><i class="fas fa-users"></i> Kelola User</h3>
      <a href="index.php" class="btn-primary" style="white-space: nowrap; margin: 0;">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>

    <div class="search-wrapper">
      <input type="text" id="searchInput" placeholder="Cari berdasarkan nama atau NIM...">
      <i class="fas fa-search"></i>
    </div>

    <div id="resultContainer">
      <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 15px;">Memuat data user...</p></div>
    </div>

    <!-- Pagination -->
    <div id="paginationContainer" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
    </div>
  </div>

</div>

<!-- Modal Edit User -->
<div id="modalEditUser" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-icon">
        <i class="fas fa-user-edit"></i>
      </div>
      <h3>Edit User</h3>
    </div>

    <form id="formEditUser">
      <input type="hidden" id="inputEditUserId" name="user_id">
      
      <div class="modal-form-group">
        <label for="inputEditNama">Nama Lengkap</label>
        <input type="text" id="inputEditNama" name="nama" placeholder="Masukkan nama lengkap" required>
        <div class="form-error" id="errorEditNama"></div>
      </div>

      <div class="modal-form-group">
        <label for="inputEditNIM">NIM</label>
        <input type="text" id="inputEditNIM" name="nim" placeholder="Masukkan NIM" required>
        <div class="form-error" id="errorEditNIM"></div>
      </div>

      <div class="modal-footer">
        <button type="button" id="btnBatalEdit" class="modal-btn modal-btn-secondary">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" id="btnSimpanEdit" class="modal-btn modal-btn-primary">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const searchInput = document.getElementById('searchInput');
const resultContainer = document.getElementById('resultContainer');
const modalEditUser = document.getElementById('modalEditUser');
const formEditUser = document.getElementById('formEditUser');
const btnBatalEdit = document.getElementById('btnBatalEdit');

let searchTimeout;
let editFormHasChanged = false;
let editIsSubmitting = false;
let originalEditNama = '';
let originalEditNIM = '';

// Pagination variables
let allUsers = [];
let currentPage = 1;
const itemsPerPage = 10;

// Search users with AJAX
function searchUsers(query) {
  const params = new URLSearchParams({
    search: query
  });

  resultContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 15px;">Mencari...</p></div>';

  fetch('api_get_users.php?' + params.toString())
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        if(data.users.length > 0) {
          allUsers = data.users;
          currentPage = 1;
          displayUsersTable(data.users);
        } else {
          resultContainer.innerHTML = '<div class="table-empty"><i class="fas fa-search" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;\"></i><p>Tidak ada user ditemukan</p><p style="font-size: 14px; font-weight: 400; margin: 0; color: var(--text-secondary);">Coba ubah kriteria pencarian</p></div>';
          document.getElementById('paginationContainer').innerHTML = '';
        }
      } else {
        resultContainer.innerHTML = '<div class="table-empty"><i class="fas fa-exclamation-circle" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;\"></i><p>Terjadi kesalahan</p></div>';
        document.getElementById('paginationContainer').innerHTML = '';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      resultContainer.innerHTML = '<div class="table-empty"><i class="fas fa-exclamation-circle" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;\"></i><p>Terjadi kesalahan</p></div>';
      document.getElementById('paginationContainer').innerHTML = '';
    });
}

// Display users as table
function displayUsersTable(users) {
  const startIdx = (currentPage - 1) * itemsPerPage;
  const endIdx = startIdx + itemsPerPage;
  const pageUsers = users.slice(startIdx, endIdx);
  
  let html = '<table class="users-table"><thead><tr><th style="width: 25%;">Nama</th><th style="width: 15%; text-align: center;">NIM</th><th style="width: 15%; text-align: center;">Tanggal</th><th style="width: 12%; text-align: center;">Waktu</th><th style="width: 33%; text-align: center;">Aksi</th></tr></thead><tbody>';
  
  if(pageUsers.length > 0) {
    pageUsers.forEach(user => {
      let tanggal = '-';
      let waktu = '-';
      if(user.created_at) {
        const parts = user.created_at.split(' ');
        tanggal = parts[0];
        waktu = parts[1];
      }
      html += `
        <tr>
          <td class="table-nama">${user.nama}</td>
          <td class="table-nim">${user.nim}</td>
          <td class="table-date">${tanggal}</td>
          <td class="table-time">${waktu}</td>
          <td>
            <div class="table-actions">
              <button type="button" class="btn-action btn-edit" data-id="${user.id}" data-nama="${user.nama}" data-nim="${user.nim}">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button type="button" class="btn-action btn-delete" data-id="${user.id}">
                <i class="fas fa-trash"></i> Hapus
              </button>
            </div>
          </td>
        </tr>
      `;
    });
  }
  
  html += '</tbody></table>';
  resultContainer.innerHTML = html;
  
  // Add event listeners
  document.querySelectorAll('#resultContainer .btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      openEditModal(this.dataset.id, this.dataset.nama, this.dataset.nim);
    });
  });

  document.querySelectorAll('#resultContainer .btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      deleteUser(this.dataset.id);
    });
  });
  
  // Update pagination
  updatePaginationButtons(users);
}

// Real-time search
searchInput.addEventListener('keyup', function() {
  clearTimeout(searchTimeout);
  
  const query = this.value.trim();
  
  if(query.length === 0) {
    currentPage = 1;
    loadAllUsers();
    return;
  }

  searchTimeout = setTimeout(() => {
    searchUsers(query);
  }, 300);
});

// Open Edit Modal
function openEditModal(userId, nama, nim) {
  document.getElementById('inputEditUserId').value = userId;
  document.getElementById('inputEditNama').value = nama;
  document.getElementById('inputEditNIM').value = nim;
  
  originalEditNama = nama;
  originalEditNIM = nim;
  editFormHasChanged = false;
  
  document.getElementById('errorEditNama').textContent = '';
  document.getElementById('errorEditNama').classList.remove('show');
  document.getElementById('errorEditNIM').textContent = '';
  document.getElementById('errorEditNIM').classList.remove('show');
  
  modalEditUser.classList.add('active');
  setupEditFormTracking();
}

// Track form changes for edit modal
function setupEditFormTracking() {
  formEditUser.addEventListener('input', function() {
    const currentNama = document.getElementById('inputEditNama').value.trim();
    const currentNIM = document.getElementById('inputEditNIM').value.trim();
    editFormHasChanged = (currentNama !== originalEditNama || currentNIM !== originalEditNIM);
  });
}

// Close Edit Modal with confirmation
function closeEditModal() {
  if(editFormHasChanged && !editIsSubmitting) {
    const confirmation = confirm('Ada perubahan yang belum disimpan. Apakah Anda yakin ingin menutup?');
    if(!confirmation) return;
  }
  modalEditUser.classList.remove('active');
  formEditUser.reset();
  editFormHasChanged = false;
}

// Delete User
function deleteUser(userId) {
  if(!confirm('Apakah Anda yakin ingin menghapus user ini?')) return;

  const formData = new FormData();
  formData.append('user_id', userId);

  fetch('api_delete_user.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        showSuccessNotification('User berhasil dihapus');
        const query = searchInput.value.trim();
        if(query.length > 0) {
          searchUsers(query);
        } else {
          loadAllUsers();
        }
      } else {
        alert('Error: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Terjadi kesalahan saat menghapus user');
    });
}

// Submit Edit Form
formEditUser.addEventListener('submit', async function(e) {
  e.preventDefault();

  const userId = document.getElementById('inputEditUserId').value;
  const nama = document.getElementById('inputEditNama').value.trim();
  const nim = document.getElementById('inputEditNIM').value.trim();

  // Validate
  let isValid = true;
  document.getElementById('errorEditNama').textContent = '';
  document.getElementById('errorEditNama').classList.remove('show');
  document.getElementById('errorEditNIM').textContent = '';
  document.getElementById('errorEditNIM').classList.remove('show');

  if(!nama) {
    document.getElementById('errorEditNama').textContent = 'Nama tidak boleh kosong';
    document.getElementById('errorEditNama').classList.add('show');
    isValid = false;
  }

  if(!nim) {
    document.getElementById('errorEditNIM').textContent = 'NIM tidak boleh kosong';
    document.getElementById('errorEditNIM').classList.add('show');
    isValid = false;
  }

  if(!isValid) return;

  editIsSubmitting = true;
  const formData = new FormData();
  formData.append('user_id', userId);
  formData.append('nama', nama);
  formData.append('nim', nim);

  try {
    const response = await fetch('api_update_user.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if(data.success) {
      editFormHasChanged = false;
      showSuccessNotification('User berhasil diubah');
      closeEditModal();
      const query = searchInput.value.trim();
      if(query.length > 0) {
        searchUsers(query);
      } else {
        loadAllUsers();
      }
      editIsSubmitting = false;
    } else {
      if(data.error.includes('NIM')) {
        document.getElementById('errorEditNIM').textContent = data.error;
        document.getElementById('errorEditNIM').classList.add('show');
      } else {
        alert('Error: ' + data.error);
      }
      editIsSubmitting = false;
    }
  } catch(error) {
    console.error('Error:', error);
    alert('Terjadi kesalahan saat mengubah user');
    editIsSubmitting = false;
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

// Load all users for pagination
async function loadAllUsers() {
  try {
    const response = await fetch('api_get_users.php?search=');
    const data = await response.json();
    
    if(data.success) {
      allUsers = data.users;
      currentPage = 1;
      displayUsersTable(allUsers);
    } else {
      resultContainer.innerHTML = '<div class="table-empty"><i class="fas fa-exclamation-circle" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;\"></i><p>Gagal memuat data user</p></div>';
      document.getElementById('paginationContainer').innerHTML = '';
    }
  } catch(error) {
    console.error('Error loading users:', error);
    resultContainer.innerHTML = '<div class="table-empty"><i class="fas fa-exclamation-circle" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;\"></i><p>Terjadi kesalahan saat memuat data</p></div>';
    document.getElementById('paginationContainer').innerHTML = '';
  }
}

// Display users for current page
function displayUsersPage(pageNum) {
  currentPage = pageNum;
  displayUsersTable(allUsers);
}

// Update pagination buttons
function updatePaginationButtons(users) {
  const displayUsers = users || allUsers;
  const totalPages = Math.ceil(displayUsers.length / itemsPerPage);
  const paginationContainer = document.getElementById('paginationContainer');
  
  if(totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  
  let html = '<div class="pagination-info">Halaman ' + currentPage + ' dari ' + totalPages + '</div>';
  html += '<button class="pagination-btn" onclick="displayUsersPage(currentPage - 1)" ' + (currentPage === 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i> Sebelumnya</button>';
  
  // Page numbers
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, currentPage + 2);
  
  if(startPage > 1) {
    html += '<button class="pagination-btn" onclick="displayUsersPage(1)">1</button>';
    if(startPage > 2) html += '<span class="pagination-info">...</span>';
  }
  
  for(let i = startPage; i <= endPage; i++) {
    html += '<button class="pagination-btn ' + (i === currentPage ? 'active' : '') + '" onclick="displayUsersPage(' + i + ')">' + i + '</button>';
  }
  
  if(endPage < totalPages) {
    if(endPage < totalPages - 1) html += '<span class="pagination-info">...</span>';
    html += '<button class="pagination-btn" onclick="displayUsersPage(' + totalPages + ')">' + totalPages + '</button>';
  }
  
  html += '<button class="pagination-btn" onclick="displayUsersPage(currentPage + 1)" ' + (currentPage === totalPages ? 'disabled' : '') + '>Berikutnya <i class="fas fa-chevron-right"></i></button>';
  
  paginationContainer.innerHTML = html;
}

// Event listeners
btnBatalEdit.addEventListener('click', closeEditModal);

// Close modal when clicking outside (with confirmation)
modalEditUser.addEventListener('click', function(e) {
  if(e.target === modalEditUser) {
    closeEditModal();
  }
});

// Load all users on page load
loadAllUsers();

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
</body>
</html>
