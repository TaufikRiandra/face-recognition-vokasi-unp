-- ===============================================
-- FIX SCHEDULE CONFIGURATION FOR LABOR TEFA
-- ===============================================
-- This script updates the Labor Tefa (ID 3) configuration to match requirements:
-- Entry: 09:00 with 15-minute tolerance (late after 09:15)
-- Exit: 16:00 (4:00 PM)
-- Tolerance: 15 minutes

UPDATE labor 
SET 
    jam_masuk_standar = '09:00:00',
    jam_pulang_standar = '16:00:00', 
    toleransi_terlambat = 15
WHERE id = 3;

-- Verify the update
SELECT id, nama, jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
FROM labor 
WHERE id = 3;

-- Expected output:
-- id=3, nama=Labor Tefa, jam_masuk_standar=09:00:00, jam_pulang_standar=16:00:00, toleransi_terlambat=15
