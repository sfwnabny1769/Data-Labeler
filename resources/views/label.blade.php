<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $competitionName ?? 'Data Labeler Workspace' }}</title>
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
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
    <!-- Axios for HTTP Requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.05), transparent 45%),
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
        .animate-fade-in {
            animation: fadeIn 0.2s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
        /* Double buffered transition */
        .buffer-img {
            transition: opacity 0.12s ease-in-out;
            will-change: opacity;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
@include('partials.neo')
</head>
<body class="app-labeler min-h-screen text-slate-100 flex flex-col relative pb-8">

    <!-- CSRF Token for Axios -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Glowing Background Orbs -->
    <div class="absolute top-10 left-10 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="absolute bottom-10 right-10 w-[500px] h-[500px] bg-purple-500/5 rounded-full blur-[150px] pointer-events-none"></div>

    <!-- Navigation Header -->
    <header class="glass-header sticky top-0 z-50 py-4 px-6 md:px-12 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <span class="font-outfit font-bold text-lg tracking-tight bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">{{ $competitionName ?? 'Data Labeler' }}</span>
                <span class="text-[10px] uppercase font-bold tracking-widest text-indigo-400 bg-indigo-400/10 px-1.5 py-0.5 rounded ml-2">Zero-Latency Workspace</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 bg-slate-900/60 border border-slate-700/50 rounded-xl py-1.5 px-4">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-semibold text-slate-300">Halo, <span class="text-white font-bold">{{ $nickname }}</span> <span class="text-slate-400 text-[11px] ml-1">({{ $prodi }})</span></span>
            </div>
            
            <form action="{{ route('nickname.logout') }}" method="POST" class="inline" onsubmit="releaseHeldLeases()">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-red-400 transition-colors duration-200">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <!-- Main Workspace Layout -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 md:px-8 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8 z-10">
        
        <!-- Left & Center Column: Workspace (8 Cols) -->
        <section class="lg:col-span-8 flex flex-col gap-6">
            
            <!-- Statistics Bar -->
            <div class="grid grid-cols-3 gap-4 nb-stats">
                <div class="glass-card rounded-2xl p-4 flex flex-col justify-center">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sisa Gambar</span>
                    <span id="stat-left" class="text-2xl font-bold font-outfit text-indigo-300 mt-1">-</span>
                </div>
                <div class="glass-card rounded-2xl p-4 flex flex-col justify-center">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Dilabel</span>
                    <span id="stat-total" class="text-2xl font-bold font-outfit text-purple-300 mt-1">-</span>
                </div>
                <div class="glass-card rounded-2xl p-4 flex flex-col justify-center">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kontribusi Anda</span>
                    <span id="stat-user" class="text-2xl font-bold font-outfit text-emerald-300 mt-1">-</span>
                </div>
            </div>

            <!-- Workspace Card -->
            <div class="glass-card rounded-3xl p-6 md:p-8 flex flex-col items-center justify-between min-h-[520px] relative shadow-2xl">
                <!-- Top subtle border -->
                <div class="absolute -top-px left-8 right-8 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>

                <!-- Alert Message -->
                <div id="alert-box" class="hidden absolute top-4 left-4 right-4 z-30 p-3.5 rounded-xl border text-sm font-semibold transition-all duration-300 flex items-center gap-2">
                    <span id="alert-msg"></span>
                </div>

                <!-- Image Frame Container (Double-Buffered for Instant 0ms Swap) -->
                <div class="w-full flex-grow flex items-center justify-center min-h-[340px] mb-6 relative rounded-2xl bg-slate-950/40 border border-slate-800/80 overflow-hidden group nb-img-frame">
                    
                    <!-- Top Status Badges: Buffer Count & Sync Status -->
                    <div class="absolute top-3 right-3 z-20 flex items-center gap-2">
                        <!-- Outbox Sync Pill -->
                        <div id="sync-badge" class="px-2.5 py-1 rounded-full bg-slate-900/80 backdrop-blur border border-slate-700/60 text-[10px] font-semibold text-emerald-300 flex items-center gap-1.5 shadow-sm">
                            <span id="sync-dot" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span id="sync-text">Tersinkron</span>
                        </div>

                        <!-- Client Buffer Pill -->
                        <div id="buffer-badge" class="px-2.5 py-1 rounded-full bg-slate-900/80 backdrop-blur border border-slate-700/60 text-[10px] font-semibold text-indigo-300 flex items-center gap-1.5 shadow-sm" title="Gambar yang sudah di-cache dalam memori browser">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                            <span id="buffer-text">Buffer: 0</span>
                        </div>
                    </div>

                    <!-- Loading Spinner (hanya muncul saat buffer awal kosong) -->
                    <div id="image-loader" class="absolute inset-0 bg-slate-900/80 flex flex-col items-center justify-center gap-3 transition-opacity duration-200 z-10">
                        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-medium text-indigo-300">Menyiapkan antrean gambar...</p>
                    </div>

                    <!-- Complete Screen (Hidden initially) -->
                    <div id="complete-screen" class="hidden absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center p-8 text-center z-30">
                        <div class="w-20 h-20 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-extrabold font-outfit text-white"  >Semua Gambar Selesai Dilabel!</h3>
                        <p class="text-slate-400 mt-2 max-w-sm text-white" >Semua dataset di folder telah berhasil dilabeli. Jika masih ada gambar yang tertahan di sesi lain, Anda bisa mencoba menyegarkan antrean.</p>
                        <button onclick="replenishBuffer(true)" class="mt-6 px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-xs font-semibold rounded-xl transition-all duration-200 border border-slate-700 cursor-pointer">
                            Segarkan Antrean Buffer
                        </button>
                    </div>

                    <!-- Double-Buffered Images (Swap instantly with zero perceptible latency) -->
                    <img 
                        id="target-image-a" 
                        src="" 
                        alt="Label Target A" 
                        class="buffer-img absolute max-h-[360px] max-w-full object-contain select-none opacity-0"
                    >
                    <img 
                        id="target-image-b" 
                        src="" 
                        alt="Label Target B" 
                        class="buffer-img absolute max-h-[360px] max-w-full object-contain select-none opacity-0"
                    >
                </div>

                <!-- Interaction / Label Selection Section (Generated dynamically from $classes) -->
                <div id="label-controls" class="w-full">
                    <p class="text-center text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Pilih Label Klasifikasi</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-{{ max(1, min(4, count($classes))) }} gap-4">
                        @foreach($classes as $cls)
                            @php
                                $color = $cls['color'] ?? 'indigo';
                            @endphp
                            <button 
                                type="button"
                                onclick="submitLabel({{ $cls['id'] }})" 
                                class="group relative flex flex-col items-center justify-center p-4 bg-gradient-to-br from-{{ $color }}-500/10 to-slate-900/40 hover:from-{{ $color }}-500/20 hover:to-slate-900/60 border border-{{ $color }}-500/20 hover:border-{{ $color }}-500/40 rounded-2xl transition-all duration-150 active:scale-95 text-center shadow-lg hover:shadow-{{ $color }}-500/10 cursor-pointer nb-choice"
                            >
                                <span class="absolute top-2.5 right-3 text-[10px] font-bold px-1.5 py-0.5 rounded bg-{{ $color }}-500/20 text-{{ $color }}-300 font-mono select-none">
                                    {{ $cls['shortcut_label'] }}
                                </span>
                                <div class="w-10 h-10 rounded-full bg-{{ $color }}-500/15 text-{{ $color }}-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform font-bold font-mono text-sm">
                                    {{ $cls['id'] }}
                                </div>
                                <span class="font-bold text-sm text-slate-200">{{ $cls['name'] }}</span>
                                <span class="text-[10px] text-slate-400 mt-1">{{ $cls['badge'] ?? $cls['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        onclick="skipImage()"
                        class="w-full mt-4 flex items-center justify-center gap-2 p-3 bg-slate-800/70 hover:bg-slate-700/80 border border-slate-700 hover:border-slate-600 rounded-2xl text-xs font-semibold text-slate-300 transition-all duration-200 active:scale-[0.99] cursor-pointer nb-skip"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M4 12h16" />
                        </svg>
                        Lewati Gambar
                        <span class="text-[10px] text-slate-500">(S)</span>
                    </button>
                </div>

            </div>
        </section>

        <!-- Right Column: Guidelines & Leaderboard (4 Cols) -->
        <section class="lg:col-span-4 flex flex-col gap-6 nb-side">
            
            <!-- Guideline Box (Dynamic per competition class) -->
            <div class="glass-card rounded-3xl p-6 relative nb-guide">
                <div class="absolute -top-px left-6 right-6 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
                <h3 class="font-outfit font-bold text-lg mb-4 flex items-center gap-2 text-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    Pedoman Pelabelan
                </h3>
                
                <div class="space-y-4">
                    @foreach($classes as $cls)
                        @php
                            $lbl = $cls['id'];
                            $color = $cls['color'] ?? 'indigo';
                        @endphp
                        <div class="flex flex-col bg-slate-900/40 border border-slate-800/80 rounded-xl p-3">
                            <div class="flex gap-3">
                                <span class="w-1.5 h-auto bg-{{ $color }}-500 rounded-full flex-shrink-0"></span>
                                <div>
                                    <h4 class="text-xs font-bold text-{{ $color }}-400 uppercase tracking-wide">{{ $cls['name'] }} ({{ $lbl }})</h4>
                                    <p class="text-xs text-slate-400 mt-1">{{ $cls['desc'] }}</p>
                                </div>
                            </div>
                            @if(!empty($examples[$lbl]))
                                <div class="grid grid-cols-4 gap-1.5 mt-2.5 ml-4.5 pl-3 example-slideshow-container" data-label="{{ $lbl }}" data-images="{{ json_encode($examples[$lbl]) }}">
                                    @foreach(array_slice($examples[$lbl], 0, 4) as $index => $imgUrl)
                                        <div class="relative group aspect-square rounded-lg bg-slate-950/40 border border-slate-800/80 overflow-hidden cursor-zoom-in">
                                            <img 
                                                id="example-{{ $lbl }}-slot-{{ $index }}"
                                                src="{{ $imgUrl }}" 
                                                class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110 opacity-100" 
                                                onclick="showLightbox(this.src)"
                                            >
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Leaderboard Box -->
            <div class="glass-card rounded-3xl p-6 relative flex flex-col flex-grow min-h-[300px] nb-leader">
                <div class="absolute -top-px left-6 right-6 h-px bg-gradient-to-r from-transparent via-purple-500/30 to-transparent"></div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-outfit font-bold text-lg flex items-center gap-2 text-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Leaderboard Live
                    </h3>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800/60 px-2 py-0.5 rounded border border-slate-700/30">Live updates</span>
                </div>

                <!-- Leaderboard list container -->
                <div class="flex-grow flex flex-col gap-2 overflow-y-auto max-h-[320px] pr-1" id="leaderboard-list">
                    <div class="py-12 text-center text-slate-500 text-xs font-medium">Memuat peringkat...</div>
                </div>
            </div>

        </section>
    </main>

    <!-- Footer Copyright -->
    <footer class="text-center pb-8 mt-4 text-[11px] text-slate-500 font-medium tracking-wide z-10">
        &copy; 2026 &bull; Tim DATASCAPE 2026 Polban &bull; Optimized Zero-Latency Engine
    </footer>

    <!-- Interactive JS Script with Zero-Latency Buffer & Outbox Pattern -->
    <script>
        // Setup Axios default headers
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        // Configuration
        const BATCH_SIZE = {{ $batchSize ?? 6 }};
        const COMPETITION_CLASSES = @json($classes);

        // State Machine
        let imageBuffer = [];       // In-memory buffer of pre-decoded images: [{ id, filename, url, preloadedImg }]
        let activeItem = null;      // Currently active image item
        let activeSlot = 'a';       // Double-buffering active element slot ('a' or 'b')
        let isAllocating = false;   // Network allocation lock
        let outboxQueue = [];       // Background submission queue
        let isProcessingOutbox = false;
        let isBufferExhausted = false;

        document.addEventListener('DOMContentLoaded', () => {
            // Initial buffer load
            replenishBuffer(true);
            updateLeaderboard();

            // Periodic Leaderboard & Heartbeat
            setInterval(updateLeaderboard, 5000);
            setInterval(sendLeaseHeartbeat, 40000); // 40s heartbeat to keep leases alive

            // Initialize Example Slideshows
            const containers = document.querySelectorAll('.example-slideshow-container');
            containers.forEach(container => {
                const label = container.getAttribute('data-label');
                const allImages = JSON.parse(container.getAttribute('data-images'));
                
                if (allImages.length <= 4) return;
                let activeImages = allImages.slice(0, 4);
                
                setTimeout(() => {
                    setInterval(() => {
                        const slotIndex = Math.floor(Math.random() * 4);
                        const imgEl = document.getElementById(`example-${label}-slot-${slotIndex}`);
                        if (!imgEl) return;

                        const availableImages = allImages.filter(img => !activeImages.includes(img));
                        if (availableImages.length === 0) return;

                        const newImage = availableImages[Math.floor(Math.random() * availableImages.length)];
                        imgEl.classList.replace('opacity-100', 'opacity-0');

                        setTimeout(() => {
                            imgEl.src = newImage;
                            activeImages[slotIndex] = newImage;
                            imgEl.classList.replace('opacity-0', 'opacity-100');
                        }, 500);
                    }, 3500);
                }, Math.random() * 2000);
            });

            // Dynamic Keyboard Shortcut Bindings
            document.addEventListener('keydown', (e) => {
                if (document.getElementById('complete-screen').classList.contains('hidden') === false) return;
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

                if (e.key === 's' || e.key === 'S') {
                    skipImage();
                    return;
                }

                for (const cls of COMPETITION_CLASSES) {
                    if (cls.keys && cls.keys.includes(e.key)) {
                        submitLabel(cls.id);
                        return;
                    }
                }
            });
        });

        /**
         * Replenish the in-memory client buffer via batch allocation API.
         */
        function replenishBuffer(forceImmediate = false) {
            if (isAllocating) return;
            if (!forceImmediate && imageBuffer.length >= 4) return;

            isAllocating = true;
            if (imageBuffer.length === 0 && !activeItem) {
                showLoader(true);
            }

            const currentIds = imageBuffer.map(item => item.id);
            if (activeItem) currentIds.push(activeItem.id);

            axios.get('{{ route("api.batch-images") }}', {
                params: {
                    count: BATCH_SIZE,
                    exclude_ids: currentIds
                }
            })
            .then(response => {
                const data = response.data;
                isAllocating = false;
                updateStats(data);

                if (data.images && data.images.length > 0) {
                    isBufferExhausted = false;
                    data.images.forEach(imgData => {
                        // Pre-decode bitmap directly in browser memory
                        const preloaded = new Image();
                        preloaded.src = imgData.url;
                        if (preloaded.decode) {
                            preloaded.decode().catch(() => {});
                        }

                        imageBuffer.push({
                            id: imgData.id,
                            filename: imgData.filename,
                            url: imgData.url,
                            imgObj: preloaded
                        });
                    });

                    updateBufferBadge();

                    // If we didn't have an active image displayed, advance immediately
                    if (!activeItem) {
                        advanceToNext();
                    }
                } else if (data.completed && imageBuffer.length === 0 && !activeItem) {
                    showCompleteScreen(true);
                    showLoader(false);
                } else if (imageBuffer.length === 0 && !activeItem) {
                    // Temporarily no images in pool
                    showCompleteScreen(true);
                    showLoader(false);
                }
            })
            .catch(err => {
                console.error('Error replenishing image buffer:', err);
                isAllocating = false;
                showLoader(false);
            });
        }

        /**
         * Switch instantly to the next image in the local buffer (0ms delay).
         */
        function advanceToNext() {
            hideAlert();

            if (imageBuffer.length === 0) {
                activeItem = null;
                showLoader(true);
                replenishBuffer(true);
                return;
            }

            const nextItem = imageBuffer.shift();
            activeItem = nextItem;
            updateBufferBadge();

            // Double buffering switch: Swap image elements without white flash
            const currentSlot = activeSlot;
            const nextSlot = (currentSlot === 'a' ? 'b' : 'a');
            activeSlot = nextSlot;

            const currentEl = document.getElementById(`target-image-${currentSlot}`);
            const nextEl = document.getElementById(`target-image-${nextSlot}`);

            nextEl.src = nextItem.url;
            nextEl.style.opacity = '1';
            currentEl.style.opacity = '0';

            showLoader(false);
            showCompleteScreen(false);

            // Auto-replenish in background if buffer drops below threshold
            if (imageBuffer.length <= 3) {
                replenishBuffer();
            }
        }

        /**
         * Submit label with optimistic 0ms UI advance + background outbox queue.
         */
        function submitLabel(labelValue) {
            if (!activeItem) return;

            const itemToSubmit = activeItem;

            // 1. Immediately advance to next image in buffer (0ms UI latency!)
            advanceToNext();

            // 2. Optimistically increment user contribution counter
            const userStatEl = document.getElementById('stat-user');
            if (userStatEl) {
                const currentVal = parseInt(userStatEl.textContent.replace(/\D/g, '')) || 0;
                userStatEl.textContent = formatNumber(currentVal + 1);
            }

            // 3. Enqueue to background outbox for network resilience
            outboxQueue.push({
                image_id: itemToSubmit.id,
                label: labelValue,
                attempts: 0
            });

            processOutbox();
        }

        /**
         * Process outbox queue asynchronously with auto-retry on network glitches.
         */
        function processOutbox() {
            if (isProcessingOutbox || outboxQueue.length === 0) return;

            isProcessingOutbox = true;
            updateSyncBadge('syncing');

            const item = outboxQueue[0];

            axios.post('{{ route("api.submit-label") }}', {
                image_id: item.image_id,
                label: item.label
            })
            .then(res => {
                outboxQueue.shift(); // Succeeded, remove from queue
                isProcessingOutbox = false;

                if (outboxQueue.length > 0) {
                    processOutbox();
                } else {
                    updateSyncBadge('synced');
                    updateLeaderboard();
                }
            })
            .catch(err => {
                item.attempts++;
                console.warn('Outbox submit warning (will retry):', err);

                if (err.response && err.response.status === 409) {
                    // Conflicted: someone else labeled it
                    outboxQueue.shift();
                    isProcessingOutbox = false;
                    showAlert('Catatan: Gambar ini telah dilabeli oleh anggota tim lain.', 'warning');
                    processOutbox();
                } else if (item.attempts < 4) {
                    // Retry with exponential backoff
                    setTimeout(() => {
                        isProcessingOutbox = false;
                        processOutbox();
                    }, item.attempts * 1200);
                } else {
                    // Give up this single item after 4 retries
                    outboxQueue.shift();
                    isProcessingOutbox = false;
                    showAlert('Koneksi sempat terputus. Label dilewati.', 'danger');
                    processOutbox();
                }
            });
        }

        /**
         * Skip current image and advance to next.
         */
        function skipImage() {
            if (!activeItem) return;
            advanceToNext();
        }

        /**
         * Keep alive active leases while the tab remains open.
         */
        function sendLeaseHeartbeat() {
            const heldIds = [];
            if (activeItem) heldIds.push(activeItem.id);
            imageBuffer.forEach(item => heldIds.push(item.id));

            if (heldIds.length === 0) return;

            axios.post('{{ route("api.heartbeat-lease") }}', {
                image_ids: heldIds
            }).catch(() => {});
        }

        /**
         * Gracefully release un-labeled leases when the user closes or exits the tab.
         */
        function releaseHeldLeases() {
            const heldIds = [];
            if (activeItem) heldIds.push(activeItem.id);
            imageBuffer.forEach(item => heldIds.push(item.id));

            if (heldIds.length === 0) return;

            const payload = JSON.stringify({
                _token: csrfToken,
                nickname: '{{ $nickname }}',
                image_ids: heldIds
            });

            // navigator.sendBeacon is guaranteed by browsers to finish even when page unloads
            if (navigator.sendBeacon) {
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('{{ route("api.release-lease") }}', blob);
            }
        }

        window.addEventListener('beforeunload', releaseHeldLeases);

        /**
         * Update buffer counter badge.
         */
        function updateBufferBadge() {
            const badge = document.getElementById('buffer-text');
            if (badge) {
                badge.textContent = `Buffer: ${imageBuffer.length}`;
            }
        }

        /**
         * Update sync status pill.
         */
        function updateSyncBadge(status) {
            const dot = document.getElementById('sync-dot');
            const text = document.getElementById('sync-text');

            if (status === 'syncing') {
                dot.className = 'w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping';
                text.textContent = `Menyimpan (${outboxQueue.length})...`;
                text.className = 'text-amber-300';
            } else {
                dot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-400';
                text.textContent = 'Tersinkron';
                text.className = 'text-emerald-300';
            }
        }

        function updateStats(data) {
            document.getElementById('stat-left').textContent = formatNumber(data.total_left);
            document.getElementById('stat-total').textContent = formatNumber(data.total_labeled);
            document.getElementById('stat-user').textContent = formatNumber(data.user_labeled);
        }

        function showLoader(show) {
            const loader = document.getElementById('image-loader');
            if (loader) {
                loader.style.opacity = show ? '1' : '0';
                loader.style.pointerEvents = show ? 'auto' : 'none';
            }
        }

        function showCompleteScreen(show) {
            const cs = document.getElementById('complete-screen');
            const controls = document.getElementById('label-controls');
            if (!cs || !controls) return;

            if (show) {
                cs.classList.remove('hidden');
                controls.classList.add('opacity-40', 'pointer-events-none');
            } else {
                cs.classList.add('hidden');
                controls.classList.remove('opacity-40', 'pointer-events-none');
            }
        }

        function updateLeaderboard() {
            axios.get('{{ route("api.leaderboard") }}')
                .then(response => {
                    const leaderboard = response.data;
                    const container = document.getElementById('leaderboard-list');
                    if (!container) return;
                    container.innerHTML = '';

                    if (leaderboard.length === 0) {
                        container.innerHTML = '<div class="py-12 text-center text-slate-500 text-xs font-medium">Belum ada label terkumpul. Jadilah yang pertama!</div>';
                        return;
                    }

                    leaderboard.forEach((user, index) => {
                        let medal = '';
                        let textClass = 'text-slate-300';
                        let rankBg = 'bg-slate-800/40 border border-slate-700/30';

                        if (index === 0) {
                            medal = '🥇';
                            textClass = 'text-yellow-300 font-bold';
                            rankBg = 'bg-yellow-500/10 border border-yellow-500/25';
                        } else if (index === 1) {
                            medal = '🥈';
                            textClass = 'text-slate-100 font-bold';
                            rankBg = 'bg-slate-300/10 border border-slate-300/20';
                        } else if (index === 2) {
                            medal = '🥉';
                            textClass = 'text-amber-500 font-bold';
                            rankBg = 'bg-amber-600/10 border border-amber-600/20';
                        }

                        const userRow = `
                            <div class="flex items-center justify-between p-3.5 ${rankBg} rounded-xl transition-all duration-300 hover:translate-x-1">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 text-center text-sm font-semibold">${medal || (index + 1)}</span>
                                    <span class="text-sm font-medium ${textClass}">${escapeHtml(user.labeled_by)}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-200">${formatNumber(user.total)}</span>
                                    <span class="text-[9px] font-semibold text-slate-500 uppercase">img</span>
                                </div>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', userRow);
                    });
                })
                .catch(error => {
                    console.error('Error fetching leaderboard:', error);
                });
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alert-box');
            const alertMsg = document.getElementById('alert-msg');
            if (!alertBox || !alertMsg) return;

            alertMsg.textContent = message;
            alertBox.classList.remove('hidden', 'bg-red-500/15', 'border-red-500/35', 'text-red-400', 'bg-amber-500/15', 'border-amber-500/35', 'text-amber-400', 'bg-indigo-500/15', 'border-indigo-500/35', 'text-indigo-300');

            if (type === 'danger') {
                alertBox.classList.add('bg-red-500/15', 'border-red-500/35', 'text-red-400');
            } else if (type === 'warning') {
                alertBox.classList.add('bg-amber-500/15', 'border-amber-500/35', 'text-amber-400');
            } else {
                alertBox.classList.add('bg-indigo-500/15', 'border-indigo-500/35', 'text-indigo-300');
            }
        }

        function hideAlert() {
            const alertBox = document.getElementById('alert-box');
            if (alertBox) alertBox.classList.add('hidden');
        }

        function formatNumber(num) {
            return num !== undefined && num !== null ? num.toLocaleString('id-ID') : '-';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Lightbox
        function showLightbox(imgUrl) {
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            img.src = imgUrl;
            modal.classList.remove('hidden');
        }

        function hideLightbox() {
            const modal = document.getElementById('lightbox-modal');
            modal.classList.add('hidden');
        }
    </script>

    <!-- Lightbox Modal Overlay -->
    <div id="lightbox-modal" class="hidden fixed inset-0 bg-slate-950/90 z-[100] flex items-center justify-center p-4 cursor-zoom-out" onclick="hideLightbox()">
        <img id="lightbox-img" src="" class="max-h-[85vh] max-w-full rounded-2xl object-contain shadow-2xl border border-slate-800 animate-fade-in">
    </div>
</body>
</html>
