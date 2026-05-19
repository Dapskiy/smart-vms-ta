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
        
        // Auto-scan states
        autoScanActive: false,
        livenessStep: 'straight', // 'straight' -> 'right' -> 'passed'
        consecutiveNoFace: 0,

        async init() {
            this.video = this.$refs.video;
            try {
                // Dynamically load face-api script if not present (Fix for Livewire modals)
                if (typeof faceapi === 'undefined') {
                    this.loadingText = 'Mengunduh pustaka AI...';
                    await new Promise((resolve, reject) => {
                        let script = document.createElement('script');
                        script.src = '/js/face-api.min.js?v=' + new Date().getTime(); // Bypass browser cache
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
                this.message = 'Kamera siap. Mulai pemindaian otomatis...';
                this.messageType = 'info';
                
                // Mulai auto-scan
                this.startAutoScan();
            } catch (error) {
                console.error(error);
                this.loadingText = 'Kamera Gagal Diakses';
                this.message = error.message || 'Kamera tidak dapat diakses atau diblokir browser.';
                this.messageType = 'error';
                // Stop loading state so error is visible on the video overlay
                this.isLoading = false;
            }
        },

        async startVideo() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Browser memblokir kamera. Gunakan HTTPS atau localhost!');
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                this.video.srcObject = stream;
                return new Promise((resolve) => {
                    this.video.onloadedmetadata = () => {
                        resolve(this.video);
                    };
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
                    if (this.consecutiveNoFace > 3) {
                        this.message = 'Wajah tidak terdeteksi. Posisikan wajah di dalam bingkai.';
                        this.messageType = 'error';
                    }
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }
                
                this.consecutiveNoFace = 0;

                // Distance Check
                const boxWidth = detection.alignedRect.box.width;
                const videoWidth = this.video.videoWidth;
                const ratio = boxWidth / videoWidth;

                if (ratio < 0.28) {
                    this.message = 'Wajah terlalu jauh. Silakan maju sedikit.';
                    this.messageType = 'error';
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }
                if (ratio > 0.65) {
                    this.message = 'Wajah terlalu dekat. Silakan mundur sedikit.';
                    this.messageType = 'error';
                    setTimeout(() => this.scanLoop(), 200);
                    return;
                }

                // Liveness Detection (Head Yaw / Nengok)
                if (this.livenessStep !== 'passed') {
                    const leftEdge = detection.landmarks.positions[0].x;
                    const rightEdge = detection.landmarks.positions[16].x;
                    const noseTip = detection.landmarks.positions[30].x;
                    
                    // Ratio hidung: 0.5 = tengah. < 0.4 = nengok kanan. > 0.6 = nengok kiri.
                    const noseRatio = (noseTip - leftEdge) / (rightEdge - leftEdge);

                    if (this.livenessStep === 'straight') {
                        this.message = 'Jarak pas! Silakan TENGOK KE KANAN perlahan.';
                        this.messageType = 'info';
                        if (noseRatio < 0.38) {
                            this.livenessStep = 'right';
                        }
                    } else if (this.livenessStep === 'right') {
                        this.message = 'Bagus! Sekarang TENGOK KE KIRI perlahan.';
                        this.messageType = 'success';
                        if (noseRatio > 0.62) {
                            this.livenessStep = 'passed';
                            this.message = 'Verifikasi berhasil! Memproses wajah...';
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
                setTimeout(() => this.scanLoop(), 500); // retry on error
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
                    this.message = 'Wajah tidak cocok dengan tamu terdaftar! (' + distance.toFixed(2) + ')';
                    this.messageType = 'error';
                    
                    // Restart auto scan after 3 seconds
                    setTimeout(() => {
                        this.livenessStep = 'straight';
                        this.startAutoScan();
                    }, 3000);
                }
            } else {
                this.message = 'Menyimpan profil wajah...';
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
    <!-- Loading Overlay (Added z-50 to ensure it covers the video) -->
    <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/95 dark:bg-gray-900/95 flex items-center justify-center rounded-xl">
        <div class="flex flex-col items-center text-center p-4">
            <svg class="animate-spin h-10 w-10 text-primary-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="loadingText" class="text-gray-800 dark:text-gray-200 font-bold text-lg"></span>
        </div>
    </div>
    
    <div class="relative rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-gray-800 w-full max-w-sm aspect-[3/4] bg-black shadow-inner z-10 mx-auto">
        <video x-ref="video" autoplay muted playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
        
        <!-- Mask Overlay (SVG Face Shape) -->
        <div class="absolute inset-0 pointer-events-none z-20">
            <svg class="w-full h-full" viewBox="0 0 100 133" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <!-- Custom Face Silhouette Path -->
                    <path id="face-shape" d="M 50,20 C 70,20 85,35 85,60 C 93,60 93,75 85,75 C 80,100 60,115 50,120 C 40,115 20,100 15,75 C 7,75 7,60 15,60 C 15,35 30,20 50,20 Z" />
                    
                    <mask id="face-mask">
                        <rect width="100%" height="100%" fill="white" />
                        <use href="#face-shape" fill="black" />
                    </mask>
                    
                    <clipPath id="face-clip">
                        <use href="#face-shape" />
                    </clipPath>
                </defs>
                
                <!-- Darkened Background -->
                <rect width="100%" height="100%" fill="rgba(0,0,0,0.65)" mask="url(#face-mask)" />
                
                <!-- Face Border -->
                <use href="#face-shape" 
                     fill="transparent" 
                     stroke-width="1.5" 
                     stroke-dasharray="6" 
                     class="transition-all duration-300"
                     :stroke="isScanning ? '#10b981' : 'rgba(255,255,255,0.8)'" />
                     
                <!-- Scanning Laser -->
                <g x-show="isScanning" clip-path="url(#face-clip)" style="color: #10b981;">
                    <rect x="0" y="20" width="100" height="1.5" fill="currentColor" style="animation: svgScan 2s ease-in-out infinite; box-shadow: 0 0 10px #10b981;" />
                    <rect x="0" y="10" width="100" height="10" fill="url(#laser-gradient)" style="animation: svgScan 2s ease-in-out infinite;" />
                </g>
                <defs>
                    <linearGradient id="laser-gradient" x1="0" y1="1" x2="0" y2="0">
                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.5" />
                        <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                    </linearGradient>
                </defs>
            </svg>
            
            <style>
                @keyframes svgScan {
                    0%, 100% { transform: translateY(0); opacity: 0; }
                    10%, 90% { opacity: 1; }
                    50% { transform: translateY(100px); }
                }
            </style>
        </div>

        <!-- Debug Info Overlay -->
        <div class="absolute top-2 left-2 z-50 text-[10px] text-white/70 pointer-events-none font-mono">
            <span x-text="'Scan: ' + isScanning + ' | Step: ' + livenessStep"></span>
        </div>

        <!-- Error Message on Video -->
        <div x-show="message" class="absolute inset-x-0 top-6 flex justify-center z-50 px-4 transition-all">
            <span x-text="message" :class="messageType === 'success' ? 'bg-success-500' : (messageType === 'error' ? 'bg-danger-500' : 'bg-primary-500')" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-2xl backdrop-blur-md bg-opacity-90 text-center border border-white/20"></span>
        </div>
    </div>
    
    <div class="mt-4 text-center w-full max-w-md">
        <p x-show="!isReady && !isLoading" class="mt-3 text-sm text-danger-500 font-medium">
            Tidak dapat melanjutkan tanpa akses kamera.
        </p>
        <div x-show="isReady" class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3 border border-primary-100 dark:border-primary-800">
            <p class="text-sm text-primary-700 dark:text-primary-400 font-medium">
                <span x-show="livenessStep !== 'passed'">Proses pemindaian berjalan otomatis.</span>
                <span x-show="livenessStep === 'passed'">Memproses data wajah Anda...</span>
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Posisikan wajah Anda lalu ikuti instruksi di layar (nengok kanan & kiri).
            </p>
        </div>
    </div>
</div>
