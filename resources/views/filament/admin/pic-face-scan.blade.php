<style>
    @keyframes kp-spin { 100% { transform: rotate(360deg); } }
    @keyframes kp-spin-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes kp-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    @keyframes kp-bounce-r { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(5px); } }
    @keyframes kp-bounce-l { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(-5px); } }
</style>

<div id="pic-scan-overlay" 
     x-data="{
         picId: {{ $record->id }},
         isCameraReady: false,
         statusText: 'Memuat Model AI...',
         scanMessage: 'Posisikan wajah di dalam lingkaran',
         msgColor: '#10b981',
         ringColor: '#10b981',
         arrowDir: 'none',
         faceInPlace: false,
         
         stream: null,
         scanActive: false,
         processing: false,
         livenessStep: 'straight',
         noFaceCount: 0,
         landmarkRAF: null,
         photoBase64: null,
         
         async init() {
             try {
                 if (typeof window.faceapi === 'undefined') {
                     await this.loadScript('{{ asset('js/face-api.min.js') }}');
                 }
                 
                 this.statusText = 'Memuat Model AI...';
                 await Promise.all([
                     faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                     faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                     faceapi.nets.faceRecognitionNet.loadFromUri('/models')
                 ]);
                 
                 this.statusText = 'Membuka Kamera...';
                 this.stream = await navigator.mediaDevices.getUserMedia({ 
                     video: { facingMode: 'user', width: { ideal: 640 } } 
                 });
                 
                 this.$refs.videoElement.srcObject = this.stream;
                 this.$refs.videoElement.onloadedmetadata = () => {
                     this.$refs.videoElement.play();
                     this.isCameraReady = true;
                     this.scanActive = true;
                     this.livenessStep = 'straight';
                     this.photoBase64 = null;
                     this.landmarkLoop();
                     this.detectionLoop();
                 };
             } catch (error) {
                 console.error('Kamera/AI Error:', error);
                 this.statusText = 'Gagal mengakses kamera atau memuat model AI.';
             }
         },
         
         loadScript(src) {
             return new Promise((resolve, reject) => {
                 if (document.querySelector(`script[src='${src}']`)) {
                     resolve(); return;
                 }
                 const script = document.createElement('script');
                 script.src = src;
                 script.onload = resolve;
                 script.onerror = reject;
                 document.head.appendChild(script);
             });
         },
         
         setMsg(msg, type) {
             this.scanMessage = msg;
             this.msgColor = type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#10b981';
         },
         
         setRing(color) {
             const map = { red: '#ef4444', green: '#10b981', blue: '#6366f1' };
             this.ringColor = map[color] || '#10b981';
         },
         
         async landmarkLoop() {
             if (!this.scanActive) return;
             try {
                 const det = await faceapi.detectSingleFace(this.$refs.videoElement, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.4 }))
                     .withFaceLandmarks();
                 if (det) this.drawLandmarks(det.landmarks.positions);
                 else this.clearLandmarks();
             } catch(e) {}
             if (this.scanActive) this.landmarkRAF = requestAnimationFrame(() => this.landmarkLoop());
         },
         
         async detectionLoop() {
             if (!this.scanActive) return;
             if (this.processing) { setTimeout(() => this.detectionLoop(), 150); return; }
             
             const video = this.$refs.videoElement;
             try {
                 const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                     .withFaceLandmarks().withFaceDescriptor();
                     
                 if (!det) {
                     this.noFaceCount++;
                     this.faceInPlace = false;
                     this.setRing('red');
                     if (this.noFaceCount > 3) this.setMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                     setTimeout(() => this.detectionLoop(), 150); return;
                 }
                 this.noFaceCount = 0;
                 
                 const box = det.alignedRect.box;
                 const ratio = box.width / video.videoWidth;
                 const offX = Math.abs((box.x + box.width/2) - video.videoWidth/2) / video.videoWidth;
                 const offY = Math.abs((box.y + box.height/2) - video.videoHeight/2) / video.videoHeight;
                 
                 if (ratio < 0.28) { this.faceInPlace = false; this.setRing('red'); this.setMsg('Wajah terlalu jauh - maju sedikit.', 'error'); setTimeout(() => this.detectionLoop(), 150); return; }
                 if (ratio > 0.65) { this.faceInPlace = false; this.setRing('red'); this.setMsg('Wajah terlalu dekat - mundur sedikit.', 'error'); setTimeout(() => this.detectionLoop(), 150); return; }
                 if (offX > 0.20 || offY > 0.20) {
                     this.faceInPlace = false;
                     this.setRing('red');
                     this.setMsg('Posisikan wajah di tengah lingkaran.', 'error');
                     setTimeout(() => this.detectionLoop(), 150); return;
                 }
                 
                 this.faceInPlace = true;
                 this.setRing('green');
                 
                 // Capture front face for the photo before liveness
                 if (!this.photoBase64 && this.livenessStep === 'straight') {
                     const tmpCanvas = document.createElement('canvas');
                     tmpCanvas.width = video.videoWidth;
                     tmpCanvas.height = video.videoHeight;
                     const tctx = tmpCanvas.getContext('2d');
                     tctx.translate(tmpCanvas.width, 0);
                     tctx.scale(-1, 1);
                     tctx.drawImage(video, 0, 0);
                     this.photoBase64 = tmpCanvas.toDataURL('image/jpeg', 0.85);
                 }
                 
                 const pts = det.landmarks.positions;
                 const nr = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);
                 
                 if (this.livenessStep === 'straight') {
                     this.setMsg('Tengok ke kanan >>>', 'info'); this.arrowDir = 'right';
                     if (nr < 0.38) this.livenessStep = 'right';
                 } else if (this.livenessStep === 'right') {
                     this.setMsg('<<< Sekarang tengok ke kiri', 'info'); this.arrowDir = 'left';
                     if (nr > 0.62) {
                         this.livenessStep = 'passed';
                         this.setMsg('Verifikasi OK! Menyimpan...', 'success');
                         this.arrowDir = 'none';
                         this.scanActive = false;
                         this.processing = true;
                         if (this.landmarkRAF) cancelAnimationFrame(this.landmarkRAF);
                         
                         // Send to Livewire v3
                         if (window.Livewire) {
                             Livewire.dispatch('save-pic-face', {
                                 picId: this.picId,
                                 descriptor: Array.from(det.descriptor),
                                 photoBase64: this.photoBase64
                             });
                         }
                         
                         setTimeout(() => {
                             this.closeModal();
                         }, 1500);
                         return;
                     }
                 }
                 setTimeout(() => this.detectionLoop(), 150);
             } catch(e) {
                 console.error(e);
                 setTimeout(() => this.detectionLoop(), 500);
             }
         },
         
         drawLandmarks(pts) {
             const canvas = this.$refs.canvasElement;
             if (!canvas) return;
             
             const dw = canvas.offsetWidth || 256;
             const dh = canvas.offsetHeight || 256;
             canvas.width = dw;
             canvas.height = dh;
             
             const ctx = canvas.getContext('2d');
             ctx.clearRect(0, 0, dw, dh);
             
             const video = this.$refs.videoElement;
             const vw = video.videoWidth || 640;
             const vh = video.videoHeight || 480;
             
             const scale = Math.max(dw / vw, dh / vh);
             const offsetX = (dw - vw * scale) / 2;
             const offsetY = (dh - vh * scale) / 2;
             const tx = p => ({ x: p.x * scale + offsetX, y: p.y * scale + offsetY });
             
             const dotR = Math.max(1.5, dw / 140);
             const lw   = Math.max(0.8, dw / 200);
             
             const groups = [
                 { s:  0, e: 17, close: false, color: 'rgba(99,102,241,0.75)',  lw: lw },
                 { s: 17, e: 22, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },
                 { s: 22, e: 27, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },
                 { s: 27, e: 31, close: false, color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 },
                 { s: 30, e: 36, close: true,  color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 },
                 { s: 36, e: 42, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },
                 { s: 42, e: 48, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },
                 { s: 48, e: 60, close: true,  color: 'rgba(251,191,36,0.8)',   lw: lw },
                 { s: 60, e: 68, close: true,  color: 'rgba(251,191,36,0.65)',  lw: lw * 0.9 },
             ];
             
             groups.forEach(({ s, e, close, color, lw: w }) => {
                 const gpts = pts.slice(s, e).map(tx);
                 if (gpts.length < 2) return;
                 ctx.beginPath();
                 ctx.moveTo(gpts[0].x, gpts[0].y);
                 gpts.slice(1).forEach(p => ctx.lineTo(p.x, p.y));
                 if (close) ctx.closePath();
                 ctx.strokeStyle = color;
                 ctx.lineWidth   = w;
                 ctx.stroke();
             });
             
             pts.forEach((rawP, i) => {
                 const p = tx(rawP);
                 let dotColor, glowColor;
                 if      (i < 17) { dotColor = '#818cf8'; glowColor = 'rgba(99,102,241,0.7)'; }
                 else if (i < 27) { dotColor = '#60c8ff'; glowColor = 'rgba(99,180,241,0.7)'; }
                 else if (i < 36) { dotColor = '#dde6ff'; glowColor = 'rgba(200,210,255,0.5)'; }
                 else if (i < 48) { dotColor = '#34d399'; glowColor = 'rgba(52,211,153,0.7)'; }
                 else             { dotColor = '#fcd34d'; glowColor = 'rgba(251,191,36,0.7)'; }
                 
                 ctx.shadowColor = glowColor;
                 ctx.shadowBlur  = 6;
                 ctx.beginPath();
                 ctx.arc(p.x, p.y, dotR, 0, Math.PI * 2);
                 ctx.fillStyle = dotColor;
                 ctx.fill();
                 ctx.shadowBlur = 0;
             });
         },
         
         clearLandmarks() {
             const canvas = this.$refs.canvasElement;
             if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
         },
         
         closeModal() {
             this.stopStream();
             const activeModal = document.querySelector('[x-data^=\'fiModal\'], .fi-modal');
             if (activeModal) {
                 const closeBtn = activeModal.querySelector('button[size=\'sm\'], button[x-on\\:click*=\'close\'], .fi-modal-close-btn');
                 if (closeBtn) {
                     closeBtn.click();
                     return;
                 }
             }
             window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'register_face' } }));
         },
         
         stopStream() {
             this.scanActive = false;
             if (this.landmarkRAF) cancelAnimationFrame(this.landmarkRAF);
             if (this.stream) {
                 this.stream.getTracks().forEach(track => track.stop());
             }
         },
         
         destroy() {
             this.stopStream();
         }
     }"
     style="position: fixed; inset: 0; z-index: 99999; background: rgba(5, 10, 24, 0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif;">
    
    <div style="background: #0d1730; border: 1.5px solid rgba(251, 191, 36, 0.4); border-radius: 1.5rem; padding: 2rem; width: min(92vw, 400px); position: relative; box-shadow: 0 0 80px rgba(251, 191, 36, 0.15); display: flex; flex-direction: column; align-items: center; gap: 1rem;">
        
        <!-- Close Button -->
        <button type="button" @click="closeModal()" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.06); border: none; color: #8899bb; border-radius: 0.5rem; width: 2rem; height: 2rem; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">✕</button>
        
        <!-- Title & Subtitle -->
        <div style="font-size: 1.3rem; font-weight: 700; color: #f0f4ff; margin-bottom: 0.2rem; text-align: center;">Registrasi Wajah PIC</div>
        <p style="font-size: 0.82rem; color: #8899bb; margin-bottom: 0.5rem; text-align: center;">Scan wajah untuk pendaftaran — tengok kanan lalu kiri</p>

        <!-- Loading State -->
        <div x-show="!isCameraReady" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 280px; gap: 1rem; width: 100%;">
            <svg style="width: 2.5rem; height: 2.5rem; color: #10b981; animation: kp-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/>
            </svg>
            <span x-text="statusText" style="font-size: 0.85rem; color: #8899bb;">Memuat model AI...</span>
        </div>

        <!-- Camera / Face Scan UI -->
        <div x-show="isCameraReady" style="display: none; flex-direction: column; align-items: center; gap: 8px; width: 100%;" :style="isCameraReady ? 'display: flex' : 'display: none'">
            
            <div style="position: relative; width: 272px; height: 272px; flex-shrink: 0; margin: 0 auto;">
                <svg id="pic-scan-ring-svg" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 4; pointer-events: none;" viewBox="0 0 272 272">
                    <circle cx="136" cy="136" r="126" fill="none" stroke-width="3" :stroke="ringColor + '33'"/>
                    <circle cx="136" cy="136" r="126" fill="none" stroke-width="5" stroke-linecap="round" stroke-dasharray="110 692" style="animation: kp-spin-ring 1.6s linear infinite; transform-origin: center;" :stroke="ringColor"/>
                </svg>
                <div style="position: absolute; inset: 8px; border-radius: 50%; overflow: hidden; background: #111; z-index: 2;">
                    <video x-ref="videoElement" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block;"></video>
                    <canvas x-ref="canvasElement" style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; transform: scaleX(-1); z-index: 3;"></canvas>
                    
                    <!-- Face Mask Guide -->
                    <div style="position: absolute; inset: 0; pointer-events: none; transition: opacity 0.5s; z-index: 2;" :style="faceInPlace ? 'opacity: 1' : 'opacity: 0'">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; display: block;" viewBox="0 0 256 256" preserveAspectRatio="xMidYMid slice">
                            <defs>
                                <path id="pic-face-shape" d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                                <mask id="pic-face-mask">
                                    <rect width="256" height="256" fill="white"/>
                                    <use href="#pic-face-shape" fill="black"/>
                                </mask>
                            </defs>
                            <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#pic-face-mask)"/>
                            <use href="#pic-face-shape" fill="none" stroke-width="2.5" stroke-dasharray="10 6" style="animation: kp-pulse 1.4s ease-in-out infinite;" :stroke="ringColor === '#ef4444' ? '#ef4444' : '#818cf8'"/>
                        </svg>
                    </div>
                    <!-- Success border overlay -->
                    <div style="position: absolute; inset: 0; border-radius: 50%; pointer-events: none; transition: box-shadow 0.4s; z-index: 5;" :style="ringColor === '#10b981' ? 'box-shadow: inset 0 0 0 3px #10b981' : ''"></div>
                </div>
            </div>

            <!-- Message box -->
            <div style="min-height: 36px; display: flex; align-items: center; justify-content: center; padding: 0 0.5rem; margin-top: 10px;">
                <span style="display: inline-block; padding: 0.4rem 1.1rem; border-radius: 999px; font-size: 0.78rem; font-weight: 600; color: #fff; text-align: center; max-width: 260px; transition: background 0.3s;" :style="'background: ' + msgColor" x-text="scanMessage"></span>
            </div>

            <!-- Arrows -->
            <div style="display: flex; align-items: center; justify-content: center; gap: 1.5rem; min-height: 52px;">
                <div style="display: none; flex-direction: column; align-items: center; gap: 4px; animation: kp-bounce-r 0.8s ease-in-out infinite;" :style="arrowDir === 'right' ? 'display: flex' : 'display: none'">
                    <svg style="width: 2rem; height: 2rem; color: #818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <span style="font-size: 0.68rem; color: #818cf8; font-weight: 700; letter-spacing: .04em;">KANAN</span>
                </div>
                <div style="display: none; flex-direction: column; align-items: center; gap: 4px; animation: kp-bounce-l 0.8s ease-in-out infinite;" :style="arrowDir === 'left' ? 'display: flex' : 'display: none'">
                    <svg style="width: 2rem; height: 2rem; color: #818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                    <span style="font-size: 0.68rem; color: #818cf8; font-weight: 700; letter-spacing: .04em;">KIRI</span>
                </div>
            </div>
        </div>
    </div>
</div>
