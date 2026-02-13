<!-- Footer -->
<footer style="background: linear-gradient(135deg, #2B579A 0%, #1e3a5f 100%); color: white; padding: 30px 20px; margin-top: 50px; border-top: 3px solid #f59e0b; font-size: 13px;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px;">
      <div>
        <h5 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #fbbf24;"><i class="fas fa-users-viewfinder"></i> Sistem Absensi</h5>
        <p style="margin: 0; line-height: 1.6; color: #e0e0e0;">Sistem manajemen absensi berbasis Face Recognition untuk mencatat kehadiran pengunjung labor secara akurat dan otomatis.</p>
      </div>
      <div>
        <h5 style="font-size: 14px; font-weight: 700; margin-bottom: 15px; color: #fbbf24;"><i class="fas fa-link"></i> Menu Cepat</h5>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="index.php" style="color: #e0e0e0; text-decoration: none;">Dashboard</a></li>
          <li style="margin-bottom: 8px;"><a href="history.php" style="color: #e0e0e0; text-decoration: none;">Riwayat</a></li>
          <li style="margin-bottom: 8px;"><a href="face-capture.php" style="color: #e0e0e0; text-decoration: none;">Input Absensi</a></li>
        </ul>
      </div>
      <div>
        <h5 style="font-size: 14px; font-weight: 700; margin-bottom: 15px; color: #fbbf24;"><i class="fas fa-info-circle"></i> Informasi</h5>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><i class="fas fa-code-branch"></i> Version 1.0</li>
          <li style="margin-bottom: 8px;"><i class="fas fa-calendar-alt"></i> <?= date('Y') ?></li>
          <li><i class="fas fa-lock"></i> Keamanan Data Terjaga</li>
        </ul>
      </div>
    </div>
    <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px; text-align: center; color: #b0b0b0;">
      <p style="margin: 0; font-size: 13px;">&copy; <?= date('Y') ?> <strong>Sistem Absensi Face Recognition</strong></p>
  </div>
</footer>
