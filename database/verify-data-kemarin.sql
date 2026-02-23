-- VERIFY & FIX: Data Kemarin untuk Lembur Lintas Hari

-- 1. CHECK: Apakah ada IN kemarin untuk user 96?
SELECT 
  'STATUS: Cek IN kemarin user 96' as info,
  COUNT(*) as total_in_kemarin,
  GROUP_CONCAT(TIME(created_at)) as waktu_in,
  DATE(created_at) as tanggal
FROM attendance_logs
WHERE user_id = 96 
  AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
  AND status = 'IN'
GROUP BY DATE(created_at);

-- 2. CHECK: Apakah ada OUT kemarin untuk user 96?
SELECT 
  'STATUS: Cek OUT kemarin user 96' as info,
  COUNT(*) as total_out_kemarin,
  GROUP_CONCAT(TIME(created_at)) as waktu_out,
  DATE(created_at) as tanggal
FROM attendance_logs
WHERE user_id = 96 
  AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
  AND status = 'OUT'
GROUP BY DATE(created_at);

-- 3. SUMMARY: Status outstanding IN
SELECT 
  CASE 
    WHEN (SELECT COUNT(*) FROM attendance_logs WHERE user_id = 96 AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status = 'IN') > 0
    AND (SELECT COUNT(*) FROM attendance_logs WHERE user_id = 96 AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status = 'OUT') = 0
    THEN '✅ ADA OUTSTANDING IN - User 96 bisa OUT kapan saja hari ini'
    WHEN (SELECT COUNT(*) FROM attendance_logs WHERE user_id = 96 AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status = 'IN') = 0
    THEN '❌ TIDAK ADA IN KEMARIN - Outstanding IN tidak terdeteksi. Harus insert data kemarin dulu!'
    ELSE '⚠️ INCOMPLETE - Ada IN tapi sudah OUT kemarin'
  END as status_lembur;

-- ===== JIKA HASIL QUERY #3 MENUNJUKKAN ❌ ATAU ⚠️ =====
-- JALANKAN QUERY INI UNTUK INSERT DATA KEMARIN:

INSERT INTO attendance_logs 
(user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (
  96, 
  3, 
  'IN', 
  0.98, 
  'User 96', 
  'lembur',
  CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 23:00:00')
);

-- Verify setelah insert:
SELECT 'VERIFY AFTER INSERT' as info;
SELECT 
  user_id,
  DATE(created_at) as tanggal,
  TIME(created_at) as waktu,
  status,
  keterangan
FROM attendance_logs
WHERE user_id = 96
ORDER BY created_at DESC
LIMIT 5;
