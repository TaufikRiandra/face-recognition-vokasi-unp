-- DEBUG QUERY: Check apakah outstanding IN dari kemarin terdeteksi dengan benar
-- Ganti USER_ID_DISINI dengan ID user yang ingin di-test (misal 96)

SET @user_id = 96;
SET @today = CURDATE();
SET @yesterday = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

-- Query 1: Check semua data user di 2 hari terakhir
SELECT 
  'RAW DATA KEMARIN & HARI INI' as info,
  id,
  DATE(created_at) as tanggal,
  TIME(created_at) as waktu,
  status,
  keterangan,
  stored_user_nama
FROM attendance_logs
WHERE user_id = @user_id
  AND DATE(created_at) >= @yesterday
  AND DATE(created_at) <= @today
ORDER BY created_at DESC;

-- Query 2: Hitung IN kemarin (copy dari logic di helpers_attendance.php)
SELECT 
  'HITUNG IN KEMARIN' as info,
  COUNT(*) as in_count
FROM attendance_logs 
WHERE user_id = @user_id 
  AND DATE(created_at) = @yesterday 
  AND status = 'IN';

-- Query 3: Hitung OUT kemarin (copy dari logic di helpers_attendance.php)
SELECT 
  'HITUNG OUT KEMARIN' as info,
  COUNT(*) as out_count
FROM attendance_logs 
WHERE user_id = @user_id 
  AND DATE(created_at) = @yesterday 
  AND status = 'OUT';

-- Query 4: Kombinasi - Deteksi Outstanding IN (sama dengan logika di code)
SELECT 
  'OUTSTANDING IN DETECTION' as info,
  (SELECT COUNT(*) FROM attendance_logs 
   WHERE user_id = @user_id AND DATE(created_at) = @yesterday AND status = 'IN') as yesterday_in_count,
  (SELECT COUNT(*) FROM attendance_logs 
   WHERE user_id = @user_id AND DATE(created_at) = @yesterday AND status = 'OUT') as yesterday_out_count,
  CASE 
    WHEN (SELECT COUNT(*) FROM attendance_logs 
          WHERE user_id = @user_id AND DATE(created_at) = @yesterday AND status = 'IN') > 0
    AND (SELECT COUNT(*) FROM attendance_logs 
         WHERE user_id = @user_id AND DATE(created_at) = @yesterday AND status = 'OUT') = 0
    THEN 'YES - ADA OUTSTANDING IN (Allow OUT anytime)'
    ELSE 'NO - Tidak ada outstanding IN (OUT only after 16:00)'
  END as status;

-- Query 5: IN hari ini
SELECT 
  'HITUNG IN HARI INI' as info,
  COUNT(*) as in_count
FROM attendance_logs 
WHERE user_id = @user_id 
  AND DATE(created_at) = @today 
  AND status = 'IN';

-- Query 6: OUT hari ini
SELECT 
  'HITUNG OUT HARI INI' as info,
  COUNT(*) as out_count
FROM attendance_logs 
WHERE user_id = @user_id 
  AND DATE(created_at) = @today 
  AND status = 'OUT';

-- Query 7: Detail data per jam hari ini
SELECT 
  'DATA HARI INI PER JAM' as info,
  TIME(created_at) as waktu,
  status,
  keterangan
FROM attendance_logs
WHERE user_id = @user_id
  AND DATE(created_at) = @today
ORDER BY created_at ASC;

-- ===== EXPECTED RESULT UNTUK LEMBUR LINTAS HARI =====
-- Query 2 result: in_count = 1 (ada IN kemarin)
-- Query 3 result: out_count = 0 (tidak ada OUT kemarin)
-- Query 4 result: status = 'YES - ADA OUTSTANDING IN'
-- 
-- Jika result ini muncul, seharusnya user bisa OUT hari ini kapan saja (tidak terbatas jam 16:00)
-- ====================================================
