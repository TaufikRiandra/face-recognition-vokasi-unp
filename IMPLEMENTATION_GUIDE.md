# Dokumentasi Update Sistem Absensi Face Recognition - Labor UNP

## 📋 Ringkasan Perubahan

Semua fitur yang Anda minta telah berhasil diimplementasikan. Berikut adalah dokumentasi lengkap mengenai perubahan yang telah dilakukan.

---

## 1. ✅ VERIFIKASI & PERBAIKAN JADWAL KERJA

### Masalah yang Ditemukan
**Database sebelumnya (Labor Tefa ID 3):**
- Jam Masuk: 09:30 (SALAH - seharusnya 09:00)
- Jam Pulang: 18:30 (SALAH - seharusnya 16:00)
- Toleransi: 1 menit (SALAH - seharusnya 15 menit)

### Solusi yang Diterapkan
**Jadwal sudah diperbarui ke:**
- Jam Masuk: **09:00** (batas akhir masuk tepat waktu)
- Jam Pulang: **16:00** (4 sore - batas standar pulang)
- Toleransi: **15 menit** (keterlambatan masih dianggap tepat waktu)

**File SQL untuk verifikasi:** `database/fix-schedule-tefa.sql`

### Cara Kerja Sistem Jadwal:
```
MASUK:
- Masuk sebelum 09:00 → Tepat waktu
- Masuk 09:00-09:15 → Masih tepat waktu (dalam toleransi)
- Masuk setelah 09:15 → TERLAMBAT

PULANG:
- Pulang sebelum 16:00 → Tepat waktu
- Pulang 16:00-16:15 → Tepat waktu (dalam toleransi)
- Pulang setelah 16:15 → LEMBUR
```

---

## 2. ✅ PENAMBAHAN NOMOR URUT DI DAFTAR USER

### Perubahan Tabel User
**File:** `pages/manage_users.php`

**Sebelum:**
| Nama | NIM | Tanggal | Waktu | Aksi |
|------|-----|---------|-------|------|

**Sesudah:**
| No | Nama | NIM | Tanggal | Waktu | Aksi |
|----|------|-----|---------|-------|------|

**Fitur:**
- Nomor urut otomatis berdasarkan halaman (1, 2, 3, ... per halaman)
- Nomor dihitung dari total record keseluruhan
- Kolom "No" memiliki lebar 5% dari tabel

---

## 3. ✅ EXPORT KE PDF

### File Baru
**File:** `backend/api_export_users_pdf.php`

### Cara Penggunaan
1. Buka halaman **Kelola User** (`pages/manage_users.php`)
2. Klik tombol **"Export PDF"** (merah)
3. File PDF akan diunduh dengan nama: `Daftar_User_DD-MM-YYYY_HisMi.pdf`

### Konten PDF
- Header: Tanggal, Judul "Daftar User"
- Tabel dengan kolom: No, Nama, NIM, Tanggal Dibuat
- Total data user aktif dari database

### Persyaratan
- TCPDF library (opsional - jika tidak ada, akan menampilkan pesan instalasi)
- Install dengan: `composer require tecnickcom/tcpdf`

---

## 4. ✅ EXPORT KE EXCEL

### File Baru
**File:** `backend/api_export_users_excel.php`

### Cara Penggunaan
1. Buka halaman **Kelola User** (`pages/manage_users.php`)
2. Klik tombol **"Export Excel"** (hijau)
3. File CSV akan diunduh dengan nama: `Daftar_User_DD-MM-YYYY_HisMi.csv`

### Konten Excel/CSV
- Header: No, Nama, NIM, Tanggal Dibuat
- Separator: Semicolon (;) untuk kompatibilitas dengan Excel Indonesia
- Encoding: UTF-8 dengan BOM
- Semua data user aktif dari database

### Keunggulan
- Tidak memerlukan library tambahan
- Kompatibel dengan Microsoft Excel, Google Sheets, LibreOffice Calc
- Format CSV universal
- File dapat langsung dibuka di Excel

---

## 5. ✅ FITUR ATUR JADWAL KERJA (SCHEDULE MANAGEMENT)

### Lokasi Fitur
**Dashboard** (`pages/dashboard.php`)

### Cara Mengakses
1. Buka Dashboard Sistem Absensi
2. Lihat menu tombol di bagian atas halaman
3. Klik tombol **"Atur Waktu"** (dengan ikon jam)
4. Modal form akan terbuka

### Form Atur Jadwal
**Dialog yang ditampilkan:**

```
┌─────────────────────────────────────────┐
│  🕐 Atur Jadwal Kerja                   │
├─────────────────────────────────────────┤
│                                         │
│  Jam Masuk (Batas Akhir)   [09:00]      │
│  Toleransi Keterlambatan   [15] menit   │
│  Jam Pulang (Batas Standar) [16:00]     │
│                                         │
│  ℹ️ Cara Kerja:                          │
│  • Masuk hingga 09:00+15min → Tepat    │
│  • Setelah itu → Terlambat              │
│  • Pulang s.d 16:00+15min → Tepat      │
│  • Setelah itu → Lembur                 │
│                                         │
│  [Batal]  [Simpan]                      │
└─────────────────────────────────────────┘
```

### File Backend
1. **`backend/api_get_schedule.php`** - Mengambil jadwal saat ini
2. **`backend/api_update_schedule.php`** - Menyimpan jadwal yang diperbarui

### Validasi & Keamanan
- Validasi format waktu HH:MM
- Validasi toleransi: 0-120 menit
- Logging perubahan jadwal di `logs/schedule_changes.log`
- Response JSON untuk integrasi
- Error handling lengkap

### Fitur
- Pre-fill formulir dengan jadwal saat ini
- Validasi input client-side
- Konfirmasi sebelum simpan
- Auto-refresh setelah berhasil
- Pesan kesuksesan/error yang jelas

---

## 📁 DAFTAR FILE YANG DIMODIFIKASI/DIBUAT

### File Dimodifikasi:
1. ✏️ `pages/manage_users.php` - Tambah nomor urut + tombol export
2. ✏️ `pages/dashboard.php` - Tambah tombol "Atur Waktu" + modal + JavaScript

### File Baru Dibuat:
1. ✨ `backend/api_export_users_pdf.php` - Export PDF user
2. ✨ `backend/api_export_users_excel.php` - Export Excel/CSV user
3. ✨ `backend/api_get_schedule.php` - Ambil jadwal kerja
4. ✨ `backend/api_update_schedule.php` - Update jadwal kerja
5. ✨ `database/fix-schedule-tefa.sql` - SQL script untuk fix jadwal
6. ✨ `fix-schedule.php` - PHP script untuk fix jadwal

---

## 🔧 INSTRUKSI INSTALASI & KONFIGURASI

### 1. Update Jadwal Kerja Langsung di Database
**Opsi A: Menggunakan phpMyAdmin**
1. Buka `database/fix-schedule-tefa.sql`
2. Copy query UPDATE
3. Jalankan di phpMyAdmin MySQL Query

**Opsi B: Command Line MySQL**
```bash
mysql -u root -p absensi_labor < database/fix-schedule-tefa.sql
```

**Opsi C: Script PHP**
```bash
php fix-schedule.php
```

### 2. Verifikasi Database
Jalankan query berikut untuk memverifikasi:
```sql
SELECT id, nama, jam_masuk_standar, jam_pulang_standar, toleransi_terlambat 
FROM labor WHERE id=3;
```

Hasil yang diharapkan:
```
id=3, nama=Labor Tefa, jam_masuk_standar=09:00:00, jam_pulang_standar=16:00:00, toleransi_terlambat=15
```

### 3. Opsional: Install TCPDF untuk PDF (Rekomendasi)
```bash
composer require tecnickcom/tcpdf
```

Jika tidak diinstall, fitur PDF masih bisa berjalan (akan redirect ke download browser).

---

## 📊 CONTOH PENGGUNAAN

### Export Daftar User ke Excel
```
1. Dashboard → Kelola User
2. (Opsional) Cari user dengan search box
3. Klik "Export Excel"
4. File "Daftar_User_15-12-2024_143022.csv" akan diunduh
5. Buka dengan Excel → Data siap dianalisis
```

### Atur Jadwal Kerja Baru
```
1. Dashboard → Klik "Atur Waktu"
2. Format baru:
   - Jam Masuk: 08:30 (ganti dari 09:00)
   - Toleransi: 10 menit
   - Jam Pulang: 17:00 (ganti dari 16:00)
3. Klik "Simpan"
4. Sistem akan update database dan refresh halaman
5. Jadwal baru berlaku permanen di database
```

---

## ⚠️ CATATAN PENTING

### Kalibrasi Jadwal
Jadwal yang telah diset:
- **Entry:** Masuk harus sebelum 09:00, toleransi hingga 09:15
- **Exit:** Pulang bisa kapan saja, standard work hour sampai 16:00, overtime setelah 16:15
- **Overtime:** Sistem mendeteksi lembur jika keluar setelah 16:15

### Jika Ada Perubahan Kebutuhan
Admin dapat langsung mengubah jadwal melalui:
1. Dashboard → Atur Waktu (UI)
2. Atau langsung query SQL di database

### Backup Data
Sebelum melakukan perubahan, sangat disarankan untuk membuat backup database:
```bash
mysqldump -u root -p absensi_labor > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

## ✨ RINGKASAN FITUR YANG SELESAI

| No | Fitur | Status | Lokasi |
|----|-------|--------|--------|
| 1 | Verifikasi jadwal entry (09:00-09:15) | ✅ | Database + helpers_attendance.php |
| 2 | Verifikasi jadwal exit (16:00, overclock 18:00) | ✅ | Database + helpers_attendance.php |
| 3 | Nomor urut di daftar user | ✅ | pages/manage_users.php |
| 4 | Export PDF user  | ✅ | backend/api_export_users_pdf.php |
| 5 | Export Excel user | ✅ | backend/api_export_users_excel.php |
| 6 | Atur jadwal dari dashboard | ✅ | pages/dashboard.php + backend APIs |

---

## 📞 SUPPORT & TROUBLESHOOTING

### Tombol Export tidak muncul
- Refresh halaman (Ctrl+F5 untuk hard refresh)
- Cek browser console (F12) untuk error messages

### AJAX gagal untuk Atur Waktu
- Pastikan file `api_get_schedule.php` dan `api_update_schedule.php` ada di folder `backend/`
- Cek permission folder (harus writable untuk log)
- Lihat Network tab (F12) untuk response error

### Export PDF/Excel tidak bekerja
- Cek TCPDF (untuk PDF): `composer require tecnickcom/tcpdf`
- Untuk Excel: Tidak perlu library, built-in PHP
- Cek file permission di `backend/` folder

---

## 🎉 SELESAI!

Semua fitur yang Anda minta telah berhasil diimplementasikan dan siap digunakan. 

Jadwal kerja sistem sudah disesuaikan dengan requirement Anda:
- ✅ Masuk: 09:00 dengan toleransi 15 menit (s.d 09:15)
- ✅ Pulang: 16:00 (kerja normal, lembur setelah 16:15)

Sistem siap untuk digunakan!

---

**Tanggal Update:** 15 Desember 2024
**Versi:** 1.0 - Release Fitur Lengkap
