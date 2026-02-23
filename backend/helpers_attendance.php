<?php
// backend/helpers_attendance.php
// Helper functions untuk attendance keterangan

/**
 * Helper untuk write log ke file
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
 * Validasi waktu attendance menggunakan server time (tidak bisa dimanipulasi)
 * @param string $status 'IN' atau 'OUT'
 * @param int $user_id User ID (optional, untuk check overtime)
 * @param mysqli $conn Database connection (optional)
 * @return array ['valid' => bool, 'message' => string, 'current_time' => string]
 */
function validateAttendanceTime($status, $user_id = null, $conn = null) {
    // Gunakan server time (WIB) - tidak bisa dimanipulasi dari client
    $current_time = date('H:i:s');
    $current_hours = date('H');
    $current_minutes = date('i');
    
    // AGGRESSIVE LOGGING - untuk debug parameter
    writeAttendanceLog("========== validateAttendanceTime START ==========");
    writeAttendanceLog("STATUS: $status");
    writeAttendanceLog("USER_ID: " . var_export($user_id, true));
    writeAttendanceLog("USER_ID > 0: " . ($user_id > 0 ? 'YES' : 'NO'));
    writeAttendanceLog("CONN: " . ($conn ? 'OK (type: ' . get_class($conn) . ')' : 'NULL'));
    writeAttendanceLog("CURRENT_TIME: $current_time (hour: $current_hours)");
    
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
        // Absen KELUAR: default mulai dari 16:00
        // EXCEPTION: Jika ada IN kemarin yang belum OUT (lembur lintas hari), allow OUT kapan saja
        $min_hour = 16;
        
        writeAttendanceLog("Processing OUT status...");
        writeAttendanceLog("Min hour required: $min_hour, Current hour: $current_hours");
        
        // Check apakah ada outstanding IN dari kemarin (lembur lintas hari)
        $allow_anytime_out = false;
        if($user_id !== null && $user_id > 0 && $conn !== null) {
            writeAttendanceLog("Parameters OK - proceeding to check outstanding IN");
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            // Debug: Log kemarin date
            writeAttendanceLog("YESTERDAY: $yesterday");
            
            // Cek IN kemarin
            $yesterday_in_query = "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = $user_id 
                AND DATE(created_at) = '$yesterday' 
                AND status = 'IN'
            ";
            writeAttendanceLog("QUERY IN: $yesterday_in_query");
            
            $yesterday_in_result = mysqli_query($conn, $yesterday_in_query);
            
            if(!$yesterday_in_result) {
                writeAttendanceLog("ERROR query IN kemarin: " . mysqli_error($conn));
                $yesterday_in_count = 0;
            } else {
                $result = mysqli_fetch_assoc($yesterday_in_result);
                $yesterday_in_count = $result ? intval($result['cnt']) : 0;
                writeAttendanceLog("RESULT - IN Count kemarin: $yesterday_in_count");
            }
            
            // Cek OUT kemarin
            $yesterday_out_query = "
                SELECT COUNT(*) as cnt FROM attendance_logs 
                WHERE user_id = $user_id 
                AND DATE(created_at) = '$yesterday' 
                AND status = 'OUT'
            ";
            writeAttendanceLog("QUERY OUT: $yesterday_out_query");
            
            $yesterday_out_result = mysqli_query($conn, $yesterday_out_query);
            
            if(!$yesterday_out_result) {
                writeAttendanceLog("ERROR query OUT kemarin: " . mysqli_error($conn));
                $yesterday_out_count = 0;
            } else {
                $result = mysqli_fetch_assoc($yesterday_out_result);
                $yesterday_out_count = $result ? intval($result['cnt']) : 0;
                writeAttendanceLog("RESULT - OUT Count kemarin: $yesterday_out_count");
            }
            
            writeAttendanceLog("SUMMARY - yesterday_in: $yesterday_in_count, yesterday_out: $yesterday_out_count");
            
            // Jika kemarin ada IN tapi tidak ada OUT = lembur lintas hari
            if($yesterday_in_count > 0 && $yesterday_out_count === 0) {
                // Outstanding IN exists - tapi hanya allow anytime OUT untuk FIRST OUT hari ini (untuk close lembur)
                // Jika sudah ada OUT hari ini, maka require 16:00 untuk OUT berikutnya
                
                $today = date('Y-m-d');
                $today_out_query = "
                    SELECT COUNT(*) as cnt FROM attendance_logs 
                    WHERE user_id = $user_id 
                    AND DATE(created_at) = '$today' 
                    AND status = 'OUT'
                ";
                writeAttendanceLog("Checking if already OUTed today...");
                writeAttendanceLog("QUERY TODAY OUT: $today_out_query");
                
                $today_out_result = mysqli_query($conn, $today_out_query);
                if(!$today_out_result) {
                    writeAttendanceLog("ERROR query OUT today: " . mysqli_error($conn));
                    $today_out_count = 0;
                } else {
                    $result = mysqli_fetch_assoc($today_out_result);
                    $today_out_count = $result ? intval($result['cnt']) : 0;
                    writeAttendanceLog("OUT count today: $today_out_count");
                }
                
                // Hanya allow anytime OUT jika belum ada OUT hari ini
                if($today_out_count === 0) {
                    $allow_anytime_out = true;
                    writeAttendanceLog("✅ DECISION: Outstanding IN detected + No OUT today yet = Allow OUT anytime (close lembur)");
                } else {
                    $allow_anytime_out = false;
                    writeAttendanceLog("⚠️  DECISION: Outstanding IN detected BUT already OUTed today = Require 16:00 for next OUT");
                }
            } else {
                writeAttendanceLog("❌ DECISION: No outstanding IN");
            }
        } else {
            writeAttendanceLog("⚠️  Parameters MISSING or INVALID for outstanding IN check");
            writeAttendanceLog("  - user_id is null: " . ($user_id === null ? 'YES' : 'NO'));
            writeAttendanceLog("  - user_id <= 0: " . ($user_id <= 0 ? 'YES' : 'NO'));
            writeAttendanceLog("  - conn is null: " . ($conn === null ? 'YES' : 'NO'));
        }
        
        writeAttendanceLog("ALLOW_ANYTIME_OUT: " . ($allow_anytime_out ? 'YES' : 'NO'));
        
        // Jika bukan lembur lintas hari, enforce jam 16:00
        if(!$allow_anytime_out && $current_hours < $min_hour) {
            writeAttendanceLog("❌ REJECT: Jam terlalu awal ($current_time) dan tidak ada outstanding IN");
            return [
                'valid' => false,
                'message' => 'Absensi pulang dimulai dari jam 16:00 WIB. Waktu server: ' . $current_time,
                'current_time' => $current_time
            ];
        }
        
        writeAttendanceLog("✅ ALLOW OUT: Time validation passed");
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
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    writeAttendanceLog("=== validateDailyLimit START - user_id: $user_id, status: $status ===");
    
    // Check apakah ada outstanding IN dari kemarin (lembur lintas hari)
    // Outstanding = ada IN kemarin tapi TIDAK ada OUT (bisa OUT kemarin atau hari ini)
    $yesterday_in_query = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM attendance_logs 
        WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'IN'
    ");
    if(!$yesterday_in_query) {
        writeAttendanceLog("ERROR: yesterday_in_query failed - " . mysqli_error($conn));
        return ['valid' => false, 'message' => 'Database error'];
    }
    $yesterday_in_result = mysqli_fetch_assoc($yesterday_in_query);
    $yesterday_in_count = intval($yesterday_in_result['cnt'] ?? 0);
    
    // Check OUT dari kemarin
    $yesterday_out_query = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM attendance_logs 
        WHERE user_id = $user_id AND DATE(created_at) = '$yesterday' AND status = 'OUT'
    ");
    if(!$yesterday_out_query) {
        writeAttendanceLog("ERROR: yesterday_out_query failed - " . mysqli_error($conn));
        return ['valid' => false, 'message' => 'Database error'];
    }
    $yesterday_out_result = mysqli_fetch_assoc($yesterday_out_query);
    $yesterday_out_count = intval($yesterday_out_result['cnt'] ?? 0);
    
    // Check OUT dari hari ini (auto-OUT system membuat OUT di hari ini untuk tutup lembur kemarin)
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
    
    // Outstanding check: ada IN kemarin TAPI belum ada OUT (baik kemarin atau hari ini)
    $total_out_count = $yesterday_out_count + $today_out_count;
    $has_outstanding_in = ($yesterday_in_count > 0 && $total_out_count === 0);
    writeAttendanceLog("Outstanding IN: yesterday_in=$yesterday_in_count, yesterday_out=$yesterday_out_count, today_out=$today_out_count, total_out=$total_out_count, has_outstanding=$has_outstanding_in");
    
    if($status === 'IN') {
        writeAttendanceLog("Validating IN request...");
        
        // CRITICAL RULE: Jika ada outstanding IN dari kemarin, TIDAK BOLEH IN sampai OUT lembur dulu!
        if($has_outstanding_in) {
            writeAttendanceLog("REJECT: User has outstanding IN from yesterday - must OUT first!");
            return [
                'valid' => false,
                'message' => 'Anda masih memiliki jam kerja lembur yang belum diselesaikan dari kemarin. Silakan keluar (OUT) lembur terlebih dahulu sebelum masuk kembali.'
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
