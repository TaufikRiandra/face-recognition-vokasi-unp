<?php
/**
 * Export Users to PDF (HTML-based without external library)
 * Uses browser's native PDF printing capability
 * Usage: POST /api_export_users_pdf.php
 */

header('Content-Type: text/html; charset=UTF-8');

include 'koneksi.php';

try {
    // Get all active users from database
    $query = "SELECT id, nama, nim, created_at FROM users WHERE is_active = 1 ORDER BY nama ASC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . mysqli_error($conn));
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    $filename = 'Daftar_User_' . date('d-m-Y_His') . '.pdf';
    $current_date = date('d-m-Y H:i:s');
    $total_users = count($users);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Daftar User</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background: white;
                padding: 40px;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #f59e0b;
                padding-bottom: 20px;
            }
            
            .header h1 {
                font-size: 28px;
                color: #1f2937;
                margin-bottom: 8px;
            }
            
            .header p {
                font-size: 14px;
                color: #6b7280;
                margin: 4px 0;
            }
            
            .info-box {
                display: flex;
                justify-content: space-between;
                margin-bottom: 25px;
                padding: 12px;
                background: #f9fafb;
                border-radius: 6px;
                border-left: 4px solid #f59e0b;
            }
            
            .info-box p {
                font-size: 13px;
                color: #4b5563;
                margin: 0;
            }
            
            .info-box strong {
                color: #1f2937;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            thead {
                background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
                color: white;
            }
            
            th {
                padding: 14px 12px;
                text-align: left;
                font-weight: 600;
                font-size: 13px;
                letter-spacing: 0.5px;
            }
            
            td {
                padding: 12px;
                border-bottom: 1px solid #e5e7eb;
                font-size: 13px;
            }
            
            tbody tr:nth-child(even) {
                background-color: #f9fafb;
            }
            
            tbody tr:hover {
                background-color: #f3f4f6;
            }
            
            .no {
                width: 50px;
                text-align: center;
                font-weight: 600;
                color: #6b7280;
            }
            
            .nama {
                font-weight: 500;
                color: #1f2937;
            }
            
            .nim {
                font-family: 'Courier New', monospace;
                color: #4b5563;
                font-weight: 500;
            }
            
            .created_at {
                color: #6b7280;
                font-size: 12px;
            }
            
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                font-size: 12px;
                color: #9ca3af;
            }
            
            @media print {
                body {
                    padding: 0;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📋 Daftar User Sistem Absensi</h1>
            <p>Laporan Data Pengguna Aktif</p>
        </div>
        
        <div class="info-box">
            <p><strong>Tanggal Cetak:</strong> <?= $current_date ?></p>
            <p><strong>Total User:</strong> <?= $total_users ?> pengguna aktif</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th>Nama Lengkap</th>
                    <th>NIM</th>
                    <th>Tanggal Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $idx => $user): ?>
                <tr>
                    <td class="no"><?= $idx + 1 ?></td>
                    <td class="nama"><?= htmlspecialchars($user['nama']) ?></td>
                    <td class="nim"><?= htmlspecialchars($user['nim']) ?></td>
                    <td class="created_at"><?= date('d-m-Y H:i:s', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Dokumen ini dihasilkan secara otomatis oleh Sistem Absensi Laboratorium</p>
            <p>Mohon gunakan browser untuk mencetak ke PDF (Ctrl+P → Save as PDF)</p>
        </div>
        
        <script>
            // Auto-trigger print dialog untuk PDF
            window.print();
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
