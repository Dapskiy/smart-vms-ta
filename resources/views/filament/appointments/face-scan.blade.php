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

        async scanFace() {
            if (this.isScanning) return;
            this.isScanning = true;
            this.message = 'Sedang memindai...';
            this.messageType = 'info';

            try {
                const detection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    this.message = 'Wajah tidak terdeteksi! Coba lagi.';
                    this.messageType = 'error';
                    this.isScanning = false;
                    return;
                }

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
                    }
                } else {
                    this.message = 'Menyimpan profil wajah...';
                    this.messageType = 'success';
                    this.stopVideo();
                    const descriptorArray = Array.from(detection.descriptor);
                    
                    // Execute submit with arguments directly
                    this.$wire.callMountedTableAction({ face_features: JSON.stringify(descriptorArray) });
                }
            } catch (error) {
                console.error(error);
                this.message = 'Terjadi kesalahan sistem.';
                this.messageType = 'error';
            }
            
            if(this.messageType === 'error') {
               setTimeout(() => {
                    this.isScanning = false;
               }, 2500);
            }
        },
        
        stopVideo() {
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
    
    <div class="relative rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 w-full max-w-md aspect-video bg-black shadow-inner z-10">
        <video x-ref="video" autoplay muted playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
        <!-- Overlay box -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="w-48 h-64 border-2 border-dashed border-white/70 rounded-3xl" :class="isScanning ? 'border-primary-500 animate-pulse' : ''"></div>
        </div>
        <!-- Error Message on Video (Added z-50) -->
        <div x-show="message" class="absolute inset-x-0 bottom-4 flex justify-center z-50 px-4">
            <span x-text="message" :class="messageType === 'success' ? 'bg-success-500' : (messageType === 'error' ? 'bg-danger-500' : 'bg-primary-500')" class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-xl backdrop-blur-md bg-opacity-90 text-center"></span>
        </div>
    </div>
    
    <div class="mt-6 text-center w-full max-w-md">
        <button x-show="isReady" @click="scanFace" :disabled="isScanning" class="w-full fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-lg fi-btn-size-lg gap-1.5 px-4 py-3 text-base inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
            <x-heroicon-o-camera class="w-5 h-5" />
            <span x-text="isScanning ? 'Mendeteksi Wajah...' : 'Pindai Wajah & Check-in'"></span>
        </button>
        <p x-show="!isReady && !isLoading" class="mt-3 text-sm text-danger-500 font-medium">
            Tidak dapat melanjutkan tanpa akses kamera.
        </p>
        <p x-show="isReady" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Arahkan wajah Anda ke dalam kotak putus-putus.
        </p>
    </div>
</div>
