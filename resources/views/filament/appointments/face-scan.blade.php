<div 
    x-data="{
        video: null,
        isLoading: true,
        isReady: false,
        isScanning: false,
        loadingText: 'Memuat Model AI...',
        message: '',
        messageType: 'info',
        referenceFeatures: @js(json_decode($record->visitor->face_features ?? 'null')),
        
        // Auto-scan & liveness states
        autoScanActive: false,
        livenessStep: 'straight', // 'straight' -> 'right' -> 'passed'
        consecutiveNoFace: 0,
        faceInPlace: false, // Wajah sudah di posisi yang pas

        // Computed outline color for circular border
        get outlineColor() {
            if (!this.isScanning) return 'none';
            if (this.messageType === 'error') return '#ef4444';
            if (this.messageType === 'success') return '#10b981';
            return '#3b82f6';
        },

        async init() {
            this.video = this.$refs.video;
            try {
                if (typeof faceapi === 'undefined') {
                    this.loadingText = 'Mengunduh pustaka AI...';
                    await new Promise((resolve, reject) => {
                        let script = document.createElement('script');
                        script.src = '/js/face-api.min.js?v=' + new Date().getTime();
                        script.onload = resolve;
                        script.onerror = () => reject(new Error('Gagal mengunduh file face-api.min.js dari server.'));
                        document.head.appendChild(script);
                    });
                }

                if (typeof faceapi === 'undefined') {
                    throw new Error('Library face-api gagal dimuat.');
                }

                this.loadingText = 'Memuat Model AI...';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models')
                ]);
                
                this.loadingText = 'Mengakses Kamera...';
                await this.startVideo();
                
                this.isLoading = false;
                this.isReady = true;
                this.message = 'Posisikan wajah Anda di dalam lingkaran.';
                this.messageType = 'info';
                
                this.startAutoScan();
            } catch (error) {
                console.error(error);
                this.loadingText = 'Kamera Gagal Diakses';
                this.message = error.message || 'Kamera tidak dapat diakses atau diblokir browser.';
                this.messageType = 'error';
                this.isLoading = false;
            }
        },

        async startVideo() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Browser memblokir kamera. Gunakan HTTPS atau localhost!');
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } });
                this.video.srcObject = stream;
                return new Promise((resolve) => {
                    this.video.onloadedmetadata = () => { resolve(this.video); };
                });
            } catch (error) {
                throw new Error('Kamera tidak ditemukan atau akses ditolak.');
            }
        },

        startAutoScan() {
            this.autoScanActive = true;
            this.isScanning = true;
            this.scanLoop();
        },

        async scanLoop() {
            if (!this.autoScanActive) return;

            try {
                const detection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    this.consecutiveNoFace++;
                    this.faceInPlace = false;
                    if (this.consecutiveNoFace > 3) {
                        this.message = 'Wajah tidak terdeteksi. Masukkan wajah ke dalam lingkaran.';
                        this.messageType = 'error';
                    }
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }
                
                this.consecutiveNoFace = 0;

                // Distance / Size check
                const box = detection.alignedRect.box;
                const videoWidth = this.video.videoWidth;
                const videoHeight = this.video.videoHeight;
                const ratio = box.width / videoWidth;

                if (ratio < 0.28) {
                    this.faceInPlace = false;
                    this.message = 'Wajah terlalu jauh — maju sedikit.';
                    this.messageType = 'error';
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }
                if (ratio > 0.65) {
                    this.faceInPlace = false;
                    this.message = 'Wajah terlalu dekat — mundur sedikit.';
                    this.messageType = 'error';
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }

                // Center check — face center must be within ±20% of video center
                const faceCenterX = box.x + box.width / 2;
                const faceCenterY = box.y + box.height / 2;
                const videoMidX = videoWidth / 2;
                const videoMidY = videoHeight / 2;
                const toleranceX = videoWidth * 0.20;
                const toleranceY = videoHeight * 0.20;

                if (Math.abs(faceCenterX - videoMidX) > toleranceX || Math.abs(faceCenterY - videoMidY) > toleranceY) {
                    this.faceInPlace = false;
                    const dx = faceCenterX - videoMidX;
                    const dy = faceCenterY - videoMidY;
                    let hint = 'Posisikan wajah di tengah lingkaran';
                    if (Math.abs(dx) > Math.abs(dy)) {
                        hint = dx > 0 ? 'Geser ke kiri ←' : 'Geser ke kanan →';
                    } else {
                        hint = dy > 0 ? 'Geser ke atas ↑' : 'Geser ke bawah ↓';
                    }
                    this.message = hint;
                    this.messageType = 'error';
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }

                // Face is correctly centered!
                this.faceInPlace = true;

                // Liveness Detection (Head Yaw)
                if (this.livenessStep !== 'passed') {
                    const leftEdge = detection.landmarks.positions[0].x;
                    const rightEdge = detection.landmarks.positions[16].x;
                    const noseTip = detection.landmarks.positions[30].x;
                    const noseRatio = (noseTip - leftEdge) / (rightEdge - leftEdge);

                    if (this.livenessStep === 'straight') {
                        this.message = 'Tengok ke kanan ➡';
                        this.messageType = 'info';
                        if (noseRatio < 0.38) {
                            this.livenessStep = 'right';
                        }
                    } else if (this.livenessStep === 'right') {
                        this.message = '⬅ Sekarang tengok ke kiri';
                        this.messageType = 'info';
                        if (noseRatio > 0.62) {
                            this.livenessStep = 'passed';
                            this.message = 'Verifikasi berhasil!';
                            this.messageType = 'success';
                        }
                    }

                    setTimeout(() => this.scanLoop(), 100);
                    return;
                }

                // Liveness Passed, proceed to match!
                this.autoScanActive = false;
                this.processCheckIn(detection);
                
            } catch (error) {
                console.error(error);
                setTimeout(() => this.scanLoop(), 500);
            }
        },

        processCheckIn(detection) {
            if (this.referenceFeatures) {
                const refDescriptor = new Float32Array(this.referenceFeatures);
                const distance = faceapi.euclideanDistance(refDescriptor, detection.descriptor);
                
                if (distance < 0.5) {
                    this.message = 'Wajah Cocok! Menyelesaikan check-in...';
                    this.messageType = 'success';
                    this.stopVideo();
                    this.$wire.callMountedTableAction();
                } else {
                    this.message = 'Wajah tidak cocok dengan tamu terdaftar!';
                    this.messageType = 'error';
                    setTimeout(() => {
                        this.livenessStep = 'straight';
                        this.faceInPlace = false;
                        this.startAutoScan();
                    }, 3000);
                }
            } else {
                this.message = 'Menyimpan profil wajah baru...';
                this.messageType = 'success';
                this.stopVideo();
                const descriptorArray = Array.from(detection.descriptor);
                this.$wire.callMountedTableAction({ face_features: JSON.stringify(descriptorArray) });
            }
        },
        
        stopVideo() {
            this.autoScanActive = false;
            if (this.video && this.video.srcObject) {
                this.video.srcObject.getTracks().forEach(track => track.stop());
            }
        },

        destroy() {
            this.stopVideo();
        }
    }"
    x-init="init()" 
    @destroyed="destroy()"
    class="relative w-full flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-900 rounded-xl"
>
    <style>
        /* Spinning outline animation */
        @keyframes spin-outline {
            0%   { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -628; }
        }
        /* Face grid pulse animation */
        @keyframes face-pulse {
            0%, 100% { opacity: 0.15; }
            50%       { opacity: 0.7; }
        }
        /* Laser scan */
        @keyframes laser-scan {
            0%, 100% { transform: translateY(-50%); opacity: 0; }
            10%, 90%  { opacity: 1; }
            50%        { transform: translateY(50%); }
        }
        /* Arrow bounce */
        @keyframes bounce-right {
            0%, 100% { transform: translateX(0); }
            50%       { transform: translateX(6px); }
        }
        @keyframes bounce-left {
            0%, 100% { transform: translateX(0); }
            50%       { transform: translateX(-6px); }
        }
        .arrow-right { animation: bounce-right 0.8s ease-in-out infinite; }
        .arrow-left  { animation: bounce-left 0.8s ease-in-out infinite; }

        /* Glow on success/error */
        .scan-glow-success { filter: drop-shadow(0 0 12px #10b981); }
        .scan-glow-error   { filter: drop-shadow(0 0 12px #ef4444); }
        .scan-glow-info    { filter: drop-shadow(0 0 12px #3b82f6); }
    </style>

    <!-- Loading Overlay -->
    <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/95 dark:bg-gray-900/95 flex items-center justify-center rounded-xl">
        <div class="flex flex-col items-center text-center p-4">
            <svg class="animate-spin h-10 w-10 text-primary-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="loadingText" class="text-gray-800 dark:text-gray-200 font-bold text-lg"></span>
        </div>
    </div>

    <!-- ── CIRCULAR CAMERA CONTAINER ─────────────────────────────── -->
    <div class="relative flex items-center justify-center"
         style="width: 280px; height: 280px;">

        <!-- The spinning colored SVG ring (sits OUTSIDE the circle crop) -->
        <svg class="absolute inset-0 w-full h-full z-30 pointer-events-none"
             viewBox="0 0 280 280"
             :class="messageType === 'success' ? 'scan-glow-success' : (messageType === 'error' ? 'scan-glow-error' : 'scan-glow-info')">
            <circle
                cx="140" cy="140" r="130"
                fill="none"
                stroke-width="5"
                stroke-linecap="round"
                :stroke="outlineColor"
                stroke-dasharray="120 694"
                style="animation: spin-outline 1.6s linear infinite; transform-origin: center;"
                x-show="isScanning"
            />
            <!-- Static thin base ring -->
            <circle
                cx="140" cy="140" r="130"
                fill="none"
                stroke-width="2"
                :stroke="messageType === 'error' ? 'rgba(239,68,68,0.25)' : (messageType === 'success' ? 'rgba(16,185,129,0.25)' : 'rgba(59,130,246,0.25)')"
            />
        </svg>

        <!-- Circular video crop -->
        <div class="relative rounded-full overflow-hidden bg-black z-10"
             style="width: 260px; height: 260px;">
            <video x-ref="video" autoplay muted playsinline
                   class="w-full h-full object-cover"
                   style="transform: scaleX(-1);"></video>

            <!-- Dark overlay + face grid SVG (fades out when face is in place) -->
            <div class="absolute inset-0 pointer-events-none z-20 transition-opacity duration-500"
                 :style="faceInPlace ? 'opacity:0' : 'opacity:1'">
                <svg class="w-full h-full" viewBox="0 0 260 260">
                    <defs>
                        <!-- Face silhouette clip -->
                        <clipPath id="face-circ-clip">
                            <circle cx="130" cy="130" r="130"/>
                        </clipPath>
                        <!-- Face grid shape: tall oval portrait -->
                        <path id="face-grid-shape"
                              d="M 130,28
                                 C 165,28 196,55 196,100
                                 C 208,100 208,126 196,128
                                 C 188,162 158,195 130,200
                                 C 102,195 72,162 64,128
                                 C 52,126 52,100 64,100
                                 C 64,55 95,28 130,28 Z"/>
                        <mask id="face-grid-mask">
                            <rect width="260" height="260" fill="white"/>
                            <use href="#face-grid-shape" fill="black"/>
                        </mask>
                    </defs>

                    <!-- Darkened backdrop (outside the face shape) -->
                    <rect width="260" height="260" fill="rgba(0,0,0,0.62)" mask="url(#face-grid-mask)" clip-path="url(#face-circ-clip)"/>

                    <!-- Pulsing face silhouette border (grid effect) -->
                    <use href="#face-grid-shape"
                         fill="none"
                         stroke-width="2.5"
                         stroke-dasharray="10 6"
                         style="animation: face-pulse 1.4s ease-in-out infinite;"
                         :stroke="messageType === 'error' ? '#ef4444' : '#60a5fa'"/>

                    <!-- Scanning laser inside face -->
                    <g clip-path="url(#face-circ-clip)" x-show="isScanning && !faceInPlace">
                        <rect x="30" y="130" width="200" height="2"
                              fill="#10b981"
                              style="animation: laser-scan 2.2s ease-in-out infinite; transform-box: fill-box; transform-origin: center;"/>
                        <rect x="30" y="118" width="200" height="14"
                              fill="url(#laser-grad)"
                              style="animation: laser-scan 2.2s ease-in-out infinite; transform-box: fill-box; transform-origin: center;"/>
                        <defs>
                            <linearGradient id="laser-grad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.45"/>
                            </linearGradient>
                        </defs>
                    </g>
                </svg>
            </div>

            <!-- SUCCESS ring flash (when faceInPlace) -->
            <div class="absolute inset-0 rounded-full pointer-events-none z-20 transition-all duration-500"
                 :style="faceInPlace ? 'box-shadow: inset 0 0 0 3px #10b981;' : ''">
            </div>
        </div>
    </div>

    <!-- ── DIRECTIONAL ARROW INSTRUCTIONS ─────────────────────────── -->
    <div class="mt-5 flex items-center justify-center gap-4 min-h-[56px]">

        <!-- Arrow RIGHT (when step = straight) -->
        <div x-show="isReady && livenessStep === 'straight' && faceInPlace"
             x-transition
             class="flex flex-col items-center gap-1 arrow-right">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            <span class="text-xs font-semibold text-blue-400">Tengok Kanan</span>
        </div>

        <!-- Status message (center) -->
        <div x-show="message" class="flex-1 text-center">
            <span x-text="message"
                  class="inline-block px-4 py-2 rounded-full text-sm font-semibold text-white shadow-lg transition-all duration-300"
                  :class="messageType === 'success' ? 'bg-emerald-500' : (messageType === 'error' ? 'bg-red-500' : 'bg-blue-500')">
            </span>
        </div>

        <!-- Arrow LEFT (when step = right) -->
        <div x-show="isReady && livenessStep === 'right'"
             x-transition
             class="flex flex-col items-center gap-1 arrow-left">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            <span class="text-xs font-semibold text-blue-400">Tengok Kiri</span>
        </div>

    </div>

    <!-- ── BOTTOM INFO BOX ─────────────────────────────────────────── -->
    <div class="mt-3 w-full max-w-xs">
        <p x-show="!isReady && !isLoading" class="text-sm text-red-500 font-medium text-center">
            Tidak dapat melanjutkan tanpa akses kamera.
        </p>
        <div x-show="isReady && livenessStep !== 'passed'"
             class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Masukkan wajah ke lingkaran → tengok kanan → tengok kiri → check-in otomatis.
            </p>
        </div>
        <div x-show="livenessStep === 'passed'"
             class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3 border border-emerald-200 dark:border-emerald-800 text-center">
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                Liveness terverifikasi. Memproses pengenalan wajah...
            </p>
        </div>
    </div>
</div>
