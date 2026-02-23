# Panduan Debugging - Toast "Absensi dimulai dari jam 16:00" pada Lembur Lintas Hari

## Masalah
User sudah insert IN kemarin (tanpa OUT), tapi saat coba OUT hari ini masih dapat pesan:
```
❌ "Absensi dimulai dari jam 16:00 WIB"
```

Padahal seharusnya sistem mendeteksi outstanding IN dan allow OUT anytime.

---

## Step-by-Step Debugging

### Step 1: Verifikasi Data di Database
Jalankan query di `debug_overtime_detection.sql`:

```bash
mysql -u username -p database_name < debug_overtime_detection.sql
```

**Perhatikan hasil Query #4 - Status Outstanding IN:**
- ✅ Jika: `YES - ADA OUTSTANDING IN (Allow OUT anytime)` → Data benar, lanjut ke Step 2
- ❌ Jika: `NO - Tidak ada outstanding IN` → Data belum sesuai, lihat bagian "Perbaikan Data"

---

### Step 2: Verifikasi PHP Error Log
Sistem sudah di-update dengan debug logging. Cek file error log:

**Lokasi error log (biasanya):**
- Linux/Mac: `/var/log/php-errors.log` atau `php_errors.log`
- Windows: Cek di `php.ini` untuk `error_log` path, biasanya di `C:\xampp\php\logs\php_error.log`

**Atau cek dari terminal:**
```bash
tail -f /var/log/php-errors.log  # Linux
# atau
Get-Content C:\xampp\php\logs\php_error.log -Tail 50  # Windows PowerShell
```

**Cari log dengan prefix "DEBUG validateAttendanceTime":**
```
DEBUG validateAttendanceTime - user_id: 96, yesterday: 2026-02-22
DEBUG - yesterday_in_count: 1, yesterday_out_count: 0
DEBUG - Outstanding IN detected! Allow OUT anytime
```

Jika log ini muncul → Outstanding IN terdeteksi dengan benar ✅
Jika tidak ada log sama sekali → Parameter tidak di-pass ke fungsi ❌

---

### Step 3: Test Manual di UI

**Skenario Test:**
1. **Kemarin (Hari 1):**
   - Buka UI attendance
   - Pilih User ID yang sudah ada IN kemarin (tanpa OUT)
   - Verifikasi di database: sudah ada 1 IN record
   
2. **Hari Ini (Hari 2):**
   - Buka UI attendance
   - Pilih user yang sama
   - **Test OUT sebelum jam 16:00** (misal jam 05:00, 08:00, dll)
   - Jika berhasil → Fitur lembur lintas hari ✅ bekerja
   - Jika ditolak dengan pesan jam 16:00 → Ada issue, lanjut ke Step 4

---

### Step 4: Jika Masih Error

#### Kemungkinan 1: Database tidak match dengan kode
**Cek:**
```sql
-- Apakah tabel attendance_logs memiliki kolom: user_id, status, created_at, keterangan?
DESCRIBE attendance_logs;
```

#### Kemungkinan 2: Koneksi database null
**Cek di process_attendance.php:**
```php
// Pastikan koneksi sudah di-include
include 'koneksi.php';  // ← Harus ada di awal file

// Jika masih ngga bekerja, tambahkan debug:
// Sebelum validateAttendanceTime call, tambah:
if(!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
```

#### Kemungkinan 3: Timezone issue
**Cek timezone di database:**
```sql
SELECT @@global.time_zone, @@session.time_zone;
```

**Jika timezone tidak sesuai, set ke WIB (UTC+7):**
```php
// Tambah di awal process_attendance.php setelah koneksi:
date_default_timezone_set('Asia/Jakarta');  // WIB timezone
```

---

### Step 5: Perbaikan Data (jika belum benar)

Jika hasil Query #4 menunjukkan `NO - Tidak ada outstanding IN`:

**Opsi A: Hapus semua data user dan mulai ulang**
```sql
DELETE FROM attendance_logs WHERE user_id = 96;
DELETE FROM face_embeddings WHERE user_id = 96;
```

**Opsi B: Insert data kemarin dengan benar**
```sql
-- Pastikan ini adalah query terbaru (validate terlebih dahulu)
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

-- Verify:
SELECT * FROM attendance_logs 
WHERE user_id = 96 
AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
```

---

## Checklist Debugging

- [ ] **Query #4 hasil:** `YES - ADA OUTSTANDING IN` ✅
- [ ] **PHP Error Log:** Ada log "Outstanding IN detected" ✅
- [ ] **UI Test:** OUT hari ini sebelum jam 16:00 → SUCCESS ✅
- [ ] **Timezone:** Set ke 'Asia/Jakarta' (WIB) ✅
- [ ] **Database connection:** Verified tidak null ✅

---

## Troubleshooting Cepat

| Gejala | Penyebab | Solusi |
|--------|---------|--------|
| Toast "jam 16:00" muncul | Outstanding IN tidak terdeteksi | Run `debug_overtime_detection.sql` dulu |
| Tidak ada log debug | Parameter tidak di-pass | Verifikasi `$user_id` dan `$conn` di process_attendance.php |
| Query debug gagal | Koneksi DB error | Check `koneksi.php` dan credentials |
| Data kemarin tidak ada | Belum di-insert | Insert manual dengan query di Step 5 |
| Timezone salah | Server timezone bukan WIB | Update `date_default_timezone_set()` |

---

## Jika Masih Tidak Bekerja

Setelah follow semua step, jika masih tidak bekerja, mohon share:

1. **Output dari query debug** (`debug_overtime_detection.sql`)
2. **PHP Error log** (search "DEBUG validateAttendanceTime")
3. **Hasil dari test UI** (pesan error yang muncul)
4. **User ID yang di-test**

Maka saya bisa debug lebih dalam lagi.
