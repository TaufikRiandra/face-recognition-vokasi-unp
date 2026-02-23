# 🚀 Setup Model Face Recognition - Panduan

## Deskripsi
Sistem ini menggunakan **persistent server-side caching** untuk menyimpan model AI secara permanen di folder server. Model tidak akan hilang meskipun user clear browser cache atau restart server.

---

## 📋 Proses Setup

### **Langkah 1: Akses Halaman Setup**
Setelah login, buka URL berikut di browser:
```
http://yourserver/setup-models.php
```

### **Langkah 2: Mulai Download**
- Klik tombol **"🚀 Mulai Download Model"**
- Tunggu hingga proses selesai (2-5 menit tergantung kecepatan internet)
- Browser akan menampilkan log real-time untuk setiap model yang diunduh

### **Langkah 3: Verifikasi**
Setelah selesai, Anda akan melihat:
- ✅ Pesan "Semua model berhasil diunduh dan disimpan!"
- 📊 Statistik jumlah model yang berhasil

---

## 📁 Struktur File

Model akan disimpan di folder:
```
face-recognition/
├── models/
│   ├── ssdMobilenetv1_model-shard1          (15 MB)
│   ├── ssdMobilenetv1_model-weights_manifest.json
│   ├── faceLandmark68_model-shard1          (25 MB)
│   ├── faceLandmark68_model-weights_manifest.json
│   ├── face_recognition_model-shard1        (32 MB)
│   ├── face_recognition_model-shard2        (18 MB)
│   └── face_recognition_model-weights_manifest.json
├── setup-models.php                          (UI untuk setup)
└── models/download-models.php                (Backend download)
```

**Total Size:** ~90 MB

---

## ⚙️ File Sistem

### **1. setup-models.php**
- **Lokasi:** Root folder
- **Fungsi:** UI untuk trigger download model
- **Akses:** Hanya admin yang sudah login
- **Output:** Progress log dan statistik real-time

### **2. models/download-models.php**
- **Lokasi:** models/ folder
- **Fungsi:** Backend untuk mengunduh dan menyimpan model
- **Proses:**
  - Check apakah file sudah ada
  - Jika belum, download dari CDN
  - Simpan ke folder lokal
  - Return statistik hasil

### **3. asset/check-models.php**
- **Lokasi:** asset/ folder
- **Fungsi:** Check ketersediaan model dan tampilkan banner warning
- **Output:** Banner kuning jika model belum tersedia
- **Auto-trigger:** Di-include di asset/header.php

### **4. pages/face-capture.php (Updated)**
- **Lokasi:** pages/ folder
- **Perubahan:**
  - Load model dari `../models/` (local first)
  - Fallback ke CDN jika local tidak tersedia
  - Pesan informasi lebih detail durant loading

### **5. asset/header.php (Updated)**
- **Include:** check-models.php untuk display banner

---

## 🔄 Cara Kerja Loading Model

```
User buka face-capture.php
         ↓
loadModels() berjalan
         ↓
Coba 1: Load dari ../models/ (LOCAL) ✅ Cepat!
         ↓ (FAILED)
Coba 2: Fallback ke CDN ⚠️ Lambat
         ↓
Model loaded → Kamera dimulai
```

---

## ⚡ Keuntungan Sistem Ini

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **Loading Pertama** | 30-60 detik (CDN) | 2-3 detik (Local) |
| **Loading Berikutnya** | 30-60 detik (CDN) | 2-3 detik (Local) |
| **Data Persisten** | Bisa hilang (cache) | Permanen (disk) |
| **Multi-user** | Setiap user download | 1 copy share |
| **Bandwidth** | Per user | 1x saja |
| **Akses Offline** | ❌ Need internet | ✅ Cepat |

---

## 🛠️ Troubleshooting

### **Error: "Gagal memuat model"**
**Solusi:**
```
1. Jalankan setup-models.php
2. Tunggu hingga semua model berhasil di-download
3. Refresh halaman face-capture.php
4. Cek console browser (F12) untuk error detail
```

### **Error: "Permission denied"**
**Solusi:**
```
1. Cek permission folder models/
2. Pastikan folder models/ bisa ditulis server
3. Jalankan command: chmod 755 models/
```

### **Error: "Failed to fetch model"**
**Solusi:**
```
1. Cek koneksi internet
2. CDN mungkin down, tunggu beberapa menit
3. Cek di browser console untuk URL detail
4. Test: curl https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/
```

### **Error: CDN Gagal (4+ model download fail)**
**Jika beberapa model gagal dari CDN, ada 2 solusi:**

#### **Solusi 1: Download Manual (Recommended)**
1. Buka: `http://yourserver/models/download-models-manual.php`
2. Klik "Download" untuk setiap file yang gagal
3. Simpan ke folder: `models/` di root server
4. Run `download-models.php` lagi untuk verifikasi

#### **Solusi 2: Coba CDN Alternatif**
```
CDN 1: https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models/
CDN 2: https://unpkg.com/face-api.js@0.22.2/models/
```
Script sudah include automatic fallback ke CDN 2 jika CDN 1 fail

#### **Solusi 3: Infrastruktur**
- Cek firewall/proxy yang block akses ke CDN
- Minta IT untuk whitelist CDN domain
- Setup proxy jika network restricted

---

## 📝 Maintenance

### **Jika ingin reset model:**
```bash
# Hapus semua file model
rm -rf models/ssdMobilenetv1*
rm -rf models/faceLandmark68*
rm -rf models/face_recognition*

# Jalankan ulang setup-models.php
```

### **Cek status model:**
```php
<?php
echo checkModelsAvailable()['available'] ? '✅ Ready' : '❌ Missing';
?>
```

---

## 📞 Support
Jika ada masalah, lihat:
- Browser console (F12) untuk error detail
- Server error log
- setup-models.php untuk download status

---

**Setup hanya perlu dijalankan SEKALI saat awal implementasi!** ✅
