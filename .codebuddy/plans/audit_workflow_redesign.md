# Rancangan Final: Redesain Alur Kerja Data-Labeler

## Konteks & Esensi Aplikasi

Aplikasi ini untuk kompetisi data science. Esensinya: **melabeli manual test set yang belum berlabel untuk membentuk kunci jawaban tim (perkiraan jawaban leaderboard), plus preprocessing train set yang sudah diflag salah-label oleh pipeline Python**. Tidak ada konsep majority vote / consensus — labeling test set cukup 1 orang per gambar (sistem lease-lock yang sudah ada sudah cukup).

## 2 Fase

### Fase 0 — Preprocessing & Cleaning (Audit Train Set)

**Flow Fase 0 (sesuai saran user):**

1. Pipeline Python (`scripts/audit_data.py`) nge-flag gambar train set yang dicurigakan salah-label + skor prioritas.
2. CSV hasil pipeline di-upload ke web via `uploadCandidatesCsv`.
3. **SEMUA gambar train set masuk antrian review** (bukan cuma yang di-flag), tapi **diurutkan berdasarkan priority_score** — gambar dengan skor flag tertinggi muncul duluan, gambar tidak-flag tetap bisa direview kalau user penasaran.
4. Gambar ditampilkan satu-satu (dengan mekanisme **lease & lock**, anti-tabrakan antar anggota tim).
5. Tim dimintai keputusan 4 opsi (rubrik A/B/C/D yang SUDAH ADA di `AuditCandidate::ROUND1_RUBRIC`):
   - **A — Salah label**: gambar berisi target (paket/dus) tapi kategori salah → **muncul popup** pilih label tujuan (relabel_to)
   - **B — Kontaminasi**: gambar bukan target (chart, foto tidak relevan) → **ditandai** sebagai kontaminasi
   - **C — Ambigu**: gambar target tapi ada ambiguitas → **ditandai** sebagai ambigu
   - **D — Model error**: gambar target sudah dilabel benar, pipeline/cleanlab salah tebak → **ditandai** sebagai model error
4. Output: CSV dengan format 8 kolom yang SUDAH ADA di `downloadRound1Csv` (filepath, given_label, predicted_label, label_quality_score, reviewer, decision, note, reviewed_at) — **dipertahankan apa adanya**, sudah kompatibel dengan pipeline Python `remove_contamination.py`.

**Yang sudah ada di `AuditController` & tinggal dipakai:**
- ✅ Rubrik A/B/C/D (valid di `submitDecision` line 182-187)
- ✅ Lease & lock via `lockForUpdate()` + 409 conflict (line 194-198)
- ✅ Popup relabel saat A (line 212-216: `relabel_to` langsung simpan ke round2)
- ✅ Prioritas berdasarkan `priority_score` (line 139-142)

**Yang perlu diubah/ditambah:**

1. **Longgarkan filter `uploadCandidatesCsv`** (line 437-443): hapus filter `$isSuspicious` agar SEMUA gambar masuk antrian dengan urutan priority_score (bukan cuma yang conflict ≥ 20% / outlier ≥ 0.5).
2. **Dinamiskan validasi `relabel_to`** (line 185): ganti hardcode `0_Recyclable,1_Electronic,2_Organic` jadi dinamis dari `config('competition.classes')` — supaya kompetisi berikutnya (yang kelasnya beda) tetap jalan.
3. **Tandai B/C/D eksplisit di UI**: saat ini B/C/D cuma disimpan ke `round1_decision` tanpa feedback visual. Tambahkan badge/konfirmasi "Ditandai sebagai Kontaminasi/Ambigu/Model Error" di UI `audit.blade.php` setelah submit.
4. **Output CSV 8 kolom dipertahankan**: format `downloadRound1Csv` yang sudah ada (filepath, given_label, predicted_label, label_quality_score, reviewer, decision, note, reviewed_at) tetap dipertahankan apa adanya — sudah kompatibel dengan pipeline Python `remove_contamination.py`.

### Fase 1 — Pelabelan Manual Test Set (Buat Kunci Jawaban Tim)

- Anggota tim melabeli gambar test set yang belum berlabel → membentuk **kunci jawaban tim** untuk estimasi skor sebelum submit ke leaderboard.
- **Default: 1 labeler per gambar** (sistem lease-lock yang sudah dibangun sudah cocok).
- **Toggle mode multi-labeler**: Admin bisa menyalakan mode "multi-labeler per image" untuk ukur inter-annotator agreement. Kalau beda → dispute queue. Implementasi penuh consensus menyusul.

## Masalah UI yang Diperbaiki

### 1. Dropdown Aktivitas yang Membingungkan

Saat ini di `admin.blade.php:142-145`:
```
Fase 2: Active Labeling (Pelabelan Crowdsourced)   ← salah nama
Fase 1: Preprocessing & Cleaning                    ← salah urutan
```

Ganti jadi (urutan logis):
```
Fase 0 — Preprocessing & Cleaning (Audit Train Set)
Fase 1 — Pelabelan Manual Test Set (Kunci Jawaban Tim)
```

### 2. Panel Audit & Labeling Digabung — Single Page

Prinsip: **laman admin tetap sama, hanya kartu/panel konten yang berganti** saat `active_activity` diubah. Hapus navigasi "Audit Label" terpisah di header.

```
admin.blade.php (single page)
├── Header sticky (tanpa link "Audit Label")
├── Kontrol Workspace Card (selalu tampil)
│   ├── Mode switcher (dropdown 2 opsi: preprocess / labeling)
│   ├── Passkey display + input custom + tombol generate
│   │   • Input text untuk admin ketik passkey sendiri (custom)
│   │   • Tombol "Generate" untuk random (fallback)
│   │   • Tombol "Simpan Passkey" untuk simpan custom
│   └── Toggle multi-labeler mode (untuk Fase 1, default OFF)
│
├── [KONTEN DINAMIS — @include partial berdasarkan mode]
│   ├── Mode 'preprocess' → admin.partials.preprocess
│   │   • Upload label_issues.csv (uploadCandidatesCsv)
│   │   • Upload ZIP train images (uploadTrainZip)
│   │   • Stats: pending/done putaran 1
│   │   • Download label_review.csv (format 8 kolom yang sudah ada, dipertahankan)
│   │
│   └── Mode 'labeling' → admin.partials.labeling
│       • Upload ZIP test set (uploadDataset)
│       • Sync folder
│       • Stats labeling per kelas + leaderboard
│       • Antrean kerja tim & lease management (release-user/release-all)
│       • Download CSV Kaggle / Split 80:20 / Full Metadata
│       • (Jika multi-labeler ON) Panel dispute resolution
│
└── Footer
```

Implementasi Blade:
```blade
@include('admin.partials.' . $workspaceSetting->active_activity, [...$sharedData])
```

## Perubahan Kode yang Diperlukan

### A. `app/Models/WorkspaceSetting.php`

Tambah konstanta `ACTIVE_PREPROCESS`, sesuaikan enum:
```php
public const ACTIVE_PREPROCESS = 'preprocess';
public const ACTIVE_LABELING   = 'labeling';
public const ACTIVE_AUDIT      = 'audit';  // tetap untuk backward compat route
// tapi dropdown hanya tampilkan preprocess + labeling
```

Tambahkan field `multi_labeler_mode` (boolean) untuk toggle di Fase 1.

### B. Migration

Tambah kolom `multi_labeler_mode` boolean default 0 di `workspace_settings`.

### C. `app/Http/Controllers/AuditController.php`

1. **Longgarkan filter `uploadCandidatesCsv`** (line 437-443): hapus `$isSuspicious` filter, semua gambar masuk dengan priority_score.
2. **Dinamiskan `relabel_to` validation** (line 185): ambil dari `config('competition.classes')`.
3. **Pertahankan `downloadRound1Csv` 8 kolom**: tidak perlu ubah format, sudah kompatibel dengan pipeline Python.
4. Pastikan `getNextCandidate()` tetap urut berdasarkan `priority_score` (sudah benar di line 139-142).

### D. `resources/views/audit.blade.php`

Tambahkan feedback visual untuk B/C/D setelah submit (badge "Ditandai sebagai Kontaminasi/Ambigu/Model Error"). Untuk A, popup relabel sudah ada (tinggal pastikan dinamis dari `config('competition.classes')`).

### E. `resources/views/admin.blade.php`

1. Ganti opsi dropdown (line 142-145) jadi 2 opsi baru yang jelas.
2. Hapus `<a href="{{ route('admin.audit') }}">Audit Label</a>` di header (line 83-85).
3. Pecah konten dinamis jadi 2 partial via `@include`.
4. Tambah toggle "Multi-labeler per image" di kartu Kontrol Workspace (default OFF).
5. **Passkey custom**: ubah form `workspace-passkey` jadi punya 2 input:
   - `<input type="text" name="custom_passkey">` — admin input passkey sendiri
   - Tombol "Generate Random" (JS) untuk isi otomatis field custom_passkey dengan `Str::random(8)` setara
   - Tombol submit "Simpan Passkey" — kirim custom_passkey ke controller

### F. Partial Views Baru

Buat 2 file di `resources/views/admin/partials/`:
- `preprocess.blade.php` — konten dipindah dari `admin_audit.blade.php`
- `labeling.blade.php` — konten dipindah dari kartu labeling `admin.blade.php`

### G. `app/Http/Controllers/DatasetController.php`

1. `index()` & `submitNickname()` — tambah branch routing untuk `preprocess` mode (redirect ke `audit.index` view yang sudah ada, karena UI review-nya sama).
2. `adminView()` — pass data conditional berdasarkan mode ke view.
3. `updateWorkspaceSettings()` — validasi & simpan `multi_labeler_mode`.
4. **`regenerateWorkspacePasskey()`** — ubah agar terima input `custom_passkey`:
   - Jika `custom_passkey` diisi → simpan apa adanya (trim + uppercase optional)
   - Jika kosong → fallback generate random seperti sebelumnya
   - Validasi: min 4 / max 32 karakter, alfanumerik + dash/underscore
   - Pesan sukses beda untuk custom vs generated

### H. `admin_audit.blade.php` — Deprecated

Kontennya sudah masuk partial `preprocess.blade.php`. File dihapus atau dijadikan redirect ke `/admin`.

## Urutan Eksekusi Implementasi

1. **Update `WorkspaceSetting` model** — tambah `ACTIVE_PREPROCESS` + field `multi_labeler_mode`.
2. **Migration** — tambah kolom `multi_labeler_mode` di `workspace_settings`.
3. **Update `AuditController`**:
   - Longgarkan filter di `uploadCandidatesCsv` (semua gambar masuk, urut priority).
   - Dinamiskan validasi `relabel_to` dari `config('competition.classes')`.
   - Pertahankan `downloadRound1Csv` (format 8 kolom, tidak diubah).
4. **Update `audit.blade.php`** — feedback visual B/C/D + pastikan popup relabel A dinamis.
5. **Update `admin.blade.php`** — ganti opsi dropdown, hapus nav audit, pecah konten jadi 2 partial.
6. **Buat 2 partial** (`preprocess.blade.php` & `labeling.blade.php`).
7. **Update `DatasetController`** — routing `preprocess` mode + simpan `multi_labeler_mode` + ubah `regenerateWorkspacePasskey` agar terima `custom_passkey`.
8. **Hapus/deprecate `admin_audit.blade.php`**.
9. **(Opsional) Bangun consensus/dispute UI** kalau multi-labeler di-enable — menyusul.

## Catatan

- Tidak ada konsep "majority vote" / "estimasi F1 Macro" — test set leaderboard belum punya GT, jadi kunci jawaban tim = label manual langsung. Estimasi skor = pakai kunci jawaban ini untuk evaluasi model lokal (di luar web).
- Sistem lease-lock, batch allocation, outbox, keepalive yang sudah dibangun tetap dipakai apa adanya di Fase 1.
- Audit 2-putaran (A/B/C/D → relabel) tetap dipertahankan untuk Fase 0 — karena sudah ada dan cocok dengan flow yang user sebutkan.
- Perubahan DB minimal (cuma nambah 1 boolean kolom).
- Dinamisasi kelas via `config('competition.classes')` supaya reproducible untuk kompetisi berikutnya.
