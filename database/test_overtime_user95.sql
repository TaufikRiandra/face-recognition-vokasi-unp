-- Test Query untuk User ID 96 - Skenario Lembur Lintas Hari
-- Demonstrasi: IN hari 1 jam 23:00, OUT hari 2 pukul 05:00 (sebelum jam 16:00, tapi diizinkan)

use absensi_labor; 

-- 1. Cek data user (pastikan user ada)
SELECT id, nama, nim FROM users WHERE id = 96;

-- 2. Cek data labor untuk melihat jam standar
SELECT * FROM labor WHERE id = 3;

-- 3. Insert test data - Lembur Hari 1 (kemarin): IN jam 23:00, tanpa OUT
-- Gunakan tanggal kemarin
INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (
  96, 
  3, 
  'IN', 
  0.98, 
  'User 96', 
  'lembur',
  CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 23:00:00')
);

-- 4. Verifikasi data kemarin (should show: 1 IN, 0 OUT untuk hari kemarin)
SELECT 
  DATE(created_at) as tanggal,
  status,
  COUNT(*) as jumlah,
  GROUP_CONCAT(TIME(created_at)) as waktu_list,
  GROUP_CONCAT(keterangan) as keterangan_list
FROM attendance_logs
WHERE user_id = 96
GROUP BY DATE(created_at), status
ORDER BY tanggal DESC;

-- 5. Test Case 1: OUT sebelum jam 16:00 hari ini (seharusnya diizinkan karena lembur)
-- Skenario: OUT jam 05:00 untuk tutup lembur kemarin
INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (
  96, 
  3, 
  'OUT', 
  0.98, 
  'User 96', 
  'lembur',
  CONCAT(CURDATE(), ' 05:00:00')
);

-- 6. Verifikasi setelah OUT lembur
SELECT 
  DATE(created_at) as tanggal,
  status,
  COUNT(*) as jumlah,
  GROUP_CONCAT(TIME(created_at)) as waktu_list,
  GROUP_CONCAT(keterangan) as keterangan_list
FROM attendance_logs
WHERE user_id = 96
GROUP BY DATE(created_at), status
ORDER BY tanggal DESC;

-- 7. Test Case 2: Kemudian IN normal hari ini (jam 09:30)
INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (
  96, 
  3, 
  'IN', 
  0.98, 
  'User 96', 
  'tepat waktu',
  CONCAT(CURDATE(), ' 09:30:00')
);

-- 8. Test Case 3: OUT normal hari ini (jam 18:30)
INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (
  96, 
  3, 
  'OUT', 
  0.98, 
  'User 96', 
  'tepat waktu',
  CONCAT(CURDATE(), ' 18:30:00')
);

-- 9. Final Summary - Lihat semua data user 96
SELECT 
  id,
  DATE(created_at) as tanggal,
  TIME(created_at) as waktu,
  status,
  keterangan,
  confidence_score,
  stored_user_nama
FROM attendance_logs
WHERE user_id = 96
ORDER BY created_at DESC;

-- 10. Summary statistik per hari
SELECT 
  DATE(al.created_at) as tanggal,
  SUM(CASE WHEN al.status = 'IN' THEN 1 ELSE 0 END) as in_count,
  SUM(CASE WHEN al.status = 'OUT' THEN 1 ELSE 0 END) as out_count,
  GROUP_CONCAT(DISTINCT al.keterangan SEPARATOR ', ') as keterangan_list
FROM attendance_logs al
WHERE al.user_id = 96
GROUP BY DATE(al.created_at)
ORDER BY tanggal DESC;

-- ========== EXPECTED RESULT =========
-- Hari kemarin: 1 IN (23:00, lembur), 0 OUT
--    └─ ini trigger kondisi "outstanding IN"
-- 
-- Hari ini: 
--   - 1 OUT (05:00, lembur) → ALLOWED karena ada outstanding IN kemarin
--   - 1 IN (09:30, tepat waktu) → ALLOWED setelah OUT lembur
--   - 1 OUT (18:30, tepat waktu) → ALLOWED normal
-- 
-- Total: Kemarin 1 record, Hari ini 3 record
-- Status Flow: [IN-23:00] → [OUT-05:00] → [IN-09:30] → [OUT-18:30]
-- ====================================
