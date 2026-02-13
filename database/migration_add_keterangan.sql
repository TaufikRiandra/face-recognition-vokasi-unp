-- Migration: Add Keterangan Column to Attendance
-- File: database/migration_add_keterangan.sql

-- 1. Add keterangan column to attendance_logs
ALTER TABLE `attendance_logs` 
ADD COLUMN `keterangan` VARCHAR(50) DEFAULT 'normal' COMMENT 'normal, terlambat, lembur' AFTER `stored_user_nama`;

-- 2. Add jam_masuk_standar and jam_pulang_standar to labor
ALTER TABLE `labor` 
ADD COLUMN `jam_masuk_standar` TIME DEFAULT '09:30:00' COMMENT 'Jam masuk standar (batas akhir masuk: 09:30)',
ADD COLUMN `jam_pulang_standar` TIME DEFAULT '18:30:00' COMMENT 'Jam pulang standar (batas pulang: 18:30, jika lewat = lembur)',
ADD COLUMN `toleransi_terlambat` INT DEFAULT 1 COMMENT 'Toleransi terlambat dalam menit (default: 1 menit)' AFTER `jam_pulang_standar`;

-- 3. Create trigger untuk otomatis hitung keterangan saat INSERT
DELIMITER //

CREATE TRIGGER tr_attendance_keterangan_insert 
BEFORE INSERT ON attendance_logs 
FOR EACH ROW
BEGIN
    DECLARE v_jam_standar TIME;
    DECLARE v_jam_pulang_standar TIME;
    DECLARE v_toleransi INT;
    DECLARE v_jam_attendance TIME;
    
    -- Get jam standar dari labor
    SELECT jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
    INTO v_jam_standar, v_jam_pulang_standar, v_toleransi
    FROM labor 
    WHERE id = NEW.labor_id;
    
    -- Set default jika tidak ada setting
    IF v_jam_standar IS NULL THEN
        SET v_jam_standar = '09:30:00';
        SET v_jam_pulang_standar = '18:30:00';
        SET v_toleransi = 1;
    END IF;
    
    -- Get waktu attendance
    SET v_jam_attendance = TIME(NEW.created_at);
    
    -- Tentukan keterangan berdasarkan status
    IF NEW.status = 'IN' THEN
        -- Untuk IN/Masuk: jika > jam_masuk_standar + toleransi = terlambat
        IF v_jam_attendance > ADDTIME(v_jam_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
            SET NEW.keterangan = 'terlambat';
        ELSE
            SET NEW.keterangan = 'tepat waktu';
        END IF;
    ELSEIF NEW.status = 'OUT' THEN
        -- Untuk OUT/Pulang: jika > jam_pulang_standar + toleransi = lembur
        IF v_jam_attendance > ADDTIME(v_jam_pulang_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
            SET NEW.keterangan = 'lembur';
        ELSE
            SET NEW.keterangan = 'tepat waktu';
        END IF;
    END IF;
END //

DELIMITER ;

-- 4. Update existing records dengan keterangan
UPDATE attendance_logs al
JOIN labor l ON al.labor_id = l.id
SET keterangan = CASE 
    WHEN al.status = 'IN' AND TIME(al.created_at) > ADDTIME(l.jam_masuk_standar, SEC_TO_TIME(COALESCE(l.toleransi_terlambat, 1) * 60)) THEN 'terlambat'
    WHEN al.status = 'OUT' AND TIME(al.created_at) > ADDTIME(l.jam_pulang_standar, SEC_TO_TIME(COALESCE(l.toleransi_terlambat, 1) * 60)) THEN 'lembur'
    ELSE 'tepat waktu'
END;

-- 5. Additional: Create stored procedure untuk get attendance dengan keterangan
DELIMITER //

CREATE PROCEDURE sp_get_attendance_report(
    IN p_labor_id INT,
    IN p_date_from DATE,
    IN p_date_to DATE
)
BEGIN
    SELECT 
        a.id,
        a.user_id,
        u.nama,
        u.nim,
        a.status,
        a.keterangan,
        TIME(a.created_at) as jam_attendance,
        DATE(a.created_at) as tanggal,
        l.nama as labor_nama,
        a.confidence_score
    FROM attendance_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN labor l ON a.labor_id = l.id
    WHERE (p_labor_id = 0 OR a.labor_id = p_labor_id)
    AND DATE(a.created_at) BETWEEN p_date_from AND p_date_to
    ORDER BY a.created_at DESC;
END //

DELIMITER ;

-- Notes:
-- 1. Jalankan script ini untuk migrate database
-- 2. Keterangan otomatis dihitung saat insert attendance
-- 3. Toleransi terlambat default 15 menit (bisa diubah per labor)
-- 4. Untuk existing data, jalankan UPDATE statement nomor 4
