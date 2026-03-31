-- Add jam_masuk_awal column to labor table
-- This defines when users can START clocking in (e.g., 06:00)
-- jam_masuk_standar = deadline for IN to be "on time" (e.g., 09:30)

ALTER TABLE labor ADD COLUMN jam_masuk_awal TIME DEFAULT '06:00:00' COMMENT 'Jam mulai bisa absen masuk (e.g., 06:00)' AFTER `toleransi_terlambat`;

-- Update existing labor records with jam_masuk_awal if needed
UPDATE labor SET jam_masuk_awal = '06:00:00' WHERE jam_masuk_awal IS NULL;
