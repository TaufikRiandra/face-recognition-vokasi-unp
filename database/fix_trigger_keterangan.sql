USE absensi_labor;

-- Fix Trigger untuk menghormati lembur lintas hari
-- Drop trigger lama terlebih dahulu
DROP TRIGGER IF EXISTS tr_attendance_keterangan_insert;

-- Create trigger baru yang respect lembur lintas hari
DELIMITER $$

CREATE TRIGGER tr_attendance_keterangan_insert
BEFORE INSERT ON attendance_logs 
FOR EACH ROW
BEGIN
    DECLARE v_jam_standar TIME;
    DECLARE v_jam_pulang_standar TIME;
    DECLARE v_toleransi INT;
    DECLARE v_jam_attendance TIME;
    DECLARE v_yesterday_in_count INT;
    DECLARE v_yesterday_out_count INT;
    DECLARE v_today_out_count INT;
    
    -- Jika keterangan sudah di-set oleh aplikasi ke 'lembur', jangan override
    -- Aplikasi will explicitly set 'lembur' untuk lembur lintas hari
    IF NEW.keterangan = 'lembur' THEN
        -- Trust application - it already determined this is overtime
        RETURN;
    END IF;
    
    -- Get labor settings
    SELECT jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
    INTO v_jam_standar, v_jam_pulang_standar, v_toleransi
    FROM labor 
    WHERE id = NEW.labor_id;
    
    IF v_jam_standar IS NULL THEN
        SET v_jam_standar = '09:30:00';
        SET v_jam_pulang_standar = '18:30:00';
        SET v_toleransi = 1;
    END IF;
    
    SET v_jam_attendance = TIME(NEW.created_at);
    
    -- Check outstanding IN dari kemarin (untuk reference only)
    SELECT COUNT(*) INTO v_yesterday_in_count 
    FROM attendance_logs 
    WHERE user_id = NEW.user_id 
      AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
      AND status = 'IN';
    
    SELECT COUNT(*) INTO v_yesterday_out_count 
    FROM attendance_logs 
    WHERE user_id = NEW.user_id 
      AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
      AND status = 'OUT';
    
    -- Calculate keterangan based on time
    IF NEW.status = 'IN' THEN
        IF v_jam_attendance > ADDTIME(v_jam_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
            SET NEW.keterangan = 'terlambat';
        ELSE
            SET NEW.keterangan = 'tepat waktu';
        END IF;
    ELSEIF NEW.status = 'OUT' THEN
        -- OUT: cek apakah melebihi jam pulang standar
        IF v_jam_attendance > ADDTIME(v_jam_pulang_standar, SEC_TO_TIME(v_toleransi * 60)) THEN
            SET NEW.keterangan = 'lembur';
        ELSE
            SET NEW.keterangan = 'tepat waktu';
        END IF;
    END IF;
END$$

DELIMITER ;

-- Test query untuk verify
SELECT 'Trigger created successfully' as status;
SELECT 
    TRIGGER_SCHEMA,
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = 'absensi_labor' 
  AND EVENT_OBJECT_TABLE = 'attendance_logs';
