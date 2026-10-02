{{--
    Lightbox gambar bersama (dipakai halaman labeler & admin).

    Catatan:
    - Discok partial ini di <body>. Setelah include, halaman pemanggil cukup
      memanggil showLightbox(this.src) dari thumbnail mana pun.
    - Support zoom (scroll wheel / tombol + -) dan pan (drag), karena untuk
      crosscheck gambar perlu melihat detail kecil yang tidak terbaca di thumbnail.
--}}
<div id="lightbox-modal"
     class="hidden fixed inset-0 bg-slate-950/95 z-[100] flex items-center justify-center p-4 select-none"
     onclick="hideLightbox()">

    <!-- Toolbar -->
    <div class="absolute top-4 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5 bg-slate-900/90 border border-slate-800 rounded-2xl px-2 py-1.5 shadow-xl backdrop-blur"
         onclick="event.stopPropagation()">
        <button type="button" onclick="zoomLightbox(-0.25)"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-300 font-bold text-lg leading-none"
                title="Perkecil (-)">&minus;</button>

        <span id="lightbox-zoom-label"
              class="text-[11px] font-semibold text-slate-400 w-14 text-center tabular-nums">100%</span>

        <button type="button" onclick="zoomLightbox(0.25)"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-300 font-bold text-lg leading-none"
                title="Perbesar (+)">+</button>

        <span class="w-px h-5 bg-slate-800 mx-1"></span>

        <button type="button" onclick="resetLightboxZoom()"
                class="px-2.5 h-8 rounded-lg hover:bg-slate-800 text-slate-400 text-[11px] font-semibold"
                title="Kembalikan ke ukuran awal (0)">Reset</button>

        <button type="button" onclick="hideLightbox()"
                class="px-2.5 h-8 rounded-lg hover:bg-red-500/20 text-slate-400 hover:text-red-300 text-[11px] font-semibold"
                title="Tutup (Esc)">Tutup</button>
    </div>

    <!-- Nama file -->
    <div id="lightbox-caption"
         class="hidden absolute bottom-4 left-1/2 -translate-x-1/2 z-10 max-w-[90vw] truncate bg-slate-900/90 border border-slate-800 rounded-xl px-3 py-1.5 text-[11px] font-mono text-slate-400"
         onclick="event.stopPropagation()"></div>

    <!-- Area gambar: drag untuk menggeser -->
    <div id="lightbox-stage"
         class="w-full h-full flex items-center justify-center overflow-hidden"
         onclick="event.stopPropagation()">
        <img id="lightbox-img"
             src=""
             alt=""
             draggable="false"
             class="max-h-[82vh] max-w-[92vw] rounded-2xl object-contain shadow-2xl border border-slate-800 animate-fade-in select-none">
    </div>
</div>

<script>
    (function () {
        // State lightbox. Sengaja ditempel ke window supaya bisa dipakai
        // showLightbox() yang dipanggil dari inline handler pada thumbnail.
        window.__lightboxZoom = 1;
        window.__lightboxPanX = 0;
        window.__lightboxPanY = 0;

        const MIN_ZOOM = 0.25;
        const MAX_ZOOM = 6;

        function lbEls() {
            return {
                modal: document.getElementById('lightbox-modal'),
                img: document.getElementById('lightbox-img'),
                label: document.getElementById('lightbox-zoom-label'),
                caption: document.getElementById('lightbox-caption'),
                stage: document.getElementById('lightbox-stage')
            };
        }

        function lbApply() {
            const { img, label } = lbEls();
            if (!img) return;

            const z = window.__lightboxZoom;
            img.style.transformOrigin = 'center center';
            img.style.transform =
                'translate(' + window.__lightboxPanX + 'px, ' + window.__lightboxPanY + 'px) scale(' + z + ')';
            img.style.cursor = z > 1 ? 'grab' : 'zoom-in';

            if (label) label.textContent = Math.round(z * 100) + '%';
        }

        window.zoomLightbox = function (delta) {
            const next = window.__lightboxZoom + delta;
            window.__lightboxZoom = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, next));

            // Sudah kembali ke 100% atau kurang: reset pan supaya gambar
            // tidak terlihat "nyangkut" di offset.
            if (window.__lightboxZoom <= 1) {
                window.__lightboxPanX = 0;
                window.__lightboxPanY = 0;
            }

            lbApply();
        };

        window.resetLightboxZoom = function () {
            window.__lightboxZoom = 1;
            window.__lightboxPanX = 0;
            window.__lightboxPanY = 0;
            lbApply();
        };

        window.showLightbox = function (imgUrl, filename) {
            const { modal, img, caption } = lbEls();
            if (!modal || !img) return;

            img.src = imgUrl;
            img.alt = filename || 'Pratinjau gambar';

            if (caption) {
                caption.textContent = filename || '';
                caption.classList.toggle('hidden', !filename);
            }

            window.resetLightboxZoom();
            modal.classList.remove('hidden');

            // Fokus ke modal supaya tombol Escape langsung bekerja tanpa
            // harus klik area kosong lebih dulu.
            modal.setAttribute('tabindex', '-1');
            modal.focus({ preventScroll: true });
        };

        window.hideLightbox = function () {
            const { modal } = lbEls();
            if (modal) modal.classList.add('hidden');
        };

        // Kontrol dipasang setelah DOM siap.
        document.addEventListener('DOMContentLoaded', function () {
            const { img, stage, modal } = lbEls();
            if (!img || !stage) return;

            // Scroll untuk zoom in/out.
            stage.addEventListener('wheel', function (e) {
                if (modal.classList.contains('hidden')) return;
                e.preventDefault();
                window.zoomLightbox(e.deltaY < 0 ? 0.2 : -0.2);
            }, { passive: false });

            // Klik gambar untuk zoom, sedangkan area kosong di sekitarnya
            // tetap menutup lewat handler onclick pada modal.
            img.addEventListener('click', function () {
                if (window.__lightboxZoom < 1.5) {
                    window.zoomLightbox(0.5);
                } else {
                    window.resetLightboxZoom();
                }
            });

            // Drag untuk menggeser gambar, hanya aktif saat sedang di-zoom.
            let dragging = false;
            let startX = 0;
            let startY = 0;
            let originX = 0;
            let originY = 0;

            img.addEventListener('mousedown', function (e) {
                if (window.__lightboxZoom <= 1) return;
                dragging = true;
                startX = e.clientX;
                startY = e.clientY;
                originX = window.__lightboxPanX;
                originY = window.__lightboxPanY;
                img.style.cursor = 'grabbing';
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!dragging) return;
                window.__lightboxPanX = originX + (e.clientX - startX);
                window.__lightboxPanY = originY + (e.clientY - startY);
                lbApply();
            });

            window.addEventListener('mouseup', function () {
                if (!dragging) return;
                dragging = false;
                lbApply();
            });

            // Keyboard: Esc tutup, + / - zoom, 0 reset.
            document.addEventListener('keydown', function (e) {
                if (modal.classList.contains('hidden')) return;

                if (e.key === 'Escape') {
                    window.hideLightbox();
                } else if (e.key === '+' || e.key === '=') {
                    window.zoomLightbox(0.25);
                } else if (e.key === '-' || e.key === '_') {
                    window.zoomLightbox(-0.25);
                } else if (e.key === '0') {
                    window.resetLightboxZoom();
                } else {
                    return;
                }

                // Mencegah "+" ikut mengetik ke input filter yang ada di belakang.
                e.preventDefault();
            });
        });
    })();
</script>