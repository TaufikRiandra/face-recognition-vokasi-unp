<?php
/**
 * Export Users to Excel (HTML format - Excel dapat membuka secara native)
 * Usage: POST /api_export_users_excel.php
 * No external libraries required
 */

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
    
    // Prepare filename
    $filename = 'Daftar_User_' . date('d-m-Y_His') . '.xls';
    
    // Set headers untuk Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output HTML table yang Excel bisa baca
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .title { 
            font-size: 16pt; 
            font-weight: bold; 
            text-align: center; 
            background-color: #F59E0B; 
            color: #1F2937; 
            padding: 10px;
            margin-bottom: 5px;
        }
        .subtitle { 
            font-size: 12pt; 
            font-style: italic; 
            color: #4B5563; 
            margin-bottom: 10px;
        }
        .info { 
            font-size: 11pt; 
            color: #6B7280; 
            background-color: #F9FAFB;
            margin-bottom: 10px;
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #F59E0B;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            border: 1px solid #D97706;
            padding: 10px;
            font-size: 11pt;
        }
        td {
            border: 1px solid #E5E7EB;
            padding: 8px;
            font-size: 10pt;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        tr:nth-child(odd) {
            background-color: #FFFFFF;
        }
        .center { text-align: center; }
        .number { text-align: center; }
    </style>
</head>
<body>
    <div class="title">📋 Daftar User Sistem Absensi</div>
    <div class="subtitle">Laporan Data Pengguna Aktif</div>
    <div class="info">
        <strong>Tanggal Cetak:</strong> <?= date('d-m-Y H:i:s') ?> | 
        <strong>Total User:</strong> <?= count($users) ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Nama Lengkap</th>
                <th style="width: 20%;">NIM</th>
                <th style="width: 30%;">Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $idx => $user): ?>
            <tr>
                <td class="number"><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($user['nama']) ?></td>
                <td class="center"><?= htmlspecialchars($user['nim']) ?></td>
                <td><?= date('d-m-Y H:i:s', strtotime($user['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
    <?php
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}


