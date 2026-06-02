<div 
    x-data="{
        video: null,
        isLoading: true,
        isReady: false,
        loadingText: 'Memuat Model AI...',
        message: '',
        messageType: 'info',
        referenceFeatures: @php
            $rf = json_decode($record->visitor->face_features ?? 'null', true);
            if (is_array($rf) && isset($rf[0])) {
                if (is_string($rf[0])) { $rf = array_map(fn($s) => json_decode($s, true) ?? [], $rf); }
                if (!is_array($rf[0])) { $rf = [$rf]; } // flat array → wrap
            }
            echo \Illuminate\Support\Js::from($rf);
        @endphp,
        visitorId: @js($record->visitor->id ?? null),

        autoScanActive: false,
        livenessStep: 'straight',
        consecutiveNoFace: 0,
        faceInPlace: false,
        photoSnapshot: null,    // Foto diambil saat wajah lurus & pas, sebelum liveness
        preparingPhoto: false,  // Flag agar capture hanya sekali

        get ringColor() {
            if (this.messageType === 'error')   return '#ef4444';
            if (this.messageType === 'success') return '#10b981';
            return '#6366f1';
        },
        get ringColorAlpha() { return this.ringColor + '33'; },

        async init() {
            this.video = this.$refs.video;
            try {
                if (typeof faceapi === 'undefined') {
                    this.loadingText = 'Mengunduh pustaka AI...';
                    await new Promise((resolve, reject) => {
                        let s = document.createElement('script');
                        s.src = '/js/face-api.min.js?v=' + Date.now();
                        s.onload = resolve;
                        s.onerror = () => reject(new Error('Gagal mengunduh face-api.min.js'));
                        document.head.appendChild(s);
                    });
                }
                if (typeof faceapi === 'undefined') throw new Error('Library face-api gagal dimuat.');
                this.loadingText = 'Memuat Model AI...';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                ]);
                this.loadingText = 'Mengakses Kamera...';
                await this.startVideo();
                this.isLoading = false;
                this.isReady = true;
                this.setMsg('Posisikan wajah di dalam lingkaran.', 'info');
                this.startAutoScan();
            } catch (error) {
                console.error(error);
                this.loadingText = 'Kamera Gagal Diakses';
                this.setMsg(error.message || 'Kamera tidak dapat diakses.', 'error');
                this.isLoading = false;
            }
        },

        async startVideo() {
            if (!navigator.mediaDevices?.getUserMedia)
                throw new Error('Browser memblokir kamera. Gunakan HTTPS atau localhost!');
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
            }).catch(() => { throw new Error('Kamera tidak ditemukan atau akses ditolak.'); });
            this.video.srcObject = stream;
            return new Promise(resolve => { this.video.onloadedmetadata = () => { this.video.play(); resolve(); }; });
        },

        setMsg(text, type) {
            this.message = text;
            this.messageType = type;
            /* Sync ke elemen statik (agar animasi CSS tetap jalan) */
            const el = this.$refs.msgBadge;
            if (!el) return;
            el.textContent = text;
            el.style.background = type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#6366f1';
        },

        setGrid(hide) {
            const g = this.$refs.faceGrid;
            if (g) g.style.opacity = hide ? '0' : '1';
        },

        setRing(type) {
            const c = this.ringColor;
            const arc  = this.$refs.ringArc;
            const base = this.$refs.ringBase;
            if (arc)  arc.setAttribute('stroke', c);
            if (base) base.setAttribute('stroke', c + '33');
        },

        showArrow(dir) {
            const r = this.$refs.arrowRight;
            const l = this.$refs.arrowLeft;
            if (r) r.style.display = dir === 'right' ? 'flex' : 'none';
            if (l) l.style.display = dir === 'left'  ? 'flex' : 'none';
        },

        startAutoScan() { this.autoScanActive = true; this.scanLoop(); },

        async scanLoop() {
            if (!this.autoScanActive) return;
            try {
                const det = await faceapi
                    .detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks().withFaceDescriptor();

                if (!det) {
                    this.consecutiveNoFace++;
                    this.faceInPlace = false;
                    this.setGrid(false);
                    this.setRing();
                    if (this.consecutiveNoFace > 3)
                        this.setMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => this.scanLoop(), 200); return;
                }
                this.consecutiveNoFace = 0;

                const box   = det.alignedRect.box;
                const ratio = box.width / this.video.videoWidth;
                const offX  = Math.abs((box.x + box.width/2)  - this.video.videoWidth/2)  / this.video.videoWidth;
                const offY  = Math.abs((box.y + box.height/2) - this.video.videoHeight/2) / this.video.videoHeight;

                if (ratio < 0.28) {
                    this.faceInPlace = false; this.setGrid(false);
                    this.setMsg('Wajah terlalu jauh — maju sedikit.', 'error');
                    this.setRing(); setTimeout(() => this.scanLoop(), 200); return;
                }
                if (ratio > 0.65) {
                    this.faceInPlace = false; this.setGrid(false);
                    this.setMsg('Wajah terlalu dekat — mundur sedikit.', 'error');
                    this.setRing(); setTimeout(() => this.scanLoop(), 200); return;
                }
                if (offX > 0.20 || offY > 0.20) {
                    this.faceInPlace = false; this.setGrid(false); this.setRing();
                    const dx = (box.x + box.width/2) - this.video.videoWidth/2;
                    const dy = (box.y + box.height/2) - this.video.videoHeight/2;
                    let hint = 'Posisikan wajah di tengah lingkaran';
                    if (Math.abs(dx) > Math.abs(dy)) hint = dx > 0 ? 'Geser ke kiri ←' : 'Geser ke kanan →';
                    else hint = dy > 0 ? 'Geser ke atas ↑' : 'Geser ke bawah ↓';
                    this.setMsg(hint, 'error'); setTimeout(() => this.scanLoop(), 200); return;
                }

                /* Wajah pas di tengah */
                this.faceInPlace = true;
                this.setGrid(true);
                this.messageType = 'success'; this.setRing();

                if (this.livenessStep !== 'passed') {
                    const pts = det.landmarks.positions;
                    const nr  = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);
                    if (this.livenessStep === 'straight') {
                        if (!this.photoSnapshot) {
                            /* Belum ada foto: tampilkan pesan diam, ambil foto diam-diam */
                            if (!this.preparingPhoto) {
                                this.preparingPhoto = true;
                                this.setMsg('Diam sebentar...', 'info');
                                this.messageType = 'info'; this.setRing();
                                setTimeout(() => {
                                    this.photoSnapshot = this.capturePhoto();
                                    this.preparingPhoto = false;
                                }, 800);
                            }
                            setTimeout(() => this.scanLoop(), 100); return;
                        }
                        this.setMsg('Tengok ke kanan ➡', 'info');
                        this.messageType = 'info'; this.setRing();
                        this.showArrow('right');
                        if (nr < 0.38) this.livenessStep = 'right';
                    } else if (this.livenessStep === 'right') {
                        this.setMsg('⬅ Sekarang tengok ke kiri', 'info');
                        this.messageType = 'info'; this.setRing();
                        this.showArrow('left');
                        if (nr > 0.62) {
                            this.livenessStep = 'passed';
                            this.setMsg('Verifikasi berhasil! Memproses...', 'success');
                            this.messageType = 'success'; this.setRing();
                            this.showArrow('none');
                            this.autoScanActive = false;
                            this.processResult(det); return;
                        }
                    }
                    setTimeout(() => this.scanLoop(), 100); return;
                }
            } catch(e) { console.error(e); setTimeout(() => this.scanLoop(), 500); }
        },

        /* Ambil snapshot frame dari video sebagai JPEG base64 */
        capturePhoto() {
            try {
                const canvas = document.createElement('canvas');
                canvas.width  = this.video.videoWidth  || 320;
                canvas.height = this.video.videoHeight || 240;
                const ctx = canvas.getContext('2d');
                // Mirror balik (karena video di-flip CSS)
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(this.video, 0, 0);
                return canvas.toDataURL('image/jpeg', 0.80);
            } catch(e) {
                console.warn('capturePhoto failed:', e);
                return null;
            }
        },

        processResult(detection) {
            if (this.referenceFeatures) {
                /* ── MODE VERIFIKASI: cocokkan dengan data tersimpan (bisa array of arrays) ── */
                let minDist = Infinity;
                if (Array.isArray(this.referenceFeatures[0])) {
                    for (let refArr of this.referenceFeatures) {
                        const ref  = new Float32Array(refArr);
                        const dist = faceapi.euclideanDistance(ref, detection.descriptor);
                        if (dist < minDist) minDist = dist;
                    }
                } else {
                    const ref  = new Float32Array(this.referenceFeatures);
                    minDist = faceapi.euclideanDistance(ref, detection.descriptor);
                }

                if (minDist < 0.5) {
                    this.setMsg('Wajah Cocok! Menyelesaikan check-in...', 'success');
                    this.stopVideo();
                    this.$wire.callMountedTableAction();
                } else {
                    this.setMsg('Wajah tidak cocok! (' + minDist.toFixed(2) + ')', 'error');
                    setTimeout(() => {
                        this.livenessStep = 'straight'; this.faceInPlace = false;
                        this.setGrid(false); this.showArrow('none');
                        this.startAutoScan();
                    }, 3000);
                }
            } else {
                /* ── MODE REGISTRASI: ambil foto → cek duplikat → simpan ── */
                const facePhoto    = this.photoSnapshot || this.capturePhoto(); // foto wajah lurus
                const descriptorArr = Array.from(detection.descriptor);
                const csrfToken    = document.querySelector('meta[name=csrf-token]')?.content || '';

                this.setMsg('Memeriksa duplikasi wajah...', 'info');
                this.messageType = 'info'; this.setRing();

                fetch('/kiosk/face-check-duplicate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ descriptor: descriptorArr, visitor_id: this.visitorId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.is_duplicate) {
                        /* Wajah sudah milik orang lain → tolak */
                        this.setMsg('❌ ' + data.message, 'error');
                        this.messageType = 'error'; this.setRing();
                        this.faceInPlace = false; this.setGrid(false);
                        setTimeout(() => {
                            this.livenessStep = 'straight'; this.faceInPlace = false;
                            this.setGrid(false); this.showArrow('none');
                            this.setMsg('Posisikan wajah di dalam lingkaran.', 'info');
                            this.startAutoScan();
                        }, 4000);
                    } else {
                        /* Wajah unik → simpan descriptor + foto terenkripsi */
                        this.setMsg('Wajah unik! Menyimpan...', 'success');
                        this.messageType = 'success'; this.setRing();
                        this.stopVideo();
                        this.$wire.callMountedTableAction({
                            face_features: descriptorArr,
                            face_photo: facePhoto,      // base64 JPEG — akan dienkripsi server-side
                        });
                    }
                })
                .catch(() => {
                    this.setMsg('Koneksi gagal. Coba lagi.', 'error');
                    setTimeout(() => { this.livenessStep='straight'; this.faceInPlace=false; this.setGrid(false); this.showArrow('none'); this.startAutoScan(); }, 3000);
                });
            }
        },

        stopVideo() {
            this.autoScanActive = false;
            if (this.video?.srcObject)
                this.video.srcObject.getTracks().forEach(t => t.stop());
        },

        destroy() { this.stopVideo(); }
    }"
    x-init="init()"
    @destroyed="destroy()"
    class="flex flex-col items-center gap-3 pb-2"
>
    <style>
        @keyframes fs-spin-ring { to { stroke-dashoffset: -692; } }
        @keyframes fs-pulse     { 0%,100%{opacity:0.15;} 50%{opacity:0.75;} }
        @keyframes fs-bounce-r  { 0%,100%{transform:translateX(0);} 50%{transform:translateX(6px);} }
        @keyframes fs-bounce-l  { 0%,100%{transform:translateX(0);} 50%{transform:translateX(-6px);} }
        @keyframes fs-spin      { to { transform:rotate(360deg); } }
    </style>

    <!-- Loading state -->
    <div x-show="isLoading" class="flex flex-col items-center justify-center gap-3 py-16">
        <svg style="width:2.8rem;height:2.8rem;animation:fs-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="#6366f1" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/>
        </svg>
        <span x-text="loadingText" class="text-sm font-medium text-gray-500 dark:text-gray-400"></span>
    </div>

    <!-- Camera section -->
    <div x-show="!isLoading" class="flex flex-col items-center gap-3 w-full">

        <!-- Circular camera frame 272×272 -->
        <div style="position:relative;width:272px;height:272px;flex-shrink:0;">

            <!-- Spinning ring -->
            <svg style="position:absolute;inset:0;width:100%;height:100%;z-index:4;pointer-events:none;"
                 viewBox="0 0 272 272">
                <circle x-ref="ringBase"
                        cx="136" cy="136" r="126"
                        fill="none" stroke-width="3"
                        :stroke="ringColorAlpha"/>
                <circle x-ref="ringArc"
                        cx="136" cy="136" r="126"
                        fill="none" stroke-width="5" stroke-linecap="round"
                        :stroke="ringColor"
                        stroke-dasharray="110 692"
                        style="animation:fs-spin-ring 1.6s linear infinite;transform-origin:center;"/>
            </svg>

            <!-- Circular crop -->
            <div style="position:absolute;inset:8px;border-radius:50%;overflow:hidden;background:#111;z-index:2;">
                <video x-ref="video" autoplay muted playsinline
                       style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block;">
                </video>

                <!-- Face grid overlay (blinking dashed face silhouette, fades when face is in place) -->
                <div x-ref="faceGrid"
                     style="position:absolute;inset:0;pointer-events:none;transition:opacity 0.5s;opacity:1;">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         style="width:100%;height:100%;display:block;"
                         viewBox="0 0 256 256"
                         preserveAspectRatio="xMidYMid slice">
                        <defs>
                            <path id="fs-face-shape"
                                  d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                            <mask id="fs-face-mask">
                                <rect width="256" height="256" fill="white"/>
                                <use href="#fs-face-shape" fill="black"/>
                            </mask>
                        </defs>
                        <!-- Dark backdrop outside face area -->
                        <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#fs-face-mask)"/>
                        <!-- Pulsing dashed face border -->
                        <use href="#fs-face-shape" fill="none"
                             :stroke="messageType === 'error' ? '#ef4444' : '#818cf8'"
                             stroke-width="2.5"
                             stroke-dasharray="10 6"
                             style="animation:fs-pulse 1.4s ease-in-out infinite;"/>
                    </svg>
                </div>

                <!-- Inner success ring (green glow when face in place) -->
                <div style="position:absolute;inset:0;border-radius:50%;pointer-events:none;transition:box-shadow 0.4s;"
                     :style="faceInPlace ? 'box-shadow:inset 0 0 0 3px #10b981' : ''">
                </div>
            </div>
        </div>

        <!-- ── STATUS MESSAGE (OUTSIDE the circle) ── -->
        <div style="min-height:36px;display:flex;align-items:center;justify-content:center;padding:0 0.5rem;">
            <span x-ref="msgBadge"
                  x-show="message"
                  style="display:inline-block;padding:0.4rem 1.1rem;border-radius:999px;
                         font-size:0.78rem;font-weight:600;color:#fff;
                         text-align:center;max-width:260px;transition:background 0.3s;
                         background:#6366f1;">
            </span>
        </div>

        <!-- ── DIRECTIONAL ARROWS (OUTSIDE the circle) ── -->
        <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;min-height:52px;">
            <!-- Arrow Right -->
            <div x-ref="arrowRight"
                 style="display:none;flex-direction:column;align-items:center;gap:4px;
                        animation:fs-bounce-r 0.8s ease-in-out infinite;">
                <svg style="width:2rem;height:2rem;color:#818cf8;"
                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KANAN</span>
            </div>

            <!-- Arrow Left -->
            <div x-ref="arrowLeft"
                 style="display:none;flex-direction:column;align-items:center;gap:4px;
                        animation:fs-bounce-l 0.8s ease-in-out infinite;">
                <svg style="width:2rem;height:2rem;color:#818cf8;"
                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KIRI</span>
            </div>
        </div>

        <!-- ── Info hint ── -->
        <div x-show="isReady && livenessStep !== 'passed'"
             class="w-full rounded-lg px-4 py-2.5 text-center text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <template x-if="!referenceFeatures">
                <span>Posisikan wajah → tengok kanan → kiri → wajah tersimpan &amp; check-in otomatis.</span>
            </template>
            <template x-if="referenceFeatures">
                <span>Posisikan wajah → tengok kanan → kiri → check-in otomatis.</span>
            </template>
        </div>

        <div x-show="livenessStep === 'passed'"
             class="w-full rounded-lg px-4 py-2.5 text-center text-xs font-medium
                    text-emerald-600 dark:text-emerald-400
                    border border-emerald-200 dark:border-emerald-800
                    bg-emerald-50 dark:bg-emerald-900/20">
            Liveness terverifikasi. Memproses...
        </div>
    </div>
</div>
