<div x-data="picFaceScan({{ $record->id }})" class="flex flex-col items-center justify-center p-4">
    <div class="relative w-full max-w-md aspect-video bg-gray-900 rounded-lg overflow-hidden flex items-center justify-center border-2 border-gray-700">
        <video x-ref="videoElement" class="w-full h-full object-cover" autoplay muted playsinline></video>
        <canvas x-ref="canvasElement" class="absolute top-0 left-0 w-full h-full object-cover pointer-events-none"></canvas>
        
        <!-- Placeholder -->
        <div x-show="!isCameraReady" class="absolute inset-0 flex flex-col items-center justify-center text-white bg-gray-900 z-10">
            <svg class="animate-spin h-8 w-8 mb-2 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="statusText" class="text-sm">Mempersiapkan Kamera...</span>
        </div>
        
        <!-- Overlay Guide -->
        <div x-show="isCameraReady" class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
            <div class="w-48 h-64 border-4 border-dashed rounded-full" :class="isFaceValid ? 'border-success-500' : 'border-white/50'"></div>
        </div>
    </div>
    
    <div class="mt-4 text-center">
        <p x-text="scanMessage" class="text-sm text-gray-500 dark:text-gray-400 mb-4 h-5"></p>
        <button 
            type="button"
            x-on:click="captureFace()" 
            x-bind:disabled="!isFaceValid || isCapturing"
            class="px-6 py-2 bg-primary-600 hover:bg-primary-500 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!isCapturing">Ambil Wajah</span>
            <span x-show="isCapturing">Menyimpan...</span>
        </button>
    </div>
</div>

<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('picFaceScan', (picId) => ({
            picId: picId,
            isCameraReady: false,
            statusText: 'Memuat Model AI...',
            scanMessage: 'Posisikan wajah Anda di dalam area oval.',
            isFaceValid: false,
            isCapturing: false,
            stream: null,
            scanInterval: null,
            
            async init() {
                try {
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
                    ]);
                    
                    this.statusText = 'Membuka Kamera...';
                    this.stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: 'user', width: 640, height: 480 } 
                    });
                    
                    this.$refs.videoElement.srcObject = this.stream;
                    this.$refs.videoElement.onloadedmetadata = () => {
                        this.isCameraReady = true;
                        this.statusText = '';
                        this.startScanning();
                    };
                } catch (error) {
                    console.error('Kamera / AI Error:', error);
                    this.statusText = 'Gagal mengakses kamera/AI.';
                }
            },
            
            startScanning() {
                const video = this.$refs.videoElement;
                const canvas = this.$refs.canvasElement;
                
                faceapi.matchDimensions(canvas, { width: video.videoWidth, height: video.videoHeight });
                
                this.scanInterval = setInterval(async () => {
                    if (this.isCapturing) return;
                    
                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                        
                    if (detection) {
                        this.isFaceValid = true;
                        this.scanMessage = 'Wajah terdeteksi! Silakan klik tombol di bawah.';
                        
                        // Draw box (optional, if you want visual feedback)
                        // const dims = faceapi.matchDimensions(canvas, { width: video.videoWidth, height: video.videoHeight });
                        // const resized = faceapi.resizeResults(detection, dims);
                        // canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                        // faceapi.draw.drawDetections(canvas, resized);
                    } else {
                        this.isFaceValid = false;
                        this.scanMessage = 'Wajah tidak terlihat jelas. Pastikan pencahayaan cukup.';
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                    }
                }, 500);
            },
            
            async captureFace() {
                this.isCapturing = true;
                this.scanMessage = 'Sedang memproses...';
                
                const video = this.$refs.videoElement;
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                    
                if (detection) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    const photoBase64 = canvas.toDataURL('image/jpeg', 0.8);
                    const descriptor = Array.from(detection.descriptor);
                    
                    // Dispatch ke Livewire komponen (ListPics)
                    this.$wire.dispatch('save-pic-face', {
                        picId: this.picId,
                        descriptor: descriptor,
                        photoBase64: photoBase64
                    });
                    
                    // Stop stream
                    this.stopStream();
                    
                    // Close modal programmatically
                    // Filament handles it with close() event on modal if we trigger it, or user can click X
                    this.scanMessage = 'Wajah tersimpan! Silakan tutup popup ini.';
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'register_face' } }));
                    }, 1000);
                } else {
                    this.isCapturing = false;
                    this.scanMessage = 'Gagal memproses wajah, coba lagi.';
                }
            },
            
            stopStream() {
                if (this.scanInterval) clearInterval(this.scanInterval);
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }
            },
            
            destroy() {
                this.stopStream();
            }
        }));
    });
</script>
