# Panduan Cek Log File - Laragon Version

## 📍 Lokasi Log File

Mulai sekarang, setiap request attendance akan di-log ke file terpisah yang mudah diakses:

```
C:\Users\Taufik\face-recognition\attendance_debug.log
```

File ini akan **automatically created** saat pertama kali ada request attendance.

---

## 🔍 Cara Membaca Log File

### Method 1: Open dengan Text Editor
```
1. Buka File Explorer
2. Navigate ke: C:\Users\Taufik\face-recognition\
3. Cari file: attendance_debug.log
4. Double-click atau buka dengan Notepad/VS Code
5. Scroll ke paling bawah untuk melihat log terbaru
```

### Method 2: Command Line (PowerShell)
```powershell
# Baca 50 baris terakhir:
Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log" -Tail 50

# Atau follow real-time (seperti tail -f):
Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log" -Tail 50 -Wait
```

### Method 3: Clear Log & Start Fresh
```powershell
# Clear file:
Clear-Content "C:\Users\Taufik\face-recognition\attendance_debug.log"

# Verify:
Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log"
```

---

## 🧪 Langkah Test (Revised)

### Step 1: Clear Log Lama
```powershell
Clear-Content "C:\Users\Taufik\face-recognition\attendance_debug.log"
```

### Step 2: Submit Attendance dari UI
- Buka halaman attendance
- Pilih user 96 (atau user yang punya outstanding IN)
- Pilih Labor 3
- Click "OUT"
- Submit

### Step 3: Cek Log File
```powershell
Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log" -Tail 100
```

---

## 📊 Expected Log Output (jika semua OK)

```
====================================================================================================
[2026-02-23 14:25:30] 12345 === ATTENDANCE REQUEST ===
[2026-02-23 14:25:30] 12345 Session admin_id: 1
[2026-02-23 14:25:30] 12345 POST data: {"action":"submit_attendance","user_id":"96","status":"OUT","labor_id":"3","confidence":"0.98"}
[2026-02-23 14:25:30] 12345 Server time: 2026-02-23 14:25:30 (TZ: Asia/Jakarta)
[2026-02-23 14:25:30] 12345 DB Connection: OK
[2026-02-23 14:25:30] 12345 Action: submit_attendance

====================================================================================================
[2026-02-23 14:25:30] 12345 === SUBMIT_ATTENDANCE START ===
[2026-02-23 14:25:30] 12345 user_id: 96, status: OUT, labor_id: 3
[2026-02-23 14:25:30] 12345 Time validation result: {"valid":true,"message":"Waktu valid","current_time":"14:25:30"}
[2026-02-23 14:25:30] 12345 Daily limit validation result: {"valid":true,"message":"Valid"}
[2026-02-23 14:25:30] 12345 User found: User 96 (ID: 96)
[2026-02-23 14:25:30] 12345 Last status today: NULL
[2026-02-23 14:25:30] 12345 Outstanding IN check - yesterday_in: 1, yesterday_out: 0, has_outstanding: YES
[2026-02-23 14:25:30] 12345 ALLOWED: Outstanding IN, no log today, OUT first time (close overtime)
[2026-02-23 14:25:30] 12345 ALLOWED: Status transition valid - lastStatus: NULL, status: OUT
[2026-02-23 14:25:30] 12345 Keterangan: lembur
[2026-02-23 14:25:30] 12345 SUCCESS: Attendance recorded - attendance_id: 12345
[2026-02-23 14:25:30] 12345 === SUBMIT_ATTENDANCE END ===
```

✅ Jika log menunjukkan ini → **BERHASIL!**

---

## ❌ Troubleshooting - Jika Muncul Error

### Error 1: File Tidak Ada
```
Cannot find path 'C:\Users\Taufik\face-recognition\attendance_debug.log' 
because it does not exist.
```
→ File akan auto-created saat ada request. Coba submit attendance terlebih dahulu.

### Error 2: Time Validation Gagal
Log menunjukkan:
```
[...] Time validation result: {"valid":false,"message":"Absensi pulang dimulai dari jam 16:00 WIB..."}
```

**Kemungkinan penyebab:**
- Outstanding IN tidak terdeteksi (berarti data kemarin tidak ada)
- Timezone masih tidak sesuai

**Fix:**
```powershell
# Cek apakah data kemarin ada:
mysql -u root -p absensi_labor
> SELECT COUNT(*) FROM attendance_logs 
  WHERE user_id = 96 AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status = 'IN';
```

Hasil harus = 1 (berarti ada 1 IN kemarin)

### Error 3: Daily Limit Validation Gagal
Log menunjukkan:
```
[...] Daily limit validation result: {"valid":false,"message":"Anda sudah keluar 1 kali hari ini"}
```

→ User sudah OUT 1x hari ini. Jika belum OK lembur, berarti outstanding IN tidak terdeteksi. Cek dengan debug query.

### Error 4: Status Transition Gagal
Log menunjukkan:
```
[...] ERROR: Status transition - last status: OUT, trying to do: OUT
```

→ User sudah OUT, tidak bisa OUT lagi. Ada bug di logic - hubungi developer.

---

## 🎯 Checklist Sebelum Report

Sebelum report error, pastikan ini sudah di-check:

- [ ] Outstanding IN data sudah ada di database (jalankan debug query)
- [ ] Timezone sudah set ke WIB (cek log: "TZ: Asia/Jakarta")
- [ ] DB Connection OK (cek log: "DB Connection: OK")
- [ ] POST data terkirim dengan benar (cek log: POST data section)
- [ ] Clear log file lama, coba test ulang

---

## Share Info Ini Jika Ada Error

1. **Copy-paste keseluruhan isi file log:**
   ```powershell
   Get-Content "C:\Users\Taufik\face-recognition\attendance_debug.log"
   ```

2. **Share user ID, labor ID, dan waktu test:**
   - User ID: ...
   - Labor ID: ...
   - Waktu test: ...

3. **Test dengan query debug juga:**
   ```sql
   -- Run debug_overtime_detection.sql
   ```

Dengan info ini saya bisa trace exact error-nya! 🔧
