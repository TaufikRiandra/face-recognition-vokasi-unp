# Test Lembur Lintas Hari - Updated Version

## 🎯 Quick Test

### Step 1: Pastikan data kemarin sudah OK
Jalankan query di database:
```sql
SELECT * FROM attendance_logs 
WHERE user_id = 96 
AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
AND status = 'IN';
```

**Hasil yang diexpect:** 1 row (ada 1 IN kemarin)

Jika tidak ada, insert manual:
```sql
INSERT INTO attendance_logs (user_id, labor_id, status, confidence_score, stored_user_nama, keterangan, created_at)
VALUES (96, 3, 'IN', 0.98, 'User 96', 'lembur', 
        CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 23:00:00'));
```

### Step 2: Clear debug log
```powershell
New-Item -Path "C:\Users\Taufik\face-recognition\attendance_debug.log" -Force | Out-Null
```

### Step 3: Submit OUT dari UI
- User ID: 96 (atau ID yang memiliki outstanding IN)
- Labor: Labor Tefa
- **Status: Pilih "Keluar (OUT)"**
- Click "Simpan Absensi"

### Step 4: Buka Log File
```powershell
Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log" -Tail 100
```

---

## ✅ Expected Output (Jika Berhasil)

Log file seharusnya menampilkan:

```
[...] === ATTENDANCE REQUEST ===
[...] Action: submit_attendance
[...] === SUBMIT_ATTENDANCE START ===
[...] user_id: 96, status: OUT, labor_id: 3

[...] DEBUG validateAttendanceTime - user_id: 96, yesterday: 2026-02-22
[...] DEBUG - yesterday_in_count: 1
[...] DEBUG - yesterday_out_count: 0
[...] DEBUG - Outstanding IN detected for user 96! Allow OUT anytime
[...] ALLOW OUT: Time validation passed (current_time: 14:25:30, allow_anytime_out: yes)
[...] Time validation result: {"valid":true,"message":"Waktu valid","current_time":"..."}

[...] === validateDailyLimit START - user_id: 96, status: OUT ===
[...] Outstanding IN: yesterday_in=1, yesterday_out=0, has_outstanding=1
[...] validateDailyLimit PASSED - status valid
[...] Daily limit validation result: {"valid":true,"message":"Valid"}

[...] Last status today: NULL
[...] Outstanding IN check - yesterday_in: 1, yesterday_out: 0, has_outstanding: YES
[...] ALLOWED: Outstanding IN, no log today, OUT first time (close overtime)
[...] ALLOWED: Status transition valid - lastStatus: NULL, status: OUT
[...] Keterangan: lembur
[...] SUCCESS: Attendance recorded - attendance_id: 12345
```

✅ **Jika loopnya seperti ini → BERHASIL! User bisa OUT sebelum jam 16:00**

---

## ❌ Jika Masih Error

### Error: "Absensi pulang dimulai dari jam 16:00"

Check log untuk:
```
[...] DEBUG - yesterday_in_count: 0
[...] DEBUG - yesterday_out_count: 0
[...] DEBUG - No outstanding IN for user 96
[...] REJECT OUT: Jam terlalu awal dan tidak ada outstanding IN
```

**Fix:**
1. Verify data kemarin dengan SQL query di atas
2. Insert data kemarin jika belum ada
3. Clear log dan test ulang

### Error: Outstanding IN terdeteksi tapi masih ditolak

Share log file lengkapnya ke sini, saya trace lebih lanjut.

---

## 📊 Verification Database

Jika sudah sukses, query ini seharusnya menunjukkan 3 record:

```sql
SELECT 
  user_id,
  DATE(created_at) as tanggal,
  TIME(created_at) as waktu,
  status,
  keterangan
FROM attendance_logs
WHERE user_id = 96
ORDER BY created_at DESC
LIMIT 3;
```

Expected result:
```
user_id | tanggal    | waktu    | status | keterangan
--------|------------|----------|--------|----------
96      | 2026-02-23 | 18:30:00 | OUT    | tepat waktu   (hari ini - normal OUT)
96      | 2026-02-23 | 09:30:00 | IN     | tepat waktu   (hari ini - normal IN)
96      | 2026-02-23 | 05:00:00 | OUT    | lembur        (hari ini - close lembur kemarin) ← YANG PENTING
96      | 2026-02-22 | 23:00:00 | IN     | lembur        (kemarin)
```

Jika sudah seperti ini → **SUKSESSS! 🎉**

---

## 🚀 Next Step

1. Jalankan Step 1-4 di atas
2. Cek log file
3. Jika ERROR: Share log file lengkapnya
4. Jika OK: Coba test dengan data real (tidak manual insert)
