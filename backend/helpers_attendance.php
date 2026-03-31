<?php
/**
 * backend/helpers_attendance.php
 * Helper functions untuk attendance - Semua waktu dari database, NO HARDCODED
 */

/**
 * Write attendance log to file
 */
function writeAttendanceLog($message) {
    $log_file = __DIR__ . '/../attendance_debug.log';
    $log_handle = fopen($log_file, 'a');
    if ($log_handle) {
        $log_prefix = "[" . date('Y-m-d H:i:s') . "] " . getmypid() . " ";
        fwrite($log_handle, $log_prefix . $message . "\n");
        fclose($log_handle);
    }
    error_log($message);
}

/**
 * Get labor schedule settings dari database
 * 
 * @param int $labor_id
 * @param mysqli $conn
 * @return array dengan keys: jam_masuk_awal, jam_masuk_standar, jam_pulang_standar, 
 *                           jam_pulang_akhir, toleransi_terlambat
 *                           atau FALSE jika tidak ditemukan
 */
function getLaborSettings($labor_id, $conn) {
    if (!$labor_id || !$conn) {
        return false;
    }
    
    $query = "SELECT jam_masuk_awal, jam_masuk_standar, jam_pulang_standar, 
                     jam_pulang_akhir, toleransi_terlambat 
              FROM labor 
              WHERE id = " . intval($labor_id);
    
    $result = mysqli_query($conn, $query);
    if (!$result || mysqli_num_rows($result) == 0) {
        writeAttendanceLog("WARNING: Labor $labor_id not found in database");
        return false;
    }
    
    return mysqli_fetch_assoc($result);
}

/**
 * Validasi waktu attendance menggunakan server time
 * TIDAK BISA dimanipulasi dari client
 * 
 * @param string $status 'IN' atau 'OUT'
 * @param int $user_id User ID
 * @param int $labor_id Labor ID - WAJIB untuk get waktu dari database
 * @param mysqli $conn Database connection
 * @return array ['valid' => bool, 'message' => string, 'current_time' => string]
 */
function validateAttendanceTime($status, $user_id, $labor_id, $conn) {
    $current_time = date('H:i:s');
    
    writeAttendanceLog("========== validateAttendanceTime START ==========");
    writeAttendanceLog("STATUS: $status | USER_ID: $user_id | LABOR_ID: $labor_id | TIME: $current_time");
    
    // Get settings dari database
    $labor = getLaborSettings($labor_id, $conn);
    if (!$labor) {
        writeAttendanceLog("ERROR: Tidak bisa get labor settings untuk labor_id $labor_id");
        return [
            'valid' => false,
            'message' => 'Gagal ambil pengaturan jadwal. Hubungi admin.',
            'current_time' => $current_time
        ];
    }
    
    $jam_masuk_awal = $labor['jam_masuk_awal'] ?? '06:00:00';
    $jam_pulang_standar = $labor['jam_pulang_standar'] ?? '16:00:00';
    
    writeAttendanceLog("From DB - jam_masuk_awal: $jam_masuk_awal, jam_pulang_standar: $jam_pulang_standar");
    
    if ($status === 'IN') {
        // Validasi MASUK: gunakan jam_masuk_awal
        $min_time = new DateTime($jam_masuk_awal);
        $current_datetime = new DateTime($current_time);
        
        if ($current_datetime < $min_time) {
            $jam_awal_display = date('H:i', strtotime($jam_masuk_awal));
            writeAttendanceLog("❌ REJECT: Jam $current_time < batas $jam_awal_display");
            return [
                'valid' => false,
                'message' => "Absensi masuk dimulai dari jam $jam_awal_display WIB. Waktu server: $current_time",
                'current_time' => $current_time
            ];
        }
        writeAttendanceLog("✅ ACCEPT: Waktu masuk valid");
        
    } else if ($status === 'OUT') {
        // Validasi KELUAR: check apakah ada outstanding IN
        // Jika ada outstanding IN (lembur), allow OUT anytime - kecuali sudah OUT hari ini
        // Jika tidak ada outstanding IN, enforce jam_pulang_standar
        
        writeAttendanceLog("Processing OUT validation...");
        
        $today = date('Y-m-d');
        $allow_anytime_out = false;
        
        // Check: ada outstanding IN dari tanggal manapun?
        $outstanding_query = "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = " . intval($user_id) . "
            AND status = 'IN'
            AND DATE(created_at) < '$today'
            AND id > COALESCE((
                SELECT MAX(id) FROM attendance_logs 
                WHERE user_id = " . intval($user_id) . "
                AND status = 'OUT'
                AND DATE(created_at) < '$today'
            ), 0)
        ";
        
        $outstanding_result = mysqli_query($conn, $outstanding_query);
        if (!$outstanding_result) {
            writeAttendanceLog("ERROR in outstanding query: " . mysqli_error($conn));
            return [
                'valid' => false,
                'message' => 'Database error', 
                'current_time' => $current_time
            ];
        }
        
        $outstanding_row = mysqli_fetch_assoc($outstanding_result);
        $outstanding_count = intval($outstanding_row['cnt'] ?? 0);
        writeAttendanceLog("Outstanding IN count: $outstanding_count");
        
        if ($outstanding_count > 0) {
            // Ada lembur dari kemarin - check apakah sudah ada OUT hari ini
            $today_out_query = "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = " . intval($user_id) . "
                AND status = 'OUT'
                AND DATE(created_at) = '$today'
            ";
            
            $today_out_result = mysqli_query($conn, $today_out_query);
            if (!$today_out_result) {
                writeAttendanceLog("ERROR in today_out query: " . mysqli_error($conn));
                return [
                    'valid' => false,
                    'message' => 'Database error',
                    'current_time' => $current_time
                ];
            }
            
            $today_out_row = mysqli_fetch_assoc($today_out_result);
            $today_out_count = intval($today_out_row['cnt'] ?? 0);
            
            // Jika belum ada OUT hari ini = allow OUT anytime (untuk tutup lembur)
            if ($today_out_count === 0) {
                $allow_anytime_out = true;
                writeAttendanceLog("✅ Outstanding IN + no OUT today = Allow OUT anytime (close lembur)");
            } else {
                writeAttendanceLog("Outstanding IN tapi sudah ada OUT hari ini - enforce jam_pulang_standar");
                $allow_anytime_out = false;
            }
        }
        
        // Jika tidak ada lembur outstanding, enforce jam_pulang_standar TANPA tolerance
        if (!$allow_anytime_out) {
            $min_time = new DateTime($jam_pulang_standar);
            $current_datetime = new DateTime($current_time);
            
            if ($current_datetime < $min_time) {
                $jam_pulang_display = date('H:i', strtotime($jam_pulang_standar));
                writeAttendanceLog("❌ REJECT: Jam $current_time < batas $jam_pulang_display dan tidak ada outstanding lembur");
                return [
                    'valid' => false,
                    'message' => "Absensi pulang dimulai dari jam $jam_pulang_display WIB. Waktu server: $current_time",
                    'current_time' => $current_time
                ];
            }
        }
        
        writeAttendanceLog("✅ ACCEPT: Waktu keluar valid");
    }
    
    writeAttendanceLog("========== validateAttendanceTime END ==========");
    return [
        'valid' => true,
        'message' => 'Waktu valid',
        'current_time' => $current_time
    ];
}

/**
 * Validasi batasan daily attendance (1x masuk, 1x keluar per hari)
 * Exception: jika hari sebelumnya IN tapi tidak OUT (lembur), bisa OUT 2x hari ini
 * Special Rule: Jika ada outstanding IN kemarin, hari ini HARUS OUT dulu sebelum bisa IN
 * Masuk tetap max 1x per hari (tanpa exception)
 * @param int $user_id User ID
 * @param string $status 'IN' atau 'OUT'
 * @param mysqli $conn Database connection
 * @return array ['valid' => bool, 'message' => string]
 */
function validateDailyLimit($user_id, $status, $conn) {
    $today = date('Y-m-d');
    
    writeAttendanceLog("=== validateDailyLimit START - user_id: $user_id, status: $status ===");
    
    // Check apakah ada outstanding IN dari tanggal manapun sebelum hari ini (bukan hanya kemarin)
    // Outstanding = ada IN sebelum hari ini yang belum diikuti OUT
    $outstanding_in_query = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM attendance_logs 
        WHERE user_id = $user_id 
        AND status = 'IN'
        AND DATE(created_at) < '$today'
        AND id > COALESCE((
            SELECT MAX(id) FROM attendance_logs 
            WHERE user_id = $user_id 
            AND status = 'OUT'
            AND DATE(created_at) < '$today'
        ), 0)
    ");
    if(!$outstanding_in_query) {
        writeAttendanceLog("ERROR: outstanding_in_query failed - " . mysqli_error($conn));
        return ['valid' => false, 'message' => 'Database error'];
    }
    $outstanding_in_result = mysqli_fetch_assoc($outstanding_in_query);
    $outstanding_in_count = intval($outstanding_in_result['cnt'] ?? 0);
    
    // Check OUT dari hari ini
    $today_out_query = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM attendance_logs 
        WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'OUT'
    ");
    if(!$today_out_query) {
        writeAttendanceLog("ERROR: today_out_query for lembur check failed - " . mysqli_error($conn));
        return ['valid' => false, 'message' => 'Database error'];
    }
    $today_out_result = mysqli_fetch_assoc($today_out_query);
    $today_out_count = intval($today_out_result['cnt'] ?? 0);
    
    // Outstanding check: ada IN dari tanggal manapun sebelum hari ini yang belum di-OUT
    $has_outstanding_in = ($outstanding_in_count > 0 && $today_out_count === 0);
    writeAttendanceLog("Outstanding IN (any past date): outstanding_in_count=$outstanding_in_count, today_out=$today_out_count, has_outstanding=$has_outstanding_in");
    
    if($status === 'IN') {
        writeAttendanceLog("Validating IN request...");
        
        // CRITICAL RULE: Jika ada outstanding IN dari tanggal manapun, TIDAK BOLEH IN sampai OUT lembur dulu!
        if($has_outstanding_in) {
            writeAttendanceLog("REJECT: User has outstanding IN (past date) - must OUT first!");
            return [
                'valid' => false,
                'message' => 'Anda masih memiliki jam kerja lembur yang belum diselesaikan. Silakan keluar (OUT) lembur terlebih dahulu sebelum masuk kembali.'
            ];
        }
        
        // Check jumlah IN hari ini - max 1x (TANPA EXCEPTION)
        $today_in_query = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'IN'
        ");
        if(!$today_in_query) {
            writeAttendanceLog("ERROR: today_in_query failed - " . mysqli_error($conn));
            return ['valid' => false, 'message' => 'Database error'];
        }
        $today_in_result = mysqli_fetch_assoc($today_in_query);
        $today_in_count = intval($today_in_result['cnt'] ?? 0);
        writeAttendanceLog("Today IN count: $today_in_count");
        
        if($today_in_count >= 1) {
            writeAttendanceLog("REJECT: Already IN once today");
            return [
                'valid' => false,
                'message' => 'Anda sudah masuk 1 kali hari ini.'
            ];
        }
    } 
    else if($status === 'OUT') {
        writeAttendanceLog("Validating OUT request...");
        
        // Check jumlah IN hari ini
        $today_in_query = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM attendance_logs 
            WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'IN'
        ");
        if(!$today_in_query) {
            writeAttendanceLog("ERROR: today_in_query for OUT check failed - " . mysqli_error($conn));
            return ['valid' => false, 'message' => 'Database error'];
        }
        $today_in_result = mysqli_fetch_assoc($today_in_query);
        $today_in_count = intval($today_in_result['cnt'] ?? 0);
        writeAttendanceLog("OUT check - today_in_count: $today_in_count, today_out_count: $today_out_count");
        
        // CASE 1: Belum ada IN hari ini (lembur scenario - auto-OUT system atau user OUT untuk lembur)
        if($today_in_count === 0) {
            writeAttendanceLog("Case 1: Belum ada IN hari ini - allow OUT (lembur scenario)");
            
            // Jika ada outstanding IN → allow OUT untuk tutup lembur
            if($has_outstanding_in) {
                writeAttendanceLog("ALLOW: OUT untuk tutup lembur dari kemarin");
                return [
                    'valid' => true,
                    'message' => 'OUT untuk menutup lembur diterima'
                ];
            }
            
            // Jika belum ada OUT sama sekali → allow OUT
            if($today_out_count === 0) {
                writeAttendanceLog("ALLOW: Belum ada OUT, boleh OUT");
                return [
                    'valid' => true,
                    'message' => 'OUT diterima'
                ];
            }
            
            // Jika sudah ada OUT tapi belum ada IN → allow OUT (for lembur documentation)
            if($today_out_count >= 1) {
                writeAttendanceLog("ALLOW: Sudah ada OUT tapi belum ada IN, boleh OUT lagi");
                return [
                    'valid' => true,
                    'message' => 'OUT diterima'
                ];
            }
        }
        
        // CASE 2: Ada IN hari ini (work shift scenario)
        if($today_in_count >= 1) {
            writeAttendanceLog("Case 2: Ada IN hari ini - work shift scenario");
            
            // Count work shift OUT (exclude lembur OUT)
            // OUT untuk lembur adalah yang punya keterangan seperti 'lembur' atau 'system otomatis'
            $work_shift_out_query = mysqli_query($conn, "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = $user_id AND DATE(created_at) = '$today' AND status = 'OUT' 
                AND keterangan NOT LIKE '%lembur%' AND keterangan NOT LIKE '%system%'
            ");
            if(!$work_shift_out_query) {
                writeAttendanceLog("ERROR: work_shift_out_query failed - " . mysqli_error($conn));
                return ['valid' => false, 'message' => 'Database error'];
            }
            $work_shift_out_result = mysqli_fetch_assoc($work_shift_out_query);
            $work_shift_out_count = intval($work_shift_out_result['cnt'] ?? 0);
            writeAttendanceLog("Work shift OUT count (excluding lembur): $work_shift_out_count");
            
            // Jika belum OUT untuk work shift → allow OUT
            if($work_shift_out_count === 0) {
                writeAttendanceLog("ALLOW: Belum OUT untuk work shift, boleh OUT sekarang");
                return [
                    'valid' => true,
                    'message' => 'OUT diterima'
                ];
            }
            
            // Jika sudah OUT sekali untuk work shift → reject
            if($work_shift_out_count >= 1) {
                writeAttendanceLog("REJECT: Sudah OUT 1x setelah IN, tidak boleh OUT lagi");
                return [
                    'valid' => false,
                    'message' => 'Anda sudah keluar 1 kali hari ini.'
                ];
            }
        }
        
        // Fallback (shouldn't reach here)
        writeAttendanceLog("REJECT: Tidak ada IN dan tidak ada outstanding lembur");
        return [
            'valid' => false,
            'message' => 'Anda belum masuk hari ini. Silakan masuk terlebih dahulu sebelum keluar.'
        ];
    }
    
    writeAttendanceLog("validateDailyLimit PASSED - status valid");
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
    $labor_id = intval($labor_id);
    
    // Get settings dari database
    $labor = getLaborSettings($labor_id, $conn);
    if (!$labor) {
        // Default fallback
        $jam_masuk = '08:00:00';
        $jam_pulang = '16:00:00';
        $toleransi = 15;
        writeAttendanceLog("WARNING: Using default times for hitungKeterangan, labor_id $labor_id not found");
    } else {
        $jam_masuk = $labor['jam_masuk_standar'] ?? '08:00:00';
        $jam_pulang = $labor['jam_pulang_standar'] ?? '16:00:00';
        $toleransi = intval($labor['toleransi_terlambat'] ?? 15);
    }
    
    // Extract waktu dari datetime
    $jam_att = date('H:i:s', strtotime($waktu_attendance));
    
    if ($status === 'IN') {
        // Untuk masuk: check apakah terlambat
        // jam_masuk + toleransi = batas maksimal tepat waktu
        $batas_jam = strtotime($jam_masuk) + ($toleransi * 60);
        $batas_jam_str = date('H:i:s', $batas_jam);
        
        $jam_att_time = strtotime($jam_att);
        
        if ($jam_att_time > $batas_jam) {
            return 'terlambat';
        } else {
            return 'tepat waktu';
        }
        
    } else if ($status === 'OUT') {
        // Untuk keluar: check apakah lembur
        // jam_pulang = batas normal pulang
        // Jika lebih dari jam_pulang = lembur, TANPA tolerance
        
        $jam_att_time = strtotime($jam_att);
        $jam_pulang_time = strtotime($jam_pulang);
        
        if ($jam_att_time > $jam_pulang_time) {
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
    // Normalize keterangan untuk matching
    $keterangan_lower = strtolower($keterangan);
    
    // Check by pattern (untuk handle 'lembur - submitted system otomatis' dll)
    if(strpos($keterangan_lower, 'lembur') !== false) {
        return [
            'class' => 'badge-warning',
            'label' => $keterangan, // Show full text
            'icon' => 'fas fa-hourglass-end',
            'color' => '#f59e0b'
        ];
    } else if(strpos($keterangan_lower, 'terlambat') !== false) {
        return [
            'class' => 'badge-danger',
            'label' => $keterangan,
            'icon' => 'fas fa-clock',
            'color' => '#ef4444'
        ];
    } else if(strpos($keterangan_lower, 'tepat') !== false) {
        return [
            'class' => 'badge-success',
            'label' => $keterangan,
            'icon' => 'fas fa-check-circle',
            'color' => '#10b981'
        ];
    }
    
    // Default fallback
    return [
        'class' => 'badge-secondary',
        'label' => $keterangan ?? 'Normal',
        'icon' => 'fas fa-minus-circle',
        'color' => '#64748b'
    ];
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
