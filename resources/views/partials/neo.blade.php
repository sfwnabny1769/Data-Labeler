{{--
  NEOBRUTALISM THEME (Butir 9 - UI redesign)
  Disisipkan ke semua halaman lewat @include('partials.neo').

  Pendekatan: CSS override layer, bukan rewrite Blade. Semua halaman yang
  sudah pakai utility class Tailwind (glass-card, tombol bg-*-600, dll)
  otomatis jadi bergaya neobrutalist tanpa risiko merusak logika/loop.

  Karakteristik neobrutalism:
  - Border hitam tebal (3px), bukan border tipis transparan
  - Drop shadow keras & offset (tidak blur), tanpa gradient/glassmorphism
  - Warna flat & jenuh, kontras tinggi
  - Font bold, tracking rapat
  - Tombol "menonjol" saat hover/active (efek tertekan)
--}}
<style>
    :root {
        --nb-ink: #000000;
        --nb-paper: #f0ddba;
        --nb-yellow: #70900f;
        --nb-pink: #ff6b9d;
        --nb-blue: #4d96ff;
        --nb-green: #6bcB77;
        --nb-orange: #ff9f45;
        --nb-purple: #b983ff;
    }

    /* ===== Base ===== */
    body {
        background-color: var(--nb-paper) !important;
        background-image:
            radial-gradient(circle at 1px 1px, rgba(0,0,0,0.13) 1px, transparent 0) !important;
        background-size: 22px 22px !important;
        color: var(--nb-ink) !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    /* Hilangkan orb glowglass yang tidak cocok dengan estetika brutalist */
    body > div.pointer-events-none,
    body .blur-\[150px\] {
        display: none !important;
    }

    /* ===== Kartu & header: border tebal + shadow keras ===== */
    .glass-card,
    .glass-header {
        background: #ae9f9f !important;
        backdrop-filter: none !important;
        border: 3px solid var(--nb-ink) !important;
        border-radius: 14px !important;
        box-shadow: 6px 6px 0 var(--nb-ink) !important;
        color: var(--nb-ink) !important;
    }

    .glass-header {
        border-radius: 0 !important;
        border-width: 0 0 3px 0 !important;
        box-shadow: 0 4px 0 var(--nb-ink) !important;
    }

    /* ===== Tipografi: warna jadi hitam, judul heavy ===== */
    .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-400,
    .text-slate-500, .text-slate-600, .text-slate-700, .text-slate-800,
    .text-slate-900, .text-white {
        color: var(--nb-ink) !important;
    }

    h1, h2, h3, h4 {
        font-family: 'Outfit', sans-serif;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
        text-transform: uppercase;
    }

    /* Label kecil tetap monospace + uppercase biar konsisten */
    label.text-\[10px\], label.block {
        font-weight: 800 !important;
        letter-spacing: 0.06em;
    }

    code, .font-mono {
        background: var(--nb-yellow) !important;
        border: 2px solid var(--nb-ink);
        border-radius: 6px;
        padding: 1px 5px;
        font-weight: 700;
    }

    /* ===== Tombol: brutalist block dengan shadow keras ===== */
    button, .btn {
        border: 3px solid var(--nb-ink) !important;
        border-radius: 10px !important;
        font-weight: 800 !important;
        box-shadow: 4px 4px 0 var(--nb-ink) !important;
        transition: transform 0.06s ease, box-shadow 0.06s ease !important;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    button:hover, .btn:hover {
        transform: translate(-2px, -2px) !important;
        box-shadow: 6px 6px 0 var(--nb-ink) !important;
    }

    button:active, .btn:active {
        transform: translate(3px, 3px) !important;
        box-shadow: 1px 1px 0 var(--nb-ink) !important;
    }

    /* Link bergaya underline ala brutalist */
    a {
        font-weight: 700;
        text-decoration-thickness: 2px;
        text-underline-offset: 3px;
    }

    /* ===== Form: kotak-putih, border tebal ===== */
    input[type="text"], input[type="password"], input[type="number"],
    input[type="email"], select, textarea {
        background: #ffffff !important;
        border: 3px solid var(--nb-ink) !important;
        border-radius: 10px !important;
        color: var(--nb-ink) !important;
        font-weight: 700 !important;
        box-shadow: 3px 3px 0 rgba(0,0,0,0.25) !important;
    }

    input:focus, select:focus, textarea:focus {
        outline: none !important;
        box-shadow: 5px 5px 0 var(--nb-ink) !important;
    }

    input::placeholder, textarea::placeholder {
        color: #6b7280 !important;
        font-weight: 600;
    }

    /* ===== Badge / chip status: warna flat ===== */
    span.px-3, span.px-4 {
        border-radius: 8px !important;
        font-weight: 800 !important;
    }

    /* ===== Tabel ===== */
    table {
        border: 3px solid var(--nb-ink) !important;
        border-radius: 12px;
        overflow: hidden;
    }

    thead {
        background: var(--nb-ink) !important;
    }

    thead th {
        color: #ffffff !important;
        font-weight: 800 !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 3px solid var(--nb-ink) !important;
    }

    tbody tr {
        border-bottom: 2px solid #000 !important;
    }

    tbody tr:hover {
        background: #fffbe0 !important;
    }

    /* ===== Tombol pagination ===== */
    nav[role="navigation"] a,
    nav[role="navigation"] span {
        border: 3px solid var(--nb-ink) !important;
        border-radius: 8px !important;
        font-weight: 800 !important;
        box-shadow: 3px 3px 0 var(--nb-ink) !important;
    }

    /* ===== Scrollbar brutalist ===== */
    ::-webkit-scrollbar { width: 12px; }
    ::-webkit-scrollbar-track { background: #e5e7eb; }
    ::-webkit-scrollbar-thumb {
        background: var(--nb-ink);
        border-radius: 0;
    }

    /* ===== Alert / flash message ===== */
    .glass-card p.text-emerald-400,
    .glass-card p.text-rose-400,
    .glass-card p.text-red-400 {
        font-weight: 800 !important;
    }

    footer {
        font-weight: 700;
        color: var(--nb-ink) !important;
    }

    /* ==========================================================
       PERBAIKAN KONRAST
       Diperlukan karena Neobrutalism mengganti latar jadi terang,
       sementara beberapa elemen masih pakai gaya untuk latar gelap.
       ========================================================== */

    /* (1) Judul gradient (text-transparent + bg-clip-text).
       Di latar terang, gradient pastel lama jadi nyaris tak terlihat.
       Kita paksa warnanya jadi gelap & pekat. */
    .bg-clip-text.text-transparent,
    .text-transparent {
        background-image: none !important;
        background: none !important;
        -webkit-background-clip: initial !important;
        background-clip: initial !important;
        -webkit-text-fill-color: var(--nb-ink) !important;
        color: var(--nb-ink) !important;
    }

    /* (2) Tombol & link yang tadinya gelap (bg-slate-800/900, bg-gray-800).
       Kalau dibiarkan gelap, teks hitam di atasnya jadi tidak terbaca.
       Kita brighten otomatis + paksa teks jadi putih kontras. */
    button.bg-slate-800, button.bg-slate-900, button.bg-gray-800,
    button.bg-slate-700, button.bg-gray-900,
    .btn.bg-slate-800, .btn.bg-slate-900, .btn.bg-gray-800,
    a.bg-slate-800, a.bg-slate-900, a.bg-gray-800,
    button.bg-slate-800 *, button.bg-slate-900 *, button.bg-gray-800 *,
    .btn.bg-slate-800 *, .btn.bg-slate-900 * {
        background-color: #5b6472 !important;
        color: #ffffff !important;
    }

    /* (3) Input dark yang teksnya jadi hitam -> paksa teks gelap & bg putih.
       (select sudahhandled di blok Form, ini untuk select-option dark) */
    select option {
        background: #ffffff !important;
        color: var(--nb-ink) !important;
    }

    /* (4) Badge/span translucent berlatar gelap -> flatten ke terang. */
    .glass-card span.bg-slate-800\/60,
    .glass-card span.bg-slate-900\/80 {
        background: #e5e7eb !important;
    }

    /* ==========================================================
       (5) OVERLAY LATAR GELAP -> paksa teks jadi putih
       Elemen seperti "screen selesai dilabel" (bg-slate-950/95)
       tetap memakai latar gelap. Aturan Neobrutalism yang memaksa
       teks jadi HITAM membuat teks putih aslinya jadi hilang
       (hitam di atas hitam). Jadi untuk elemen berlatar gelap
       pekat, kita KEMBALIKAN teksnya jadi putih.
       ========================================================== */
    .bg-slate-950\/95,
    .bg-slate-950,
    .bg-slate-900\/95,
    .bg-slate-900\/90,
    .bg-black\/90,
    .bg-gray-950,
    .bg-gray-900 {
        /* teks default putih agar kontras dengan latar gelap */
        color: #ffffff !important;
    }

    /* Paksa elemen berlatar gelap (termasuk h3/p/span di dalamnya) tetap putih */
    .bg-slate-950\/95 h1, .bg-slate-950\/95 h2, .bg-slate-950\/95 h3, .bg-slate-950\/95 h4,
    .bg-slate-950 h1, .bg-slate-950 h2, .bg-slate-950 h3, .bg-slate-950 h4,
    .bg-slate-900\/95 h1, .bg-slate-900\/95 h2, .bg-slate-900\/95 h3, .bg-slate-900\/95 h4,
    .bg-slate-900\/90 h1, .bg-slate-900\/90 h2, .bg-slate-900\/90 h3, .bg-slate-900\/90 h4,
    .bg-black\/90 h1, .bg-black\/90 h2, .bg-black\/90 h3, .bg-black\/90 h4,
    .bg-gray-950 h1, .bg-gray-950 h2, .bg-gray-950 h3, .bg-gray-950 h4,
    .bg-gray-900 h1, .bg-gray-900 h2, .bg-gray-900 h3, .bg-gray-900 h4 {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    /* Paragraf & span di dalam overlay gelap juga harus putih/terang */
    .bg-slate-950\/95 p, .bg-slate-950\/95 span,
    .bg-slate-900\/95 p, .bg-slate-900\/95 span,
    .bg-black\/90 p, .bg-black\/90 span,
    .bg-gray-950 p, .bg-gray-950 span {
        color: #d1d5db !important;
    }

    /* Ikon centang hijau di overlay tetap hijau (bukan ikut putih) */
    .bg-slate-950\/95 .text-emerald-400 {
        color: #34d399 !important;
    }

    /* ==========================================================
       (6) FIT LAYOUT - tanpa scroll, proporsional di 100% zoom
       ==========================================================
       Masalah: halaman labeler memakai min-h-[520px] + min-h-[340px],
       sehingga total tinggi melebihi layar laptop 1366x768 dan memaksa
       user scroll (atau zoom out) agar tombol terlihat.

       Solusi: pakai viewport-relative height (dvh/svh) supaya layout
       mengikuti tinggi viewport yang tersedia. Tidak ada perubahan
       struktur HTML/Blade.
       */

    html, body {
        min-height: 100%;
    }

    /* Halaman labeler: 1 layar penuh, footer menempel bawah */
    body.app-labeler {
        height: 100vh;
        height: 100dvh;
        min-height: 0 !important;
        padding-bottom: 0 !important;
        overflow: hidden;
    }

    body.app-labeler main {
        flex: 1 1 auto;
        min-height: 0 !important;
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    body.app-labeler header {
        flex-shrink: 0;
    }

    /* Workspace card: mengikuti ruang yang tersisa */
    body.app-labeler .min-h-\[520px\] {
        min-height: 0 !important;
        flex: 1 1 auto;
        padding: 1rem !important;
    }

    /* Frame gambar: PRIORITASKAN UKURAN GAMBAR.
       Pakai aspect-ratio supaya gambar besar & proporsional, bukan gepeng. */
    body.app-labeler .min-h-\[340px\] {
        min-height: 0 !important;
        height: auto !important;
        flex: 1 1 auto;
        aspect-ratio: 16 / 9;
        max-height: 46vh !important;
        min-height: clamp(180px, 24vh, 340px) !important;
        margin-bottom: 0.75rem !important;
    }

    /* Gambar mengisi frame tanpa keluar dari batas */
    body.app-labeler .buffer-img {
        max-height: 100% !important;
        max-width: 100% !important;
        object-fit: contain !important;
    }

    /* Judul "Pilih Label Klasifikasi" lebih rapat */
    body.app-labeler #label-controls > p {
        margin-bottom: 0.5rem !important;
        font-size: 10px !important;
    }

    /* Grid tombol label lebih rapat */
    body.app-labeler #label-controls > div {
        gap: 0.5rem !important;
    }

    /* Tombol label: padding dikecilkan agar tidak mendorong halaman */
    body.app-labeler #label-controls button {
        padding: 0.5rem !important;
    }

    /* Tombol lewati */
    body.app-labeler #label-controls > button {
        margin-top: 0.5rem !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }

    /* Statistik bar lebih tipis */
    body.app-labeler .grid.grid-cols-3 {
        gap: 0.75rem !important;
    }

    body.app-labeler .grid.grid-cols-3 > div {
        padding: 0.5rem !important;
    }

    /* Panduan pelabelan dipadatkan */
    body.app-labeler section.lg\:col-span-4 .glass-card {
        padding: 0.75rem !important;
    }

    body.app-labeler section.lg\:col-span-4 h3 {
        margin-bottom: 0.5rem !important;
        font-size: 0.875rem !important;
    }

    /* Leaderboard: scroll internal, tinggi dibatasi ketat */
    body.app-labeler .min-h-\[300px\] {
        min-height: 0 !important;
        flex: 0 1 auto;
        max-height: 18vh !important;
        padding: 0.75rem !important;
    }

    body.app-labeler #leaderboard-list {
        max-height: 12vh !important;
    }

    body.app-labeler > footer {
        margin-top: 0 !important;
        padding: 0.3rem 1rem !important;
        flex-shrink: 0;
        font-size: 10px !important;
    }

    /* ================= Layar pendek / laptop kecil ================= */
    @media (max-height: 800px) {
        body.app-labeler .min-h-\[340px\] {
            aspect-ratio: auto;
            height: clamp(160px, 26vh, 340px) !important;
            min-height: 0 !important;
            max-height: 26vh !important;
        }

        body.app-labeler .min-h-\[300px\] {
            max-height: 14vh !important;
        }

        body.app-labeler #leaderboard-list {
            max-height: 9vh !important;
        }
    }

    @media (max-height: 650px) {
        body.app-labeler .min-h-\[340px\] {
            height: clamp(130px, 22vh, 340px) !important;
            max-height: 22vh !important;
        }

        body.app-labeler .min-h-\[300px\] {
            max-height: 12vh !important;
        }
    }

    /* ==========================================================
       (8) PERBAIKAN KONTRAS TAMBAHAN
       Banner "Zero-Latency Workspace" memakai gradient teks + bg
       transparan. Setelah restyle Neobrutalism, teksnya jadi nyaris
       tak terlihat. Kita beri warna solid yang kontras.
       ========================================================== */
    body.app-labeler header span.bg-clip-text,
    body.app-labeler header span.text-transparent {
        background-image: none !important;
        -webkit-text-fill-color: var(--nb-ink) !important;
        color: var(--nb-ink) !important;
        font-weight: 800 !important;
    }

    /* Badge "Zero-Latency Workspace" */
    body.app-labeler header span.uppercase.bg-indigo-400\/10 {
        background: var(--nb-yellow) !important;
        color: var(--nb-ink) !important;
        border: 2px solid var(--nb-ink) !important;
    }

    /* ================= MOBILE: tinggi dinamis & aman iOS ================= */
    @media (max-width: 1023px) {
        body.app-labeler {
            overflow-y: auto;
            overflow-x: hidden;
            height: auto;
            min-height: 100vh;
            min-height: 100dvh;
        }

        body.app-labeler main {
            overflow: visible;
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }

        body.app-labeler .min-h-\[520px\] {
            min-height: 0 !important;
        }

        body.app-labeler .min-h-\[340px\] {
            height: auto !important;
            min-height: 55vw !important;
        }

        body.app-labeler .min-h-\[300px\] {
            max-height: none !important;
            min-height: 0 !important;
        }

        body.app-labeler #leaderboard-list {
            max-height: 50vh !important;
        }

        body.app-labeler > footer {
            padding: 0.5rem 1rem 1rem !important;
        }
    }

    /* ==========================================================
       (7) HALAMAN MASUK (nickname)
       Supaya link "Masuk sebagai Admin" & footer selalu terlihat
       tanpa scroll dan tanpa zoom out.
       ========================================================== */
    body.app-gate {
        min-height: 100vh;
        min-height: 100dvh;
        overflow: hidden;
        padding: 0.75rem !important;
        align-items: center;
    }

    body.app-gate > div.w-full {
        max-height: 100vh;
        max-height: 100dvh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Logo lebih kecil */
    body.app-gate .text-center.mb-8 .inline-flex {
        width: 2.75rem !important;
        height: 2.75rem !important;
        margin-bottom: 0.5rem !important;
    }

    body.app-gate .text-center.mb-8 svg {
        width: 1.25rem !important;
        height: 1.25rem !important;
    }

    /* Judul menyusut supaya form tetap muat di layar pendek */
    body.app-gate .text-center.mb-8 {
        margin-bottom: 0.75rem !important;
    }

    body.app-gate h1 {
        font-size: clamp(1.35rem, 4.5vw, 2rem) !important;
        line-height: 1.1 !important;
    }

    /* Kartu form: padding_INTERNAL tetap, tapi jarak antar bagian dirapatkan */
    body.app-gate .glass-card {
        padding: 1.1rem !important;
        flex-shrink: 0;
    }

    /* Rapatkan jarak form & field */
    body.app-gate form.space-y-6 {
        row-gap: 0.6rem !important;
    }

    body.app-gate .space-y-4 {
        row-gap: 0.6rem !important;
    }

    /* Input lebih pendek (tinggi kecil tapi tetap mudah diketik) */
    body.app-gate input {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }

    body.app-gate label {
        margin-bottom: 0.25rem !important;
        font-size: 10px !important;
    }

    /* Tombol submit sedikit lebih pendek */
    body.app-gate button[type="submit"] {
        padding-top: 0.6rem !important;
        padding-bottom: 0.6rem !important;
    }

    /* Blok link Admin + footer */
    body.app-gate .mt-6 {
        margin-top: 0.6rem !important;
    }

    body.app-gate .text-center.mt-6 .space-y-4 {
        row-gap: 0.5rem !important;
    }

    /* Layar pendek: sembunyikan deskripsi panjang (info tidak kritis) */
    @media (max-height: 700px) {
        body.app-gate .text-center.mb-8 p {
            display: none;
        }

        body.app-gate .text-center.mb-8 {
            margin-bottom: 0.5rem !important;
        }

        body.app-gate .glass-card {
            padding: 1rem !important;
        }
    }

    /* Mobile: boleh scroll kalau layar sangat pendek */
    @media (max-width: 1023px) {
        body.app-gate {
            overflow-y: auto;
            height: auto;
            min-height: 100vh;
            min-height: 100dvh;
        }
    }
</style>