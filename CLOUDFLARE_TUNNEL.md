# Cloudflare Tunnel — Panduan Sharing Data-Labeler

Dokumen ini adalah panduan operasional bagi tim untuk membuka Data-Labeler ke
internet lewat Cloudflare Tunnel, menggantikan tunneling ngrok yang sudah mencapai
limit request.

---

## Kenapa Cloudflare Tunnel?

| | Ngrok (lama) | Cloudflare Tunnel |
|---|---|---|
| Biaya | Free tier sudah limit | Gratis, unlimited |
| URL | Berubah tiap restart | **Tetap selamanya** |
| HTTPS | Ya | Ya (otomatis, `.dev` ada HSTS) |
| Delay | Ada | Minimal |

---

## Cara Pakai (Untuk Uso Sehari-hari)

Cukup **klik 1 file**:

```
run-tunnel.bat
```

Selesai. File tersebut otomatis:
1. Cek PHP, cloudflared, dan file config tunnel
2. Matikan tunnel lama supaya tidak konflik
3. Jalankan server Laravel di port 8000
4. Jalankan Cloudflare Tunnel

Lalu bagikan URL ini ke seluruh anggota tim:

```
https://data-labeler.sfwn.dev
```

### Yang perlu diingat
- **PC harus menyala terus** selama tim sedang melabeli
- Jangan tutup jendela tunnel (bisa di-minimize)
- Untuk menghentikan: tekan **Ctrl+C**

### Kalau mau pakai lokal saja
Gunakan `run-dev.bat` (tanpa tunnel, hanya `http://127.0.0.1:8000`).

---

## Setup Awal (Sudah Dilakukan)

Kalau nanti setup ulang di PC lain, ikuti langkah ini.

### 1. Install cloudflared

```powershell
winget install --id Cloudflare.cloudflared
```

Restart PowerShell, lalu verifikasi:
```powershell
cloudflared --version
```

### 2. Login ke Cloudflare

```powershell
cloudflared tunnel login
```

Browser terbuka — pilih domain yang sudah ada di dashboard Cloudflare
(`sfwn.dev`).

### 3. Buat tunnel

```powershell
cloudflared tunnel create data-labeler
```

Catat UUID yang muncul, misal:
```
Tunnel credentials written to C:\Users\NAMAKAMU\.cloudflared\<UUID>.json
```

### 4. Buat file config

Buka `C:\Users\NAMAKAMU\.cloudflared\config.yml` dengan text editor, isi:

```yaml
tunnel: data-labeler
credentials-file: C:\Users\NAMAKAMU\.cloudflared/<UUID-KAMU>.json

ingress:
  - hostname: data-labeler.sfwn.dev
    service: http://localhost:8000
  - service: http_status:404
```

> **Penting:** hostname di config harus **sama persis** dengan URL yang dibuka.
> Kalau beda, hasilnya error 525.

### 5. Daftarkan DNS record

Buka dashboard Cloudflare → `sfwn.dev` → **DNS** → **Records** → **Add record**:

| Field | Isi |
|---|---|
| Type | `CNAME` |
| Name | `data-labeler` |
| Target | `<UUID>.cfargotunnel.com` |
| Proxy | 🟠 Proxied (default) |
| TTL | Auto |

> Lihat bagian "Pelajaran Penting" di bawah untuk kenapa record ini wajib ada.

### 6. Jalankan

```powershell
run-tunnel.bat
```

Atau manual dua terminal:

```powershell
# Terminal 1
php artisan serve --port=8000

# Terminal 2
cloudflared tunnel run data-labeler
```

---

## Troubleshooting

### Error 525 — SSL handshake failed

**Penyebab paling umum:** DNS record belum mengarah ke tunnel, atau hostname
di config tidak sama dengan URL yang dibuka.

**Periksa:**
1. `config.yml` hostname = `data-labeler.sfwn.dev`
2. URL yang dibuka = `https://data-labeler.sfwn.dev`
3. DNS record CNAME ada dan Proxied

Test DNS di PowerShell:
```powershell
nslookup -type=CNAME data-labeler.sfwn.dev 1.1.1.1
```

Kalau tidak ada `Aliases` → record CNAME belum dibuat.

> **Pelajaran penting:** domain `sfwn.dev` memakai **wildcard DNS** (`*.sfwn.dev`).
> Wildcard hanya berarti "semua subdomain resolve ke Cloudflare" — TIDAK otomatis
> mengarah ke tunnel. Karena itu tetap perlu **CNAME record eksplisit** yang
> menunjuk `<UUID>.cfargotunnel.com`. Record eksplisit akan menimpa wildcard.

### Error 1033 — Tunnel offline

Tunnel-nya belum jalan. Buka `run-tunnel.bat`, atau cek:
```powershell
cloudflared tunnel list
```

### Halaman loading lama / stuck

- Pastikan server Laravel hidup: buka `http://127.0.0.1:8000` langsung
- Kalau localhost juga lambat, masalahnya bukan tunnel

### `cloudflared` tidak ditemukan

```powershell
winget install --id Cloudflare.cloudflared
```
Lalu **restart** PowerShell.

### Tunnel conflict (d sudah jalan)

`run-tunnel.bat` sudah otomatis `taskkill` tunnel lama. Kalau manual, jalankan:
```powershell
taskkill /IM cloudflared.exe /F
```

### SSL error / "unsupported protocol"

Pastikan buka **hostname lengkap**, bukan menyalin sebagian:
```
https://data-labeler.sfwn.dev      ✅
label.abins.dev.sfwn.dev           ❌ (salah ketik)
```

---

## Cara Kerja (Supaya Dipahami)

```
Tim buka https://data-labeler.sfwn.dev
            ↓
Cloudflare edge (sertifikat HTTPS otomatis)
            ↓  koneksi terenkripsi, tanpa port terbuka di router
cloudflared di PC kamu
            ↓  tunnel terenkripsi
Laravel server (localhost:8000)
```

Keamanan: **tidak ada port yang terbuka di internet**. Yang terlihat hanya
server kamu yang handshake lewat Cloudflare.

---

## Konsep: Subdomain = "Cabang" dari Domain Utama

Satu domain bisa dipakai banyak proyek. Tiap proyek dapat subdomain sendiri,
saling tidak mengganggu:

```
sfwn.dev
├── projekA.sfwn.dev        → Cloudflare Pages
├── data-labeler.sfwn.dev   → Cloudflare Tunnel (Data-Labeler)
└── projectC.sfwn.dev       → apa pun
```

Analogi: seperti branch di git — satu repo induk, banyak cabang, masing-masing
hidup sendiri.

---

## Catatan Tim

- **Passkey workspace** bisa diubah di `/admin` → Kontrol Workspace
- **Admin password** default `admin123` — ganti lewat `.env` (`ADMIN_PASSWORD`)
- Kalau butuh URL lain (misal untuk Utilities), tinggal buat subdomain + tunnel
  baru; proyek lain tidak terpengaruh sama sekali