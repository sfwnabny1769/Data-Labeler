{{-- Partial untuk Fase 1: Pelabelan Manual Test Set (Kunci Jawaban Tim) --}}
{{-- Variabel tersedia: $stats, $pendingItems, $approvedItems, $manualExamples, $pendingLabelers, 
     $filterUser, $searchPending, $approvedLabelers, $filterApprovedUser, $searchApproved,
     $workspaceSetting, $competitionClasses, $classStats, $activeLeases --}}

<!-- Synchronization & Download Toolbar -->
<div class="glass-card rounded-3xl p-6 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl relative">
    <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-slate-500/20 to-transparent"></div>
    <div>
        <h2 class="text-xl font-bold font-outfit text-slate-100">Manajemen Dataset & Hasil</h2>
        <p class="text-xs text-slate-400 mt-1">Gunakan tombol sync untuk memindai folder lokal <code class="bg-slate-900 px-1.5 py-0.5 rounded text-indigo-300">public/dataset</code>, dan tombol unduh untuk mendapatkan file CSV akhir.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <form action="{{ route('admin.sync') }}" method="POST" class="w-full sm:w-auto">
            @csrf
            <button type="submit" class="w-full px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/15 hover:shadow-indigo-600/30 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                </svg>
                Sinkronisasi Folder
            </button>
        </form>

        <a href="{{ route('admin.download') }}" class="w-full sm:w-auto px-5 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-emerald-500/15 hover:shadow-emerald-500/30 transform hover:-translate-y-0.5 active:translate-y-0 transition-all text-center flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Unduh CSV Kaggle
        </a>

        <a href="{{ route('admin.download-split') }}" class="w-full sm:w-auto px-4 py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-xs font-semibold shadow-md transform hover:-translate-y-0.5 active:translate-y-0 transition-all text-center flex items-center justify-center gap-2" title="Unduh Train/Val 80:20 ZIP">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 011.414.586l4.414 4.414a1 1 0 01.586 1.414V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
            </svg>
            Split 80:20 (ZIP)
        </a>

        <a href="{{ route('admin.download-full') }}" class="w-full sm:w-auto px-4 py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-xs font-semibold shadow-md transform hover:-translate-y-0.5 active:translate-y-0 transition-all text-center flex items-center justify-center gap-2" title="Unduh CSV Lengkap dengan Metadata">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Full Metadata
        </a>
    </div>
</div>

<!-- Upload Panel Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

    <!-- Upload Dataset ZIP Card -->
    <div class="glass-card rounded-3xl p-6 relative shadow-xl">
        <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
        <div class="mb-4">
            <h3 class="text-lg font-bold font-outfit text-slate-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Upload ZIP Dataset (Data Test)
            </h3>
            <p class="text-xs text-slate-400 mt-1">Unggah file ZIP berisi kumpulan gambar test langsung dari komputer lokal Anda ke server.</p>

            <div class="mt-4 p-3 bg-red-500/10 border border-red-500/25 rounded-2xl flex items-start gap-2.5 text-red-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <span class="text-xs font-bold block uppercase tracking-wider">Peringatan Penting:</span>
                    <span class="text-[10px] font-medium leading-relaxed block mt-0.5 text-slate-300">Mengunggah dataset ZIP baru dapat menimpa data gambar lama dan menghapus seluruh progres pelabelan. Pastikan Anda telah mengunduh file CSV hasil pelabelan sebelumnya!</span>
                </div>
            </div>
        </div>

        <form id="zip-upload-form" onsubmit="uploadZip(event)" class="space-y-4">
            @csrf
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-2xl cursor-pointer bg-slate-900/40 hover:bg-slate-900/60 transition-all duration-200">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2-8H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V8l-6-6z" />
                        </svg>
                        <p class="mb-1 text-xs text-slate-300 font-semibold" id="zip-file-label">Klik untuk memilih file ZIP</p>
                        <p class="text-[10px] text-slate-500">ZIP (Maksimal 200MB)</p>
                    </div>
                    <input type="file" name="dataset_zip" id="dataset_zip" accept=".zip" class="hidden" required onchange="updateZipFileName(this)" />
                </label>
            </div>

            <div id="upload-progress-container" class="hidden space-y-2 p-3 bg-slate-900/60 rounded-xl border border-slate-800">
                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <span id="upload-progress-text">Mengunggah: 0%</span>
                    <span id="upload-speed-text">-- KB/s</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-850">
                    <div id="upload-progress-bar" class="bg-indigo-500 h-full rounded-full transition-all duration-150" style="width: 0%"></div>
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/20 transition-all duration-200 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Ekstrak & Sinkronisasi ZIP
            </button>
        </form>
    </div>

    <!-- Upload Guidelines Examples Card -->
    <div class="glass-card rounded-3xl p-6 relative shadow-xl">
        <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-purple-500/30 to-transparent"></div>
        <div class="mb-4">
            <h3 class="text-lg font-bold font-outfit text-slate-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Upload Gambar Contoh (Guidelines)
            </h3>
            <p class="text-xs text-slate-400 mt-1">Unggah beberapa gambar sampel untuk dijadikan panduan klasifikasi di workspace pelabelan.</p>
        </div>

        <form action="{{ route('admin.upload-examples') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <label for="label_select" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Kelas</label>
                    <select name="label" id="label_select" class="w-full bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-purple-500 font-medium">
                        @foreach($competitionClasses as $cls)
                            <option value="{{ $cls['id'] }}">{{ $cls['id'] }} - {{ $cls['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Pilih File Gambar</label>
                    <div class="relative">
                        <input type="file" name="example_images[]" id="example_images" accept="image/*" multiple required class="w-full bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-purple-500 font-medium" />
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-purple-600/20 transition-all duration-200 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Upload Gambar Contoh
            </button>
        </form>

        <div class="mt-4 pt-4 border-t border-slate-800">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Daftar Contoh Saat Ini:</h4>
            <div class="space-y-3 max-h-[140px] overflow-y-auto pr-1">
                @foreach($competitionClasses as $cls)
                    @php
                        $lbl = $cls['id'];
                        $color = $cls['color'] ?? 'indigo';
                    @endphp
                    <div class="flex flex-col gap-1.5">
                        <div class="text-[11px] font-bold text-slate-300 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-{{ $color }}-500"></span>
                            Kelas {{ $lbl }} ({{ $cls['name'] }})
                        </div>
                        @if(empty($manualExamples[$lbl]))
                            <div class="text-[10px] text-slate-500 italic pl-3.5">Belum ada contoh manual.</div>
                        @else
                            <div class="flex flex-wrap gap-2 pl-3.5">
                                @foreach($manualExamples[$lbl] as $ex)
                                    <div class="relative w-10 h-10 rounded border border-slate-850 bg-slate-900 overflow-hidden group">
                                        <img src="{{ $ex['url'] }}" class="w-full h-full object-cover">
                                        <form action="{{ route('admin.delete-example') }}" method="POST" class="absolute inset-0 bg-slate-950/80 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-150">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $ex['id'] }}">
                                            <button type="submit" class="text-red-400 hover:text-red-300" title="Hapus Gambar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Statistics Dashboard -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="glass-card rounded-2xl p-5">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Database</span>
        <span class="text-3xl font-extrabold font-outfit text-slate-100 mt-2 block">{{ number_format($stats['total'], 0, ',', '.') }}</span>
        <span class="text-[10px] text-slate-400 mt-1 block">Seluruh data yang terdaftar</span>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Belum Dilabel (Unlabeled)</span>
        <span class="text-3xl font-extrabold font-outfit text-indigo-400 mt-2 block">{{ number_format($stats['unlabeled'], 0, ',', '.') }}</span>
        <span class="text-[10px] text-slate-400 mt-1 block">Tersisa di pool workspace</span>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Perlu Validasi (Pending)</span>
        <span class="text-3xl font-extrabold font-outfit text-amber-400 mt-2 block">{{ number_format($stats['pending'], 0, ',', '.') }}</span>
        <span class="text-[10px] text-slate-400 mt-1 block">Menunggu persetujuan admin</span>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Disetujui (Approved)</span>
        <span class="text-3xl font-extrabold font-outfit text-emerald-400 mt-2 block">{{ number_format($stats['approved'], 0, ',', '.') }}</span>
        <span class="text-[10px] text-slate-400 mt-1 block">Akan diexport ke CSV hasil</span>
    </div>
</section>

<!-- Active Leases & Team Monitoring Panel (AFK Release) -->
<div class="glass-card rounded-3xl p-6 relative shadow-xl">
    <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                <h3 class="font-outfit font-bold text-base text-slate-100">Antrean Kerja Tim & Sewa Gambar (Active Leases)</h3>
                <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2.5 py-0.5 rounded-full font-mono">{{ number_format($stats['active_leases'] ?? 0) }} img di-hold</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Daftar anggota tim yang sedang memegang gambar di buffer mereka. Jika ada labeler AFK, lepaskan sewa agar gambar kembali ke antrean publik.</p>
        </div>

        @if(isset($activeLeases) && $activeLeases->isNotEmpty())
            <form action="{{ route('admin.leases.release-all') }}" method="POST" onsubmit="return confirm('Lepas seluruh sewa gambar aktif? Seluruh anggota tim akan me-refresh antrean mereka.')">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-red-600/80 hover:bg-red-600 text-white rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 shadow-lg shadow-red-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Reset Semua Sewa (AFK Release All)
                </button>
            </form>
        @endif
    </div>

    @if(isset($activeLeases) && $activeLeases->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($activeLeases as $lease)
                <div class="flex items-center justify-between p-4 bg-slate-900/60 border border-slate-800 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/20 text-indigo-300 flex items-center justify-center font-bold text-xs uppercase font-mono">
                            {{ strtoupper(substr($lease->reserved_by, 0, 2)) }}
                        </div>
                        <div>
                            <span class="font-bold text-xs text-slate-200 block">{{ $lease->reserved_by }}</span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Memegang <b class="text-indigo-300">{{ $lease->total_held }}</b> gambar di buffer</span>
                        </div>
                    </div>
                    <form action="{{ route('admin.leases.release-user') }}" method="POST" onsubmit="return confirm('Lepas gambar yang tertahan oleh {{ $lease->reserved_by }}?')">
                        @csrf
                        <input type="hidden" name="nickname" value="{{ $lease->reserved_by }}">
                        <button type="submit" class="px-3 py-1.5 bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 rounded-lg text-[11px] font-semibold transition-all">
                            Lepas Lease
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-6 text-center bg-slate-900/40 border border-slate-800/60 rounded-2xl text-slate-400 text-xs">
            Tidak ada gambar yang sedang tertahan. Seluruh gambar tersedia di antrean publik untuk tim.
        </div>
    @endif
</div>

<!-- Class Distribution Summary (Approved only) -->
<div class="glass-card rounded-3xl p-6 relative">
    <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-slate-500/10 to-transparent"></div>
    <h3 class="font-outfit font-bold text-sm text-slate-300 uppercase tracking-widest mb-4">Distribusi Kelas Terlabel Disetujui</h3>

    <div class="grid grid-cols-1 sm:grid-cols-{{ max(1, min(4, count($competitionClasses))) }} gap-4">
        @foreach($competitionClasses as $cls)
            @php
                $color = $cls['color'] ?? 'indigo';
            @endphp
            <div class="flex items-center gap-3 bg-slate-900/40 border border-slate-800/80 rounded-2xl p-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $color }}-500/15 border border-{{ $color }}-500/20 text-{{ $color }}-400 flex items-center justify-center font-bold font-mono">{{ $cls['id'] }}</div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">{{ $cls['name'] }}</span>
                    <span class="text-lg font-bold text-slate-200">{{ number_format($classStats[$cls['id']]['count'] ?? 0, 0, ',', '.') }} <span class="text-xs font-normal text-slate-500">img</span></span>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Pending Items Table -->
<section class="glass-card rounded-3xl p-6 md:p-8 relative shadow-2xl">
    <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-amber-500/30 to-transparent"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold font-outfit text-slate-100 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                Menunggu Validasi ({{ $pendingItems->total() }})
            </h2>
            <p class="text-xs text-slate-400 mt-1">Tinjau hasil kerjaan teman-temanmu. Setujui, tolak, atau ubah label langsung.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('admin') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <input type="text" name="search_pending" value="{{ $searchPending }}" placeholder="Cari ID / nama file..." class="bg-slate-900 border border-slate-700/60 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500 font-medium placeholder-slate-500 w-44">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                <select name="filter_user" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500 font-medium">
                    <option value="">Semua Labeler (No Filter)</option>
                    @foreach($pendingLabelers as $labeler)
                        <option value="{{ $labeler }}" {{ $filterUser == $labeler ? 'selected' : '' }}>
                            Filter: {{ $labeler }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition">Cari</button>
                @if($filterUser || $searchPending)
                    <a href="{{ route('admin') }}" class="p-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-xl text-xs font-semibold transition" title="Reset Filter & Pencarian">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </form>

            <form action="{{ route('admin.approve-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui semua data ini secara massal?')" class="flex items-center">
                @csrf
                @if($filterUser)<input type="hidden" name="labeled_by" value="{{ $filterUser }}">@endif
                @if($searchPending)<input type="hidden" name="search" value="{{ $searchPending }}">@endif
                <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-emerald-600/20 transform hover:-translate-y-0.5 active:translate-y-0 transition duration-150 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @if($filterUser && $searchPending)
                        Setujui Semua Cocok
                    @elseif($filterUser)
                        Setujui Semua dari "{{ $filterUser }}"
                    @elseif($searchPending)
                        Setujui Hasil Pencarian
                    @else
                        Setujui Semua (Global)
                    @endif
                </button>
            </form>

            <form action="{{ route('admin.reject-all-pending') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menolak semua usulan label ini secara massal? Semuanya akan kembali ke pool belum terlabel.')" class="flex items-center">
                @csrf
                @if($filterUser)<input type="hidden" name="labeled_by" value="{{ $filterUser }}">@endif
                @if($searchPending)<input type="hidden" name="search" value="{{ $searchPending }}">@endif
                <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-red-600/20 transform hover:-translate-y-0.5 active:translate-y-0 transition duration-150 flex items-center gap-1.5" title="Kembalikan semua usulan ini ke pool belum terlabel">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    @if($filterUser && $searchPending)
                        Tolak Semua Cocok
                    @elseif($filterUser)
                        Tolak Semua dari "{{ $filterUser }}"
                    @elseif($searchPending)
                        Tolak Hasil Pencarian
                    @else
                        Tolak Semua (Global)
                    @endif
                </button>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/20">
        <table class="w-full border-collapse text-left text-sm text-slate-300">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-900/40 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-4 px-6 w-28">Preview</th>
                    <th class="py-4 px-6">Nama File</th>
                    <th class="py-4 px-6">Labeler (Prodi)</th>
                    <th class="py-4 px-6">Label Usulan</th>
                    <th class="py-4 px-6 text-center w-72">Aksi Validasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60" id="pending-table-body">
                @forelse($pendingItems as $item)
                    <tr class="hover:bg-slate-900/20 transition-colors" id="row-pending-{{ $item->id }}">
                        <td class="py-4 px-6">
                            <div class="relative group w-16 h-12 rounded-lg bg-slate-900 border border-slate-850 overflow-hidden flex items-center justify-center cursor-zoom-in">
                                <img src="{{ $item->url }}" alt="{{ $item->filename }}" class="h-full w-full object-contain transition-transform duration-200 group-hover:scale-125">
                            </div>
                        </td>
                        <td class="py-4 px-6 font-medium text-slate-200">{{ $item->filename }}</td>
                        <td class="py-4 px-6">
                            <div class="font-semibold text-slate-200">{{ $item->labeled_by }}</div>
                            <div class="text-[11px] text-slate-400 font-medium">{{ $item->prodi }}</div>
                        </td>
                        <td class="py-4 px-6">
                            @foreach($competitionClasses as $cls)
                                @if($item->label === $cls['id'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $cls['color'] ?? 'indigo' }}-500/10 text-{{ $cls['color'] ?? 'indigo' }}-400 border border-{{ $cls['color'] ?? 'indigo' }}-500/20">
                                        {{ $cls['id'] }} - {{ $cls['name'] }}
                                    </span>
                                @endif
                            @endforeach
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="approveLabel({{ $item->id }})" title="Setujui Label" class="p-2 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 hover:border-emerald-500/40 text-emerald-400 rounded-xl transition-all duration-150 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </button>
                                @foreach($competitionClasses as $cls)
                                    <button onclick="updateLabel({{ $item->id }}, {{ $cls['id'] }})" title="Ubah & Setujui ke {{ $cls['name'] }} ({{ $cls['id'] }})" class="px-2 py-1 bg-slate-800 hover:bg-{{ $cls['color'] ?? 'indigo' }}-600/20 border border-slate-700 hover:border-{{ $cls['color'] ?? 'indigo' }}-500/30 text-slate-400 hover:text-{{ $cls['color'] ?? 'indigo' }}-400 rounded-lg text-xs font-bold transition-all">Set {{ $cls['id'] }}</button>
                                @endforeach
                                <button onclick="rejectLabel({{ $item->id }})" title="Tolak Label (Kembalikan ke Pool)" class="p-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/40 text-red-400 rounded-xl transition-all duration-150 active:scale-95 ml-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500 font-medium">Tidak ada data label yang perlu divalidasi. Pekerjaan teman-temanmu sudah beres!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $pendingItems->appends(['approved_page' => $approvedItems->currentPage(), 'filter_user' => $filterUser, 'search_pending' => $searchPending])->links() }}
    </div>
</section>

<!-- Approved Items Table -->
<section class="glass-card rounded-3xl p-6 md:p-8 relative shadow-lg">
    <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold font-outfit text-slate-100 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                Sudah Divalidasi / Disetujui ({{ $approvedItems->total() }})
            </h2>
            <p class="text-xs text-slate-400 mt-1">Daftar label yang disetujui. Kamu bisa merevisi label jika ada kekeliruan.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('admin') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <input type="text" name="search_approved" value="{{ $searchApproved }}" placeholder="Cari ID / nama file..." class="bg-slate-900 border border-slate-700/60 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 font-medium placeholder-slate-500 w-44">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                <select name="filter_approved_user" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700/60 rounded-xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 font-medium">
                    <option value="">Semua Labeler (No Filter)</option>
                    @foreach($approvedLabelers as $labeler)
                        <option value="{{ $labeler }}" {{ $filterApprovedUser == $labeler ? 'selected' : '' }}>Filter: {{ $labeler }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition">Cari</button>
                @if($filterApprovedUser || $searchApproved)
                    <a href="{{ route('admin') }}" class="p-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-xl text-xs font-semibold transition" title="Reset Filter & Pencarian">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </a>
                @endif
            </form>

            <form action="{{ route('admin.reject-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan semua persetujuan data ini secara massal? Semuanya akan kembali ke pool belum terlabel.')" class="flex items-center">
                @csrf
                @if($filterApprovedUser)<input type="hidden" name="labeled_by" value="{{ $filterApprovedUser }}">@endif
                @if($searchApproved)<input type="hidden" name="search" value="{{ $searchApproved }}">@endif
                <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-red-600/20 transform hover:-translate-y-0.5 active:translate-y-0 transition duration-150 flex items-center gap-1.5" title="Kembalikan semua ke unlabeled pool">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" /></svg>
                    @if($filterApprovedUser && $searchApproved)
                        Batalkan Semua Cocok
                    @elseif($filterApprovedUser)
                        Batalkan Semua dari "{{ $filterApprovedUser }}"
                    @elseif($searchApproved)
                        Batalkan Hasil Pencarian
                    @else
                        Batalkan Semua (Global)
                    @endif
                </button>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/10">
        <table class="w-full border-collapse text-left text-sm text-slate-400">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-900/20 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <th class="py-4 px-6 w-24">Preview</th>
                    <th class="py-4 px-6">Nama File</th>
                    <th class="py-4 px-6">Labeler (Prodi)</th>
                    <th class="py-4 px-6">Label Disetujui</th>
                    <th class="py-4 px-6 text-center w-60">Ubah Kembali</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/40" id="approved-table-body">
                @forelse($approvedItems as $item)
                    <tr class="hover:bg-slate-900/10 transition-colors" id="row-approved-{{ $item->id }}">
                        <td class="py-3 px-6">
                            <div class="w-14 h-10 rounded bg-slate-900 border border-slate-850 overflow-hidden flex items-center justify-center">
                                <img src="{{ $item->url }}" alt="{{ $item->filename }}" class="h-full w-full object-contain">
                            </div>
                        </td>
                        <td class="py-3 px-6 font-medium text-slate-300">{{ $item->filename }}</td>
                        <td class="py-3 px-6">
                            <div class="font-medium text-slate-300 text-xs">{{ $item->labeled_by }}</div>
                            <div class="text-[10px] text-slate-500">{{ $item->prodi }}</div>
                        </td>
                        <td class="py-3 px-6 font-bold text-slate-200">
                            @foreach($competitionClasses as $cls)
                                @if($item->label === $cls['id'])
                                    <span class="text-{{ $cls['color'] ?? 'indigo' }}-400">{{ $cls['id'] }} - {{ $cls['name'] }}</span>
                                @endif
                            @endforeach
                        </td>
                        <td class="py-3 px-6">
                            <div class="flex items-center justify-center gap-1.5">
                                @foreach($competitionClasses as $cls)
                                    <button onclick="updateLabel({{ $item->id }}, {{ $cls['id'] }}, true)" class="px-2 py-0.5 text-[10px] bg-slate-800 border border-slate-700 hover:border-{{ $cls['color'] ?? 'indigo' }}-500/35 hover:text-{{ $cls['color'] ?? 'indigo' }}-400 rounded font-semibold transition-all">Set {{ $cls['id'] }}</button>
                                @endforeach
                                <button onclick="rejectLabel({{ $item->id }}, true)" title="Batalkan Persetujuan (Balik ke Pool)" class="p-1 bg-red-500/10 hover:bg-red-500/25 border border-red-500/20 text-red-400 rounded ml-1.5 transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-600 text-xs">Belum ada data label yang disetujui.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $approvedItems->appends(['pending_page' => $pendingItems->currentPage(), 'filter_approved_user' => $filterApprovedUser, 'search_approved' => $searchApproved])->links() }}
    </div>
</section>

{{-- ============================================================
     BUTIR 9: Panel Resolution Dispute Multi-Labeler
     Hanya tampil saat multi-labeler mode aktif.
     ============================================================ --}}
@if($multiLabelerMode)
<section class="glass-card rounded-3xl p-6 md:p-8 mt-8 border-2 border-amber-500/30">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
            </div>
            <div>
                <h3 class="font-outfit font-bold text-base text-slate-100">Antrean Resolution Dispute</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Mode multi-labeler aktif. Setiap gambar dikumpulkan dari
                    <b class="text-slate-200">{{ $requiredLabelers }}</b> labeler berbeda.
                    Kalau suara tidak sama -> masuk antrean ini untuk diputuskan manual.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <span class="px-3 py-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] font-bold">Dispute: {{ $disputeStats['dispute'] }}</span>
            <span class="px-3 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-[11px] font-bold">Mengumpulkan: {{ $disputeStats['collecting'] }}</span>
            <span class="px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[11px] font-bold">Consensus: {{ $disputeStats['agreed'] }}</span>
            <span class="px-3 py-2 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-300 text-[11px] font-bold">Total Suara: {{ $disputeStats['total_votes'] }}</span>
        </div>
    </div>

    @if($disputeItems->isEmpty())
        <div class="py-10 text-center">
            <p class="text-sm text-slate-400 font-semibold">Tidak ada dispute yang menunggu.</p>
            <p class="text-xs text-slate-500 mt-1">Semua gambar yang dikumpulkan justru mendapat suara yang sama.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($disputeItems as $item)
                @php $voteGroups = $item->votes->groupBy('label'); @endphp
                <div class="rounded-2xl border border-slate-700/60 bg-slate-900/50 p-4">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-5">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <img src="{{ $item->url }}" alt="{{ $item->filename }}"
                                 class="w-20 h-20 rounded-xl object-cover border-2 border-slate-700 shrink-0 bg-slate-800">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-200 font-mono truncate">{{ $item->filename }}</p>
                                <p class="text-[10px] text-slate-500 mt-1">
                                    {{ $item->votes->count() }} suara &bull; oleh {{ $item->labeled_by ?: '—' }}
                                </p>
                                @if($item->dispute_note)
                                    <p class="text-[10px] text-emerald-400 mt-1 font-semibold">Catatan: {{ $item->dispute_note }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 shrink-0">
                            @foreach($voteGroups as $labelId => $voters)
                                <div class="px-3 py-2 rounded-xl border-2 text-center {{ $loop->first ? 'border-rose-500/50 bg-rose-500/10' : 'border-slate-600/50 bg-slate-800/50' }}">
                                    <p class="text-[9px] uppercase tracking-wider font-bold {{ $loop->first ? 'text-rose-300' : 'text-slate-400' }}">{{ $voters->count() }} suara</p>
                                    <p class="text-xs font-bold text-slate-100 mt-0.5">{{ $competitionClasses[$labelId]['name'] ?? 'Kelas ' . $labelId }}</p>
                                    <p class="text-[9px] text-slate-500 mt-0.5">{{ $voters->pluck('labeled_by')->join(', ') }}</p>
                                </div>
                            @endforeach
                        </div>

                        <form action="{{ route('admin.dispute.resolve', $item->id) }}" method="POST"
                              class="flex flex-wrap items-center gap-2 shrink-0">
                            @csrf
                            <select name="label" required
                                    class="bg-slate-900 border-2 border-slate-700 text-slate-200 text-xs font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-amber-500">
                                <option value="">Putuskan label…</option>
                                @foreach($competitionClasses as $cls)
                                    <option value="{{ $cls['id'] }}">{{ $cls['name'] }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="dispute_note" placeholder="Catatan (opsional)" maxlength="255"
                                   class="bg-slate-900 border-2 border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:outline-none focus:border-amber-500 w-36">
                            <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold border-2 border-black transition-transform active:translate-y-0.5">
                                Selesaikan
                            </button>
                        </form>

                        <form action="{{ route('admin.dispute.reopen', $item->id) }}" method="POST"
                              onsubmit="return confirm('Kembalikan gambar ini ke antrean dan hapus semua suara?')" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl text-xs font-bold border-2 border-black transition-transform active:translate-y-0.5">
                                Label Ulang
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endif