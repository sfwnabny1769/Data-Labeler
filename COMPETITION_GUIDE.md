# Buku Panduan Kompetisi: Menggunakan Data-Labeler

Dokumen ini adalah panduan praktis bagi tim untuk menggunakan kembali (*reproduce*) aplikasi **Data-Labeler** pada kompetisi data science / machine learning berikutnya (misalnya Satria Data, Kaggle, lomba AI kampus, riset, dll.).

---

## 1. Persiapan Awal untuk Kompetisi Baru

### 1.1 Kustomisasi Kelas Label
Buka file `config/competition.php`:
```php
'name' => 'Klasifikasi Daun Padi - Satria Data 2026',
'description' => 'Bantu tim melabeli kondisi kesehatan tanaman padi.',

// Konfigurasi Kelas Dinamis (Bisa 2 kelas, 3 kelas, 5 kelas, 10 kelas)
'classes' => [
    [
        'id' => 0,
        'name' => 'Sehat',
        'badge' => 'Normal',
        'shortcut_label' => 'Q / 1',
        'keys' => ['0', '1', 'q', 'Q'],
        'color' => 'emerald',
        'desc' => 'Tanaman tanpa bercak atau penyakit.'
    ],
    [
        'id' => 1,
        'name' => 'Hawar Daun',
        'badge' => 'Bacterial Blight',
        'shortcut_label' => 'W / 2',
        'keys' => ['2', 'w', 'W'],
        'color' => 'blue',
        'desc' => 'Terdapat bercak kekuningan/keabuan memanjang.'
    ],
    [
        'id' => 2,
        'name' => 'Tungro',
        'badge' => 'Virus Tungro',
        'shortcut_label' => 'E / 3',
        'keys' => ['3', 'e', 'E'],
        'color' => 'amber',
        'desc' => 'Daun menguning jingga dan kerdil.'
    ],
]
```
> **Catatan:** Setelah mengubah `config/competition.php`, antarmuka pengguna (`/`), panduan, shortcut keyboard, dan dashboard admin (`/admin`) akan otomatis beradaptasi dengan kelas baru tersebut tanpa perlu menyentuh kode lain!

### 1.2 Memasukkan Dataset Gambar Baru
Ada dua cara mudah:
1. **Lewat Dashboard Admin (`/admin`)**:
   - Buka `/admin` -> Masukkan password (`admin123`).
   - Unggah file `dataset.zip` yang berisi foto-foto yang ingin dilabeli.
   - Sistem akan otomatis mengekstrak dan mendaftarkan gambar ke database.
2. **Secara Manual di Komputer Server/Host**:
   - Taruh foto-foto dataset ke folder `public/dataset/` (atau ubah `DATASET_PATH` di `.env`).
   - Di dashboard admin, klik tombol **"Sinkronisasi Folder"** (atau via terminal: `php artisan dataset:sync`).

---

## 2. Menjalankan Aplikasi

### Opsi A: Jalankan di Komputer Lokal (Windows)
1. Cukup klik ganda file `run-dev.bat`.
2. Buka browser di `http://127.0.0.1:8000`.

### Opsi B: Membagikan ke Tim Online via Ngrok
1. Pastikan ngrok sudah terinstall di komputer host.
2. Klik ganda `run-ngrok.bat`.
3. Terminal akan menampilkan URL publik Ngrok (misal: `https://abcd-1234.ngrok-free.app`).
4. Bagikan link tersebut dan **Passkey Aktif** dari halaman `/admin` kepada seluruh anggota tim.

### Opsi C: Deploy ke VPS Cloud (Docker)
1. Clone repository ke VPS:
   ```bash
   git clone <repo-url>
   cd Data-Labeler
   ```
2. Jalankan docker:
   ```bash
   docker compose up -d --build
   ```
3. Web otomatis aktif di port `8000`.

---

## 3. Fitur Unggulan untuk Tim Kompetisi

### ⚡ 1. Zero-Latency Buffer (0 Detik Jeda)
- Browser tiap anggota tim selalu menyimpan *buffer* 6–10 gambar yang sudah di-decode di memori.
- Saat menekan hotkey (Q/W/E atau 1/2/3), gambar berganti seketika (0 milidetik).
- Hasil label dikirim secara asinkron di latar belakang (*outbox queue*) sehingga pengguna tidak terganggu oleh lag jaringan.

### 🛡️ 2. Anti-Tabrakan Antar Anggota Tim (Lease Locking)
- Setiap kali browser mengambil batch gambar, gambar tersebut otomatis **direservasi selama 3 menit** untuk user tersebut.
- Anggota tim lain tidak akan pernah menerima gambar yang sedang dikerjakan orang lain.
- Jika ada anggota tim yang menutup tab browser, reservasi otomatis lepas lewat *beacon auto-release*.

### 👤 3. Tombol Admin "Lepas Lease" (AFK Management)
- Jika di akhir dataset ada gambar yang tertahan karena ada anggota tim yang AFK / tidak aktif:
  - Buka `/admin`
  - Lihat panel **"Antrean Kerja Tim & Sewa Gambar"**
  - Klik **"Lepas Lease"** pada nama anak yang AFK, atau klik **"Reset Semua Sewa"** untuk membebaskan seluruh gambar ke antrean tim.

---

## 4. Mengekspor Hasil Labeling untuk Model AI

Setelah proses pelabelan selesai dan disetujui di `/admin`, admin dapat mengunduh data dalam 3 format siap pakai:
1. **Unduh CSV Kaggle**: Format standar `id,label` untuk langsung di-submit ke platform kompetisi atau pipeline benchmarking.
2. **Split 80:20 (ZIP)**: Menghasilkan `train.csv` dan `val.csv` dengan pembagian *stratified* (distribusi kelas seimbang) untuk pelatihan PyTorch / TensorFlow.
3. **Full Metadata CSV**: Berisi detail lengkap nama file, ID kelas, nama kelas, siapa yang melabeli, program studi, dan timestamp.
