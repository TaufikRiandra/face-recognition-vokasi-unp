<?php
// backend/helpers_attendance.php
// Helper functions untuk attendance keterangan

/**
 * Validasi waktu attendance menggunakan server time (tidak bisa dimanipulasi)
 * @param string $status 'IN' atau 'OUT'
 * @return array ['valid' => bool, 'message' => string, 'current_time' => string]
 */
function validateAttendanceTime($status) {
    // Gunakan server time (WIB) - tidak bisa dimanipulasi dari client
    $current_time = date('H:i:s');
    $current_hours = date('H');
    $current_minutes = date('i');
    
    if($status === 'IN') {
        // Absen MASUK: mulai dari 06:00
        // Format untuk perbandingan
        $min_hour = 6;
        
        if($current_hours < $min_hour) {
            return [
                'valid' => false,
                'message' => 'Absensi dimulai dari jam 06:00 WIB. Waktu server: ' . $current_time,
                'current_time' => $current_time
            ];
        }
    } else if($status === 'OUT') {
        // Absen KELUAR: mulai dari 16:00
        $min_hour = 16;
        
        if($current_hours < $min_hour) {
            return [
                'valid' => false,
                'message' => 'Absensi pulang dimulai dari jam 16:00 WIB. Waktu server: ' . $current_time,
                'current_time' => $current_time
            ];
        }
    }
    
    return [
        'valid' => true,
        'message' => 'Waktu valid',
        'current_time' => $current_time
    ];
}

/**
 * Validasi batasan daily attendance (1x masuk, 1x keluar per hari)
 * Exception: jika hari sebelumnya IN tapi tidak OUT (lembur), bisa OUT 2x hari ini
 * Masuk tetap max 1x per hari (tanpa exception)
 * @param int $user_id User ID
 * @param string $status 'IN' atau 'OUT'
 * @param mysqli $conn Database connection
 * @return array ['valid' => bool, 'message' => string]
 */
function validateDailyLimit($user_id, $status, $conn) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if($status === 'IN') {
        // Check jumlah IN hari ini - max 1x (TANPA EXCEPTION)
        $today_in_query = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'IN'
        ");
        $today_in_count = mysqli_fetch_assoc($today_in_query)['cnt'] ?? 0;
        
        if($today_in_count >= 1) {
            return [
                'valid' => false,
                'message' => 'Anda sudah masuk 1 kali hari ini. Silakan keluar terlebih dahulu baru bisa masuk lagi.'
            ];
        }
    } 
    else if($status === 'OUT') {
        // Check jumlah OUT hari ini
        $today_out_query = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'OUT'
        ");
        $today_out_count = mysqli_fetch_assoc($today_out_query)['cnt'] ?? 0;
        
        // Max OUT = 1x, tapi dengan exception bisa 2x jika kemarin lembur
        if($today_out_count >= 2) {
            // Sudah OUT 2x hari ini, reject
            return [
                'valid' => false,
                'message' => 'Anda sudah keluar 2 kali hari ini.'
            ];
        }
        
        if($today_out_count >= 1) {
            // Sudah OUT 1x hari ini, check exception: apakah kemarin IN tapi tidak OUT (lembur)?
            $yesterday_in_query = mysqli_query($conn, "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'
            ");
            $yesterday_in_count = mysqli_fetch_assoc($yesterday_in_query)['cnt'] ?? 0;
            
            $yesterday_out_query = mysqli_query($conn, "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'
            ");
            $yesterday_out_count = mysqli_fetch_assoc($yesterday_out_query)['cnt'] ?? 0;
            
            if($yesterday_in_count > 0 && $yesterday_out_count === 0) {
                // EXCEPTION: Kemarin IN tapi tidak OUT (lembur), allow OUT 2x hari ini
                // OUT 1 = selesaikan lembur kemarin
                // OUT 2 = pulang hari ini
                return [
                    'valid' => true,
                    'message' => 'Valid - OUT 2x karena lembur kemarin'
                ];
            } else {
                // Sudah OUT 1x dan tidak ada exception lembur
                return [
                    'valid' => false,
                    'message' => 'Anda sudah keluar 1 kali hari ini.'
                ];
            }
        }
    }
    
    return [
        'valid' => true,
        'message' => 'Valid'
    ];
}

/**
 * Hitung keterangan attendance berdasarkan waktu dan status
 * @param string $status IN atau OUT
 * @param string $waktu_attendance DATETIME dari attendance
 * @param int $labor_id Labor ID
 * @param mysqli $conn Database connection
 * @return string Keterangan (tepat waktu, terlambat, lembur)
 */
function hitungKeterangan($status, $waktu_attendance, $labor_id, $conn) {
    // Get jam standar dari labor
    $query = "SELECT jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
              FROM labor 
              WHERE id = $labor_id";
    $result = mysqli_query($conn, $query);
    
    if(!$result || mysqli_num_rows($result) == 0) {
        // Default jika tidak ada setting labor
        $jam_masuk = '08:00:00';
        $jam_pulang = '16:00:00';
        $toleransi = 15;
    } else {
        $labor = mysqli_fetch_assoc($result);
        $jam_masuk = $labor['jam_masuk_standar'] ?? '09:30:00';
        $jam_pulang = $labor['jam_pulang_standar'] ?? '18:30:00';
        $toleransi = $labor['toleransi_terlambat'] ?? 1;
    }
    
    // Extract waktu dari datetime
    $jam_att = date('H:i:s', strtotime($waktu_attendance));
    
    if($status === 'IN') {
        // Untuk masuk: jam_masuk adalah batas akhir masuk (09:30)
        // Jika melebihi jam_masuk + toleransi (09:31) = terlambat
        $jam_batas = date('H:i:s', strtotime($jam_masuk) + ($toleransi * 60));
        
        if($jam_att > $jam_batas) {
            return 'terlambat';
        } else {
            return 'tepat waktu';
        }
    } elseif($status === 'OUT') {
        // Untuk OUT: jam_pulang adalah batas pulang normal (18:30)
        // Jika melebihi jam_pulang + toleransi (18:31) = lembur
        $jam_batas_lembur = date('H:i:s', strtotime($jam_pulang) + ($toleransi * 60));
        
        if($jam_att > $jam_batas_lembur) {
            return 'lembur';
        } else {
            return 'tepat waktu';
        }
    }
    
    return 'normal';
}

/**
 * Get label keterangan dengan styling
 * @param string $keterangan Keterangan (tepat waktu, terlambat, lembur)
 * @return array dengan keys 'class', 'label', 'icon'
 */
function getKeteranganBadge($keterangan) {
    $badges = [
        'tepat waktu' => [
            'class' => 'badge-success',
            'label' => 'Tepat Waktu',
            'icon' => 'fas fa-check-circle',
            'color' => '#10b981'
        ],
        'terlambat' => [
            'class' => 'badge-danger',
            'label' => 'Terlambat',
            'icon' => 'fas fa-clock',
            'color' => '#ef4444'
        ],
        'lembur' => [
            'class' => 'badge-warning',
            'label' => 'Lembur',
            'icon' => 'fas fa-hourglass-end',
            'color' => '#f59e0b'
        ],
        'normal' => [
            'class' => 'badge-secondary',
            'label' => 'Normal',
            'icon' => 'fas fa-minus-circle',
            'color' => '#64748b'
        ]
    ];
    
    return $badges[$keterangan] ?? $badges['normal'];
}

/**
 * Get HTML badge untuk keterangan
 * @param string $keterangan
 * @return string HTML badge
 */
function getKeteranganHTML($keterangan) {
    $badge = getKeteranganBadge($keterangan);
    return '<span style="
        background: ' . $badge['color'] . '; 
        color: white; 
        padding: 5px 10px; 
        border-radius: 12px; 
        font-size: 12px; 
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    ">
        <i class="' . $badge['icon'] . '" style="font-size: 10px;"></i>
        ' . $badge['label'] . '
    </span>';
}

/**
 * Get statistik keterangan
 * @param int $labor_id
 * @param string $date_from YYYY-MM-DD
 * @param string $date_to YYYY-MM-DD
 * @param mysqli $conn
 * @return array
 */
function getStatisticKeterangan($labor_id, $date_from, $date_to, $conn) {
    $query = "
        SELECT 
            keterangan,
            COUNT(*) as total,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance_logs al2 
                WHERE DATE(al2.created_at) BETWEEN '$date_from' AND '$date_to' 
                AND al2.labor_id = $labor_id), 1) as percentage
        FROM attendance_logs al
        WHERE labor_id = $labor_id
        AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY keterangan
        ORDER BY FIELD(keterangan, 'tepat waktu', 'terlambat', 'lembur', 'normal'), total DESC
    ";
    
    $result = mysqli_query($conn, $query);
    $stats = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        $stats[$row['keterangan']] = [
            'total' => $row['total'],
            'percentage' => $row['percentage']
        ];
    }
    
    return $stats;
}

?>
