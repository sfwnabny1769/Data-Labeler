<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Labeler - Admin Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <!-- Axios for API requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.04), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(220, 38, 38, 0.03), transparent 45%),
                        #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .glass-header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        /* Fade out row animation */
        .row-fade-out {
            transition: all 0.4s ease-out;
            opacity: 0;
            transform: translateX(-20px);
            height: 0;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            border: none !important;
        }
    </style>
@include('partials.neo')
</head>
<body class="min-h-screen text-slate-100 flex flex-col relative pb-12">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Glowing Background Orbs -->
    <div class="absolute top-10 right-10 w-[500px] h-[500px] bg-red-500/3 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="absolute bottom-10 left-10 w-[500px] h-[500px] bg-indigo-500/3 rounded-full blur-[150px] pointer-events-none"></div>

    <!-- Header -->
    <header class="glass-header sticky top-0 z-50 py-4 px-6 md:px-12 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <span class="font-outfit font-bold text-lg tracking-tight bg-gradient-to-r from-slate-200 to-red-200 bg-clip-text text-transparent">Data Labeler</span>
                <span class="text-[10px] uppercase font-bold tracking-widest text-red-400 bg-red-400/10 px-1.5 py-0.5 rounded ml-2">Admin Panel</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-300 hover:text-indigo-400 transition-colors duration-200">
                Workspace Label
            </a>

            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/40 text-red-400 rounded-xl text-xs font-semibold transition-all duration-200">
                    Keluar Admin
                </button>
            </form>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 md:px-8 py-8 space-y-8 z-10">

        <!-- Status Alerts -->
        @if(session('success'))
            <div class="p-4 bg-emerald-500/15 border border-emerald-500/30 rounded-2xl text-emerald-400 text-sm font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="p-4 bg-amber-500/15 border border-amber-500/30 rounded-2xl text-amber-400 text-sm font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-500/15 border border-red-500/30 rounded-2xl text-red-400 text-sm font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @php
            // Normalisasi mode aktif: 'audit' (legacy) diperlakukan sebagai 'preprocess'
            // agar partial yang benar tetap dimuat tanpa migrasi data.
            $activeMode = $workspaceSetting->active_activity ?? 'labeling';
            if ($activeMode === 'audit') {
                $activeMode = 'preprocess';
            }
            $modeLabel = match($activeMode) {
                'preprocess' => 'Fase 0 — Preprocessing & Cleaning',
                'labeling'   => 'Fase 1 — Pelabelan Manual Test Set',
                default      => 'Tidak diketahui',
            };
        @endphp

        <!-- Kontrol Workspace Card (always visible) -->
        <div class="glass-card rounded-3xl p-6 shadow-xl relative">
            <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-slate-500/20 to-transparent"></div>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div>
                    <h2 class="text-xl font-bold font-outfit text-slate-100">Kontrol Workspace</h2>
                    <p class="text-xs text-slate-400 mt-1">Atur passkey dan pilih fase yang sedang dibuka. Kartu konten di bawah otomatis menyesuaikan fase yang dipilih.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-300">Fase aktif: <b class="text-white">{{ $modeLabel }}</b></span>
                    <span class="px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-300">Passkey aktif: <b class="text-white font-mono">{{ $workspaceSetting->access_passkey ?? '-' }}</b></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
                <!-- Mode switcher + multi-labeler toggle -->
                <form action="{{ route('admin.workspace-settings') }}" method="POST" class="glass-card rounded-2xl p-4 space-y-3">
                    @csrf
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Fase yang dibuka</label>
                    <select name="active_activity" class="w-full bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-medium font-outfit">
                        <option value="preprocess" @selected($activeMode === 'preprocess')>Fase 0 — Preprocessing & Cleaning (Audit Train Set)</option>
                        <option value="labeling" @selected($activeMode === 'labeling')>Fase 1 — Pelabelan Manual Test Set (Kunci Jawaban Tim)</option>
                    </select>

                    <!-- Multi-labeler toggle (only relevant for Fase 1) -->
                    <label class="flex items-center justify-between gap-3 mt-2 p-3 bg-slate-900/60 border border-slate-800 rounded-xl cursor-pointer select-none">
                        <div class="flex items-start gap-2.5">
                            <span class="w-2 h-2 rounded-full mt-1.5 {{ ($workspaceSetting->multi_labeler_mode ?? false) ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                            <div>
                                <span class="text-[11px] font-bold text-slate-200 block">Mode Multi-labeler per Gambar</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Untuk Fase 1: lebih dari satu labeler bisa melabel gambar yang sama (ukur inter-annotator agreement). Default OFF — satu gambar satu labeler (lease & lock).</span>
                            </div>
                        </div>
                        <input type="checkbox" name="multi_labeler_mode" value="1" {{ ($workspaceSetting->multi_labeler_mode ?? false) ? 'checked' : '' }} class="sr-only peer" onchange="this.closest('label').querySelector('span.rounded-full').classList.toggle('bg-emerald-400', this.checked); this.closest('label').querySelector('span.rounded-full').classList.toggle('bg-slate-600', !this.checked)">
                        <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-700 peer-checked:bg-indigo-600 transition-colors">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ ($workspaceSetting->multi_labeler_mode ?? false) ? 'translate-x-4' : 'translate-x-1' }}"></span>
                        </span>
                    </label>

                    <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-all">Simpan Pengaturan Workspace</button>
                </form>

                <!-- Passkey custom + generate random -->
                <form action="{{ route('admin.workspace-passkey') }}" method="POST" class="glass-card rounded-2xl p-4 space-y-3" id="passkey-form">
                    @csrf
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Passkey Workspace</label>
                    <p class="text-[10px] text-slate-400 leading-relaxed">Ketik passkey sendiri (alfanumerik, dash, underscore; 4–32 karakter) lalu klik <b class="text-amber-300">Simpan Passkey</b>. Atau klik <b class="text-indigo-300">Generate Random</b> untuk membuat passkey acak.</p>
                    <div class="flex items-stretch gap-2">
                        <input type="text" name="custom_passkey" id="custom_passkey_input" placeholder="KETIK-PASSKEY-ANDA" maxlength="32" minlength="4" pattern="[a-zA-Z0-9_\-]+" value="{{ $workspaceSetting->access_passkey ?? '' }}" class="flex-1 bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2.5 text-xs text-amber-200 font-mono uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-amber-500 placeholder-slate-600" autocomplete="off">
                        <button type="button" onclick="generateRandomPasskey()" class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-[11px] font-semibold transition-all whitespace-nowrap flex items-center gap-1.5" title="Buat passkey acak 8 karakter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                            </svg>
                            Random
                        </button>
                    </div>
                    <button type="submit" class="w-full py-3 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold transition-all flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Passkey
                    </button>
                </form>
            </div>
        </div>

        {{-- KONTEN DINAMIS: kartu/panel berganti sesuai fase, halaman tetap sama --}}
        @if(in_array($activeMode, ['preprocess', 'labeling'], true))
            @include('admin.partials.' . $activeMode)
        @else
            <div class="glass-card rounded-3xl p-8 text-center text-slate-400 text-sm">
                Mode aktif tidak dikenali ({{ $activeMode }}). Pilih fase di kartu Kontrol Workspace di atas.
            </div>
        @endif

        <!-- Footer Copyright -->
        <footer class="text-center pb-8 mt-8 text-[11px] text-slate-500 font-medium tracking-wide">
            &copy; 2026 &bull; Tim DATASCAPE 2026 Polban
        </footer>
    </main>

    <!-- AJAX Interactive actions scripting -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        // Approve Label Action
        function approveLabel(id) {
            axios.post(`/admin/approve/${id}`)
                .then(response => {
                    const row = document.getElementById(`row-pending-${id}`);
                    if (row) {
                        row.classList.add('row-fade-out');
                        setTimeout(() => row.remove(), 400);
                        refreshStatsQuietly();
                    }
                })
                .catch(error => {
                    console.error('Error approving label:', error);
                    alert('Gagal menyetujui label. Silakan coba lagi.');
                });
        }

        // Reject Label Action
        function rejectLabel(id, fromApproved = false) {
            axios.post(`/admin/reject/${id}`)
                .then(response => {
                    const prefix = fromApproved ? 'row-approved-' : 'row-pending-';
                    const row = document.getElementById(`${prefix}${id}`);
                    if (row) {
                        row.classList.add('row-fade-out');
                        setTimeout(() => row.remove(), 400);
                        refreshStatsQuietly();
                    }
                })
                .catch(error => {
                    console.error('Error rejecting label:', error);
                    alert('Gagal menolak label. Silakan coba lagi.');
                });
        }

        // Update Label to custom value Action
        function updateLabel(id, labelValue, fromApproved = false) {
            axios.post(`/admin/update/${id}`, {
                label: labelValue
            })
            .then(response => {
                const prefix = fromApproved ? 'row-approved-' : 'row-pending-';
                const row = document.getElementById(`${prefix}${id}`);
                if (row) {
                    if (!fromApproved) {
                        row.classList.add('row-fade-out');
                        setTimeout(() => row.remove(), 400);
                    } else {
                        window.location.reload();
                        return;
                    }
                    refreshStatsQuietly();
                }
            })
            .catch(error => {
                console.error('Error updating label:', error);
                alert('Gagal merubah label. Silakan coba lagi.');
            });
        }

        // Helper: refresh statistics values without full page reload
        function refreshStatsQuietly() {
            // Administrative actions — let user refresh manually if needed.
        }

        // Helper to update ZIP file name in input label
        function updateZipFileName(input) {
            const label = document.getElementById('zip-file-label');
            if (input.files && input.files.length > 0) {
                label.textContent = "Terpilih: " + input.files[0].name;
                label.classList.remove('text-slate-300');
                label.classList.add('text-indigo-400');
            } else {
                label.textContent = "Klik untuk memilih file ZIP";
                label.classList.remove('text-indigo-400');
                label.classList.add('text-slate-300');
            }
        }

        // Generate random passkey (client-side) and fill the input.
        // Server will also accept empty input and generate its own random,
        // but we pre-fill so admin can preview before saving.
        function generateRandomPasskey() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let out = '';
            for (let i = 0; i < 8; i++) {
                out += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            const input = document.getElementById('custom_passkey_input');
            if (input) {
                input.value = out;
                input.classList.add('ring-1', 'ring-indigo-500');
                setTimeout(() => input.classList.remove('ring-1', 'ring-indigo-500'), 800);
            }
        }

        // AJAX ZIP Upload with Progress Bar
        function uploadZip(event) {
            event.preventDefault();
            const fileInput = document.getElementById('dataset_zip');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih file ZIP terlebih dahulu.');
                return;
            }

            const formData = new FormData();
            formData.append('dataset_zip', fileInput.files[0]);

            const container = document.getElementById('upload-progress-container');
            const bar = document.getElementById('upload-progress-bar');
            const text = document.getElementById('upload-progress-text');
            const speedText = document.getElementById('upload-speed-text');
            const submitBtn = document.querySelector('#zip-upload-form button[type="submit"]');

            container.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

            let startTime = Date.now();

            axios.post("{{ route('admin.upload-dataset') }}", formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: function(progressEvent) {
                    if (progressEvent.lengthComputable) {
                        const percentComplete = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        bar.style.width = percentComplete + '%';
                        text.textContent = `Mengunggah: ${percentComplete}%`;
                        
                        const duration = (Date.now() - startTime) / 1000;
                        if (duration > 0) {
                            const speed = progressEvent.loaded / duration;
                            if (speed > 1024 * 1024) {
                                speedText.textContent = (speed / (1024 * 1024)).toFixed(1) + ' MB/s';
                            } else {
                                speedText.textContent = (speed / 1024).toFixed(0) + ' KB/s';
                            }
                        }
                    } else {
                        text.textContent = 'Mengunggah...';
                    }
                }
            })
            .then(response => {
                text.textContent = 'Mengekstrak & Sinkronisasi...';
                bar.classList.remove('bg-indigo-500');
                bar.classList.add('bg-emerald-500', 'animate-pulse');
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Error uploading zip:', error);
                let errorMsg = 'Terjadi kesalahan saat mengunggah.';
                if (error.response?.data?.errors?.dataset_zip) {
                    errorMsg = error.response.data.errors.dataset_zip[0];
                } else if (error.response?.data?.message) {
                    errorMsg = error.response.data.message;
                }
                alert('Gagal mengunggah dataset: ' + errorMsg);
                container.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</body>
</html>
