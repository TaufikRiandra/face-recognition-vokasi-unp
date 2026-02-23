# Dokumentasi Fitur Lembur Lintas Hari (Overtime Next Day)

## Ringkasan Perubahan

Sistem attendance sekarang mendukung skenario lembur yang dimulai hari satu dan berakhir di hari dua (IN hari 1, OUT hari 2) dengan fitur berikut:

1. **OUT tanpa batas jam** - User bisa OUT kapan saja pada hari ke-2 jika ada lembur yang belum selesai dari hari sebelumnya
2. **Enforce status transition** - Jika user belum OUT lembur dari kemarin, mereka HARUS OUT dulu sebelum bisa IN lagi
3. **Deteksi otomatis lembur lintas hari** - Sistem secara otomatis mendeteksi kasus lembur (IN kemarin, tidak ada OUT)

---

## Alur Kerja

### Skenario A: Normal (IN Hari 1, OUT Hari 1)
```
Hari 1: IN 09:30 (tepat waktu) → OUT 18:30 (tepat waktu) ✓
Hari 2: Bisa melakukan IN/OUT normal ✓
```

### Skenario B: Lembur Lintas Hari (IN Hari 1, OUT Hari 2)
```
Hari 1: IN 23:00 (lembur) → TIDAK ADA OUT
    └─ Sistem detech ada outstanding IN

Hari 2:
  1. OUT 05:00 (DIIZINKAN - untuk tutup lembur) ✓
     └─ Walaupun sebelum jam 16:00, karena ada outstanding IN kemarin
  2. Setelah OUT lembur, baru bisa IN lagi
  3. IN 09:30 (tepat waktu) ✓
  4. OUT 18:30 (tepat waktu) ✓
```

### Skenario C: Jika User Coba IN Sebelum OUT Lembur (DITOLAK)
```
Hari 1: IN 23:00 (lembur) → TIDAK ADA OUT
    └─ Sistem detech ada outstanding IN

Hari 2:
  1. IN 09:30 (DITOLAK) ✗
     └─ Pesan: "Anda masih memiliki jam kerja lembur yang belum diselesaikan dari kemarin. 
        Silakan keluar (OUT) lembur terlebih dahulu sebelum masuk kembali."
  
  2. OUT 05:00 (DIIZINKAN) ✓ [OUT lembur pertama]
  3. Baru setelah ini, bisa IN lagi ✓
```

---

## File yang Diubah

### 1. **backend/helpers_attendance.php**

#### Fungsi: `validateAttendanceTime($status, $user_id = null, $conn = null)`
**Perubahan:**
- Menambah parameter opsional `$user_id` dan `$conn` untuk deteksi lembur lintas hari
- Untuk status `OUT`: jika ada outstanding IN dari kemarin, izinkan OUT kapan saja (tidak perlu jam 16:00)
- Jika tidak ada lembur lintas hari, tetap enforce jam 16:00

**Logika:**
```php
if($status === 'OUT') {
    // Check outstanding IN dari kemarin
    if(ada IN kemarin && tidak ada OUT kemarin) {
        // Allow OUT anytime
        $allow_anytime_out = true;
    }
    
    if(!$allow_anytime_out && jam_sekarang < 16:00) {
        // Reject kalau bukan lembur dan jam terlalu awal
        return error;
    }
}
```

#### Fungsi: `validateDailyLimit($user_id, $status, $conn)`
**Perubahan:**
- Menambah deteksi outstanding IN dari kemarin di awal fungsi
- Untuk status `IN`: jika ada outstanding IN kemarin, reject dengan pesan harus OUT lembur dulu
- Untuk status `OUT`: 
  - Jika ada outstanding IN kemarin, allow OUT 2x (1x tutup lembur + 1x normal pulang)
  - Jika tidak ada outstanding IN, enforce max 1x OUT per hari

**Logika:**
```php
$has_outstanding_in = (IN kemarin > 0 && OUT kemarin === 0);

if($status === 'IN') {
    if($has_outstanding_in) {
        // Reject - harus OUT lembur dulu
        return error;
    }
}

if($status === 'OUT') {
    if($out_hari_ini >= 2 && !$has_outstanding_in) {
        // Reject jika sudah OUT 2x dan bukan lembur
        return error;
    }
}
```

---

### 2. **backend/process_attendance.php**

#### Section: `save_embedding` action
**Perubahan:**
- Update call ke `validateAttendanceTime()` untuk menambah parameter `$user_id` dan `$conn`
- Komentari update untuk menjelaskan perubahan

#### Section: `submit_attendance` action
**Perubahan:**
1. Update call ke `validateAttendanceTime()` untuk pass `$user_id` dan `$conn`
2. Tambah logika deteksi outstanding IN dari kemarin sebelum validasi status transition
3. Update validasi status transition untuk support lembur lintas hari:
   ```php
   if($has_outstanding_in && $lastStatus === null && $status === 'OUT') {
       // Allow: Ini OUT pertama untuk tutup lembur kemarin
   }
   ```

---

## Validasi Data

### Kondisi Outstanding IN (Lembur Lintas Hari)
```sql
SELECT COUNT(*) FROM attendance_logs
WHERE user_id = ? 
  AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
  AND status = 'IN'
  AND (SELECT COUNT(*) FROM attendance_logs al2
       WHERE al2.user_id = user_id 
         AND DATE(al2.created_at) = DATE(created_at)
         AND al2.status = 'OUT') = 0
```

Jika hasil > 0: Ada outstanding IN dari kemarin (lembur lintas hari)

---

## Pesan Error yang Ditampilkan

| Kondisi | Pesan Error |
|---------|------------|
| Coba OUT sebelum jam 16:00 (bukan lembur lintas hari) | "Absensi pulang dimulai dari jam 16:00 WIB. Waktu server: HH:MM:SS" |
| Coba IN sebelum sudah OUT lembur kemarin | "Anda masih memiliki jam kerja lembur yang belum diselesaikan dari kemarin. Silakan keluar (OUT) lembur terlebih dahulu sebelum masuk kembali." |
| Coba IN 2x sehari (normal) | "Anda sudah masuk 1 kali hari ini. Silakan keluar terlebih dahulu baru bisa masuk lagi." |
| Coba OUT sebelum IN hari ini (dan tidak ada lembur kemarin) | "Anda belum masuk hari ini. Silakan masuk terlebih dahulu sebelum keluar." |
| OUT 2x sehari (tapi bukan lembur lintas hari) | "Anda sudah keluar 1 kali hari ini." |

---

## Testing dengan Query

File test query sudah dibuat: `test_overtime_user95.sql`

Skenario test:
1. **Hari 1 (kemarin):** User 95 IN jam 23:00 (lembur)
2. **Hari 2 (hari ini):** 
   - OUT jam 05:00 (DIIZINKAN ✓)
   - IN jam 09:30 (DIIZINKAN ✓)
   - OUT jam 18:30 (DIIZINKAN ✓)

Expected database state:
```
Hari kemarin: [IN 23:00 (lembur)]
Hari ini: [OUT 05:00 (lembur), IN 09:30 (tepat waktu), OUT 18:30 (tepat waktu)]
```

---

## Cara Menjalankan Test

1. **Setup test data dengan menjalankan query:**
   ```bash
   mysql -u username -p database_name < test_overtime_user95.sql
   ```

2. **Verifikasi di aplikasi (UI):**
   - User 95 akan bisa OUT pada jam 05:00 (meskipun sebelum jam 16:00)
   - UI akan mencatat OUT tersebut sebagai "lembur"
   - Setelah OUT, user bisa IN normal

3. **Verifikasi di history:**
   - Keterangan: "lembur" untuk IN 23:00 dan OUT 05:00
   - Keterangan: "tepat waktu" untuk IN 09:30 dan OUT 18:30

---

## Backward Compatibility

Semua perubahan **backward compatible**:
- User tanpa lembur lintas hari tidak terdampak
- Aturan normal IN/OUT tetap berlaku
- Hanya tambahan fitur untuk kasus lembur lintas hari

---

## Ringkasan Validasi

### validateAttendanceTime(status, user_id, conn)
| Status | Kondisi | Hasil |
|--------|---------|-------|
| IN | Jam >= 06:00 | ✓ Valid |
| IN | Jam < 06:00 | ✗ Error |
| OUT | Jam >= 16:00 | ✓ Valid |
| OUT | Jam < 16:00 + outstanding IN kemarin | ✓ Valid |
| OUT | Jam < 16:00 + no outstanding IN | ✗ Error |

### validateDailyLimit(user_id, status, conn)
| Status | Kondisi | Hasil |
|--------|---------|-------|
| IN | Outstanding IN kemarin | ✗ Error (OUT dulu) |
| IN | Already IN today | ✗ Error |
| IN | Normal | ✓ Valid |
| OUT | Outstanding IN kemarin + OUT 1x today | ✓ Valid (OUT 2x) |
| OUT | Outstanding IN kemarin + OUT 2x today | ✗ Error |
| OUT | No outstanding IN + OUT 1x today | ✗ Error |
| OUT | No outstanding IN + OUT 0x today + IN 0x today | ✗ Error |
| OUT | Normal (IN > OUT) | ✓ Valid |

---

## Catatan Penting

1. **Waktu Server:** Sistem menggunakan server time (WIB) yang tidak bisa dimanipulasi dari client
2. **Multiple Embeddings:** Fungsi `save_embedding` juga mendapat update untuk support lembur (lebih detail lihat di code)
3. **Keterangan:** Field `keterangan` di database akan otomatis terisi berdasarkan waktu:
   - "lembur" untuk OUT setelah jam 18:30 (atau OUT untuk tutup lembur kemarin)
   - "terlambat" untuk IN setelah jam 09:31
   - "tepat waktu" untuk yang lain
