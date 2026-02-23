# Debug Tracking - Lembur Lintas Hari Tidak Bisa OUT

## Langkah 1: Bersihkan Log File Lama
Sebelum test, bersihkan log file agar hanya ada log baru:

**Windows (PowerShell):**
```powershell
# Jika menggunakan XAMPP default:
$logfile = "C:\xampp\php\logs\php_error.log"
if (Test-Path $logfile) {
    Clear-Content $logfile
    Write-Host "Log cleared"
}
```

**Linux/Mac:**
```bash
sudo truncate -s 0 /var/log/php-errors.log
# atau
sudo > /var/log/php-errors.log
```

---

## Langkah 2: Submit Attendance Melalui UI
1. Buka UI attendance
2. Pilih user ID yang memiliki outstanding IN dari kemarin (misal user 96)
3. Pilih "OUT"
4. Submit
5. Catat error message yang muncul

---

## Langkah 3: Trace Log untuk Setiap Tahap

### Checkpoint 1: Parameter Validation
Cari log:
```
=== SUBMIT_ATTENDANCE START ===
user_id: 96, status: OUT, labor_id: 3
```

❌ **Jika tidak ada:** Parameter/user_id tidak diterima dengan baik
✅ **Jika ada:** Lanjut ke checkpoint 2

---

### Checkpoint 2: Time Validation
Cari log:
```
DEBUG validateAttendanceTime - user_id: 96, yesterday: 2026-02-22
DEBUG - yesterday_in_count: 1
DEBUG - yesterday_out_count: 0
DEBUG - Outstanding IN detected for user 96! Allow OUT anytime
```

**Hasil yang mungkin:**

**CASE A - Outstanding terdeteksi:**
```
Time validation result: {"valid":true,"message":"Waktu valid","current_time":"..."}
```
✅ Lolos, lanjut checkpoint 3

**CASE B - Timezone jadi hang atau parameter tidak di-pass:**
```
DEBUG validateAttendanceTime - Missing parameter. user_id: 0, conn: NULL
```
❌ **FIX:** Pastikan `date_default_timezone_set('Asia/Jakarta')` di awal koneksi

**CASE C - Outstanding tidak terdeteksi:**
```
DEBUG - yesterday_in_count: 0
DEBUG - yesterday_out_count: 0
Time validation result: {"valid":false,"message":"Absensi pulang dimulai dari jam 16:00 WIB..."}
```
❌ **Berarti data outstanding IN tidak ada di database**, cek ulang data kemarin

---

### Checkpoint 3: Daily Limit Validation
Cari log:
```
=== validateDailyLimit START - user_id: 96, status: OUT ===
Outstanding IN: yesterday_in=1, yesterday_out=0, has_outstanding=1
OUT check - today_out_count: 0
OUT check - today_in_count: 0
validateDailyLimit PASSED - status valid
```

**Hasil yang mungkin:**

**✅ PASSED:**
```
Daily limit validation result: {"valid":true,"message":"Valid"}
```
Lanjut checkpoint 4

**❌ FAILED - OOT 1x sudah:**
```
OUT check - today_out_count: 1
ERROR: Already OUT once today and no outstanding IN
Daily limit validation result: {"valid":false,"message":"Anda sudah keluar 1 kali hari ini"}
```
→ User sudah OUT 1x hari ini (tanpa outstanding IN)

**❌ FAILED - OUT 2x sudah:**
```
OUT check - today_out_count: 2
ERROR: Already OUT 2x today and no outstanding IN
Daily limit validation result: {"valid":false,"message":"Anda sudah keluar 2 kali hari ini"}
```
→ User sudah OUT 2x hari ini

---

### Checkpoint 4: Status Transition Validation
Cari log:
```
Last status today: NULL
Outstanding IN check - yesterday_in: 1, yesterday_out: 0, has_outstanding: YES
ALLOWED: Outstanding IN, no log today, OUT first time (close overtime)
```

**Hasil yang mungkin:**

**✅ ALLOWED:**
```
ALLOWED: Status transition valid - lastStatus: NULL, status: OUT
```
Lanjut checkpoint 5 (INSERT)

**❌ ERROR - Harus IN dulu:**
```
Last status today: OUT
ERROR: Status transition - last status: OUT, trying to do: OUT
ERROR: Status transition - last status: OUT (no outstanding), trying to do: OUT
```
→ User sudah OUT, tidak bisa OUT lagi

**❌ ERROR - Ada IN sebelumnya:**
```
Last status today: IN
ERROR: Status transition - last status IN, trying to do: OUT (TAPI ERROR HARUS OUT)
```
→ Logic error, ini harusnya bukan conditional ini

---

### Checkpoint 5: Insert Database
Cari log:
```
Keterangan: lembur
SUCCESS: Attendance recorded - attendance_id: 12345
=== SUBMIT_ATTENDANCE END ===
```

**✅ SUCCESS:**
```
SUCCESS: Attendance recorded - attendance_id: 12345
```
→ Data berhasil disimpan! ✅

**❌ ERROR - Insert gagal:**
```
ERROR: Insert failed - Duplicate entry...
```
→ Database constraint error

---

## Quick Reference - Log Keseluruhan

Seharusnya kalau semuanya OK, log akan terlihat:

```
=== SUBMIT_ATTENDANCE START ===
user_id: 96, status: OUT, labor_id: 3

DEBUG validateAttendanceTime - user_id: 96, yesterday: 2026-02-22
DEBUG - yesterday_in_count: 1
DEBUG - yesterday_out_count: 0
DEBUG - Outstanding IN detected for user 96! Allow OUT anytime
Time validation result: {"valid":true,...}

=== validateDailyLimit START - user_id: 96, status: OUT ===
Outstanding IN: yesterday_in=1, yesterday_out=0, has_outstanding=1
OUT check - today_out_count: 0
OUT check - today_in_count: 0
validateDailyLimit PASSED - status valid

Daily limit validation result: {"valid":true,"message":"Valid"}

User found: User 96 (ID: 96)
Last status today: NULL
Outstanding IN check - yesterday_in: 1, yesterday_out: 0, has_outstanding: YES
ALLOWED: Outstanding IN, no log today, OUT first time (close overtime)
ALLOWED: Status transition valid - lastStatus: NULL, status: OUT

Keterangan: lembur
SUCCESS: Attendance recorded - attendance_id: 12345

=== SUBMIT_ATTENDANCE END ===
```

---

## Troubleshooting

| Jika di Checkpoint | Kemungkinan | Solusi |
|------------------|-----------|--------|
| 1 | user_id: 0 | Cek apakah user_id di-pass dari UI dengan benar |
| 2 | outstanding_in_count: 0 | Verify data kemarin dengan `debug_overtime_detection.sql` |
| 3 | today_out_count: 1 | Berarti user sudah OUT 1x, cek apakah outstanding IN terdeteksi |
| 4 | lastStatus: OUT | User sudah OUT, tidak bisa OUT lagi |
| 5 | Insert failed | Cek database constraint atau kolom missing |

---

## Langkah next jika masih error:

1. **Share log lengkapnya (dari checkpoint 1-5)** sampai ke error terakhir
2. **Share output dari `debug_overtime_detection.sql`**
3. **Share user ID yang di-test**
4. **Share jam berapa coba submit**

Dengan info ini saya bisa identify di mana exactnya error!
