-- Add jam_pulang_akhir column to labor table
-- This defines the latest time users can clock out (e.g., 20:00)
-- jam_pulang_standar = normal end time (e.g., 18:00 = overtime if after this)
-- jam_pulang_akhir = absolute deadline (e.g., 20:00 = must not exceed this)

ALTER TABLE labor ADD COLUMN jam_pulang_akhir TIME DEFAULT '20:00:00' COMMENT 'Jam terakhir bisa absen pulang (e.g., 20:00)' AFTER `jam_pulang_standar`;

-- Update existing labor records with jam_pulang_akhir if needed
UPDATE labor SET jam_pulang_akhir = '20:00:00' WHERE jam_pulang_akhir IS NULL;
