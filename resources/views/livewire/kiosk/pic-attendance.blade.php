<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Absensi Karyawan — VISITA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --bg-void: #050a18; --bg-deep: #080f22; --bg-surface: #0d1730; --bg-card: #101d38;
            --accent-primary: #6366f1; --accent-glow: #818cf8; --accent-rose: #f43f5e; --accent-gold: #fbbf24;
            --text-primary: #f0f4ff; --text-secondary: #8899bb; --text-muted: #445577;
            --border-subtle: rgba(99,102,241,0.15); --border-card: rgba(99,102,241,0.25);
            --shadow-card: 0 8px 48px rgba(0,0,0,0.5); --shadow-glow: 0 0 60px rgba(99,102,241,0.18);
        }

        html, body {
            width: 100vw; height: 100vh; overflow: hidden;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-void);
            color: var(--text-primary);
            user-select: none; -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .bg-mesh {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 50% at 15% 20%, rgba(99,102,241,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 85% 75%, rgba(244,63,94,0.07) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 50% 50%, rgba(14,25,55,0.6) 0%, transparent 80%);
        }

        .kiosk-shell {
            position: relative; z-index: 10; width: 100vw; height: 100vh;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 1.5rem; padding: 2rem;
        }

        .page-title { font-size: clamp(1.3rem,2.5vw,1.8rem); font-weight: 700; color: var(--text-primary); text-align: center; }
        .page-sub { font-size: 0.85rem; color: var(--text-secondary); text-align: center; margin-top: 0.25rem; }

        /* === Same modal-box style as welcome.blade.php === */
        .face-box {
            background: #0d1730;
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 1.5rem;
            padding: 1.5rem 1.5rem 2rem;
            width: min(92vw, 400px);
            position: relative;
            box-shadow: 0 0 80px rgba(99,102,241,0.2);
        }

        .modal-title { font-size: 1.3rem; font-weight: 700; color: #f0f4ff; margin-bottom: 0.2rem; }
        .modal-sub   { font-size: 0.82rem; color: #8899bb; margin-bottom: 1rem; }

        .back-link {
            font-size: 0.8rem; color: var(--text-secondary); text-decoration: none;
            transition: color 0.2s; display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .back-link:hover { color: var(--text-primary); }

        /* Result panel */
        .result-panel {
            width: min(92vw, 400px);
            background: #0d1730;
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 1.5rem;
            padding: 1.25rem 1.5rem;
            display: none;
            text-align: center;
            box-shadow: 0 0 80px rgba(99,102,241,0.15);
        }
        .result-panel.active { display: block; animation: fadeUp 0.3s ease both; }
        .result-name { font-size: 1.2rem; font-weight: 700; color: #f0f4ff; margin-bottom: 0.2rem; }
        .result-badge { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 600; border-radius: 999px; padding: 0.3rem 0.9rem; }
        .result-badge.checkin  { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.4); color: #10b981; }
        .result-badge.checkout { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.4); color: #818cf8; }
        .result-badge.error    { background: rgba(244,63,94,0.12); border: 1px solid rgba(244,63,94,0.4); color: #f43f5e; }

        @keyframes kp-spin      { to { transform: rotate(360deg); } }
        @keyframes kp-spin-ring { to { stroke-dashoffset: -680; } }
        @keyframes kp-pulse     { 0%,100%{opacity:0.15;}50%{opacity:0.75;} }
        @keyframes kp-bounce-r  { 0%,100%{transform:translateX(0);}50%{transform:translateX(6px);} }
        @keyframes kp-bounce-l  { 0%,100%{transform:translateX(0);}50%{transform:translateX(-6px);} }
        @keyframes fadeUp       { from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);} }
    </style>

    @livewireStyles
</head>
<body>
    <div class="bg-mesh"></div>

    <div class="kiosk-shell">
        <div>
            <div class="page-title">Absensi Karyawan • <span id="kiosk-location-display" style="color: #818cf8;">SA</span></div>
            <p class="page-sub">Tengok kanan lalu kiri untuk verifikasi wajah</p>
        </div>

        <!-- Face Scan Box — identical to #modal-face in welcome.blade.php -->
        <div class="face-box">
            <div id="pa-face-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;gap:1rem;">
                <svg style="width:2.5rem;height:2.5rem;color:#6366f1;animation:kp-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/></svg>
                <span style="color:#8899bb;font-size:0.85rem;">Memuat model AI...</span>
            </div>
            <div id="pa-face-camera-wrap" style="display:none;flex-direction:column;align-items:center;gap:0.5rem;">
                <div style="position:relative;width:272px;height:272px;flex-shrink:0;">
                    <!-- Spinning ring -->
                    <svg id="pa-ring-svg" style="position:absolute;inset:0;width:100%;height:100%;z-index:4;pointer-events:none;" viewBox="0 0 272 272">
                        <circle class="pa-ring-base" cx="136" cy="136" r="126" fill="none" stroke-width="3" stroke="#6366f133"/>
                        <circle class="pa-ring-arc"  cx="136" cy="136" r="126" fill="none" stroke-width="5" stroke-linecap="round" stroke="#6366f1" stroke-dasharray="110 692" style="animation:kp-spin-ring 1.6s linear infinite;transform-origin:center;"/>
                    </svg>
                    <!-- Circular crop -->
                    <div style="position:absolute;inset:8px;border-radius:50%;overflow:hidden;background:#111;z-index:2;">
                        <video id="pa-face-video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block;"></video>
                        <!-- Landmark canvas overlay (real 68-point dots from face-api) -->
                        <canvas id="pa-landmark-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;transform:scaleX(-1);"></canvas>
                        <!-- Face grid -->
                        <div id="pa-face-grid" style="position:absolute;inset:0;pointer-events:none;transition:opacity 0.5s;opacity:1;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;display:block;" viewBox="0 0 256 256" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    <path id="pa-face-shape" d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                                    <mask id="pa-face-mask">
                                        <rect width="256" height="256" fill="white"/>
                                        <use href="#pa-face-shape" fill="black"/>
                                    </mask>
                                </defs>
                                <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#pa-face-mask)"/>
                                <use id="pa-face-border" href="#pa-face-shape" fill="none" stroke="#818cf8" stroke-width="2.5" stroke-dasharray="10 6" style="animation:kp-pulse 1.4s ease-in-out infinite;"/>
                            </svg>
                        </div>
                        <!-- Inner success ring -->
                        <div id="pa-inner-ring" style="position:absolute;inset:0;border-radius:50%;pointer-events:none;transition:box-shadow 0.4s;"></div>
                    </div>
                </div>
                <!-- Message badge -->
                <div style="min-height:36px;display:flex;align-items:center;justify-content:center;padding:0 0.5rem;">
                    <span id="pa-face-msg" style="display:inline-block;padding:0.4rem 1.1rem;border-radius:999px;font-size:0.78rem;font-weight:600;color:#fff;text-align:center;max-width:260px;transition:background 0.3s;background:#6366f1;"></span>
                </div>
                <!-- Arrows -->
                <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;min-height:52px;">
                    <div id="pa-arrow-right" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-r 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KANAN</span>
                    </div>
                    <div id="pa-arrow-left" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-l 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KIRI</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result panel (shown after scan) -->
        <div id="pa-result-panel" class="result-panel">
            <div class="result-name" id="pa-result-name"></div>
            <div style="margin-top:0.5rem;">
                <span class="result-badge" id="pa-result-badge"></span>
            </div>
        </div>

        <a href="{{ route('kiosk.welcome') }}" class="back-link">
            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Kembali ke Layar Utama
        </a>
    </div>

    @livewire('kiosk.pic-attendance')

    <script src="{{ asset('js/face-api.min.js') }}"></script>
    <script>
        /* ---- State ---- */
        let paScanStream   = null;
        let paScanActive   = false;
        let paLivenessStep = 'straight';
        let paNoFace       = 0;
        let paFaceInPlace  = false;
        let paPrepPhoto    = false;
        let paPhotoSnap    = null;

        /* ---- Bootstrap ---- */
        (async function init() {
            setPaMsg('Memuat Model AI...', 'info');
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                ]);

                paScanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode:'user', width:{ ideal:640 } } });
                const video  = document.getElementById('pa-face-video');
                video.srcObject = paScanStream;
                video.onloadedmetadata = () => {
                    video.play();
                    document.getElementById('pa-face-loading').style.display     = 'none';
                    document.getElementById('pa-face-camera-wrap').style.display = 'flex';
                    paLivenessStep = 'straight'; paFaceInPlace = false; paScanActive = true;
                    updatePaRing('blue');
                    setPaMsg('Posisikan wajah di dalam lingkaran', 'info');
                    paScanLoop(video);
                };
            } catch(e) {
                setPaMsg('Kamera tidak dapat diakses.', 'error');
            }
        })();

        /* ---- Main scan loop (identical logic to faceScanLoop in welcome.blade.php) ---- */
        async function paScanLoop(video) {
            if (!paScanActive) return;
            try {
                const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks().withFaceDescriptor();

                updatePaRing('blue');

                if (!det) {
                    paNoFace++; paFaceInPlace = false; paGridVisible(false);
                    clearPaLandmarks();
                    if (paNoFace > 3) setPaMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => paScanLoop(video), 200); return;
                }
                paNoFace = 0;

                // Draw real 68-point landmarks
                drawPaLandmarks(det.landmarks.positions, video);

                const box   = det.alignedRect.box;
                const ratio = box.width / video.videoWidth;
                const faceCX = box.x + box.width/2, faceCY = box.y + box.height/2;
                const midX = video.videoWidth/2, midY = video.videoHeight/2;
                const offX = Math.abs(faceCX - midX) / video.videoWidth;
                const offY = Math.abs(faceCY - midY) / video.videoHeight;

                if (ratio < 0.28) { paFaceInPlace=false; paGridVisible(false); updatePaRing('red'); setPaMsg('Wajah terlalu jauh — maju sedikit.','error'); setTimeout(()=>paScanLoop(video),200); return; }
                if (ratio > 0.65) { paFaceInPlace=false; paGridVisible(false); updatePaRing('red'); setPaMsg('Wajah terlalu dekat — mundur sedikit.','error'); setTimeout(()=>paScanLoop(video),200); return; }
                if (offX > 0.20 || offY > 0.20) {
                    paFaceInPlace=false; paGridVisible(false); updatePaRing('red');
                    const dx=faceCX-midX, dy=faceCY-midY;
                    let hint = 'Posisikan wajah di tengah lingkaran';
                    if (Math.abs(dx)>Math.abs(dy)) hint = dx>0 ? 'Geser ke kiri ←':'Geser ke kanan →';
                    else hint = dy>0 ? 'Geser ke atas ↑':'Geser ke bawah ↓';
                    setPaMsg(hint,'error'); setTimeout(()=>paScanLoop(video),200); return;
                }

                paFaceInPlace = true; paGridVisible(true); updatePaRing('green');

                /* Liveness: straight → right → passed */
                if (paLivenessStep !== 'passed') {
                    const pts = det.landmarks.positions;
                    const nr  = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);

                    if (paLivenessStep === 'straight') {
                        if (!paPhotoSnap) {
                            if (!paPrepPhoto) {
                                paPrepPhoto = true;
                                setPaMsg('Diam sebentar...', 'info');
                                setTimeout(() => {
                                    paPhotoSnap = capturePhoto(video);
                                    paPrepPhoto = false;
                                }, 800);
                            }
                            setTimeout(() => paScanLoop(video), 100); return;
                        }
                        setPaMsg('Tengok ke kanan ➡', 'info'); showPaArrow('right');
                        if (nr < 0.38) paLivenessStep = 'right';

                    } else if (paLivenessStep === 'right') {
                        setPaMsg('⬅ Sekarang tengok ke kiri', 'info'); showPaArrow('left');
                        if (nr > 0.62) {
                            paLivenessStep = 'passed';
                            setPaMsg('Verifikasi berhasil! Memproses...', 'success');
                            showPaArrow('none');
                            paScanActive = false;
                            submitPaDescriptor(det.descriptor);
                            return;
                        }
                    }
                    setTimeout(() => paScanLoop(video), 100); return;
                }
            } catch(e) { console.error(e); setTimeout(() => paScanLoop(video), 500); }
        }

        /* ---- Real Face Landmark Drawing ---- */
        function drawPaLandmarks(pts, video) {
            const canvas = document.getElementById('pa-landmark-canvas');
            if (!canvas) return;

            // Use display size as canvas resolution for 1:1 pixel accuracy
            const dw = canvas.offsetWidth  || 256;
            const dh = canvas.offsetHeight || 256;
            canvas.width  = dw;
            canvas.height = dh;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, dw, dh);

            const vw = video.videoWidth  || 640;
            const vh = video.videoHeight || 480;

            // object-fit: cover — scale to fill, then center-crop
            const scale   = Math.max(dw / vw, dh / vh);
            const offsetX = (dw - vw * scale) / 2;
            const offsetY = (dh - vh * scale) / 2;

            // Transform from video-space → display-space
            const tx = p => ({ x: p.x * scale + offsetX, y: p.y * scale + offsetY });

            const dotR = Math.max(1.5, dw / 140);
            const lw   = Math.max(0.8, dw / 200);

            const groups = [
                { s:  0, e: 17, close: false, color: 'rgba(99,102,241,0.75)',  lw: lw },        // jawline
                { s: 17, e: 22, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },        // right eyebrow
                { s: 22, e: 27, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },        // left eyebrow
                { s: 27, e: 31, close: false, color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 }, // nose bridge
                { s: 30, e: 36, close: true,  color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 }, // nose bottom
                { s: 36, e: 42, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },        // right eye
                { s: 42, e: 48, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },        // left eye
                { s: 48, e: 60, close: true,  color: 'rgba(251,191,36,0.8)',   lw: lw },        // outer lips
                { s: 60, e: 68, close: true,  color: 'rgba(251,191,36,0.65)',  lw: lw * 0.9 }, // inner lips
            ];

            // Draw connecting lines (transformed coordinates)
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

            // Draw glowing dots at each landmark
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
        }

        function clearPaLandmarks() {
            const canvas = document.getElementById('pa-landmark-canvas');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }

        function capturePhoto(video) {
            try {
                const c = document.createElement('canvas');
                c.width = video.videoWidth || 320; c.height = video.videoHeight || 240;
                const ctx = c.getContext('2d');
                ctx.translate(c.width, 0); ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0);
                return c.toDataURL('image/jpeg', 0.80);
            } catch(e) { return null; }
        }

        /* Send descriptor to Livewire */
        function submitPaDescriptor(descriptor) {
            const loc = localStorage.getItem('kiosk-location') || 'SA';
            Livewire.dispatch('process-pic-face', { descriptor: Array.from(descriptor), location: loc });
        }

        /* ---- UI helpers ---- */
        function setPaMsg(msg, type) {
            const el = document.getElementById('pa-face-msg'); if (!el) return;
            el.textContent = msg;
            el.style.background = type==='error'?'#ef4444':type==='success'?'#10b981':'#6366f1';
        }

        function updatePaRing(color) {
            const arc  = document.querySelector('#pa-ring-svg .pa-ring-arc');
            const base = document.querySelector('#pa-ring-svg .pa-ring-base');
            const bdr  = document.getElementById('pa-face-border');
            const ring = document.getElementById('pa-inner-ring');
            const map  = { red:'#ef4444', green:'#10b981', blue:'#6366f1' };
            const c    = map[color] || '#6366f1';
            if (arc)  arc.setAttribute('stroke', c);
            if (base) base.setAttribute('stroke', c + '33');
            if (bdr)  bdr.setAttribute('stroke', color==='red' ? '#ef4444' : '#818cf8');
            if (ring) ring.style.boxShadow = color==='green' ? 'inset 0 0 0 3px #10b981' : '';
        }

        function paGridVisible(hide) {
            const g = document.getElementById('pa-face-grid'); if (g) g.style.opacity = hide ? '0' : '1';
        }

        function showPaArrow(dir) {
            document.getElementById('pa-arrow-right').style.display = dir==='right' ? 'flex' : 'none';
            document.getElementById('pa-arrow-left').style.display  = dir==='left'  ? 'flex' : 'none';
        }

        /* ---- Result panel handler (from Livewire events) ---- */
        window.addEventListener('attendance-success', event => {
            const d = event.detail;
            const panel  = document.getElementById('pa-result-panel');
            const nameEl = document.getElementById('pa-result-name');
            const badge  = document.getElementById('pa-result-badge');

            nameEl.textContent  = d.message;
            badge.textContent   = d.type === 'checkin' ? '✅ Check-In Berhasil' : '👋 Check-Out Berhasil';
            badge.className     = 'result-badge ' + (d.type === 'checkin' ? 'checkin' : 'checkout');
            panel.classList.add('active');

            // Sound feedback
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = d.type === 'checkin' ? 880 : 660;
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.1, ctx.currentTime + 0.1);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.5);
            } catch(e) {}

            // Resume scanning after 4s
            setTimeout(() => {
                panel.classList.remove('active');
                paLivenessStep = 'straight'; paFaceInPlace = false; paPhotoSnap = null; paPrepPhoto = false;
                updatePaRing('blue');
                setPaMsg('Posisikan wajah di dalam lingkaran', 'info');
                showPaArrow('none');
                paScanActive = true;
                paScanLoop(document.getElementById('pa-face-video'));
            }, 4000);
        });

        window.addEventListener('attendance-error', event => {
            const panel = document.getElementById('pa-result-panel');
            const nameEl = document.getElementById('pa-result-name');
            const badge  = document.getElementById('pa-result-badge');

            nameEl.textContent = event.detail.message || 'Wajah tidak dikenali';
            badge.textContent  = '❌ Tidak Dikenali';
            badge.className    = 'result-badge error';
            panel.classList.add('active');

            setTimeout(() => {
                panel.classList.remove('active');
                paLivenessStep = 'straight'; paFaceInPlace = false; paPhotoSnap = null; paPrepPhoto = false;
                updatePaRing('blue');
                setPaMsg('Posisikan wajah di dalam lingkaran', 'info');
                showPaArrow('none');
                paScanActive = true;
                paScanLoop(document.getElementById('pa-face-video'));
            }, 3000);
        });

        // Restore saved location on load
        (function() {
            const savedLoc = localStorage.getItem('kiosk-location') || 'SA';
            const displayEl = document.getElementById('kiosk-location-display');
            if (displayEl) {
                displayEl.textContent = savedLoc;
            }
        })();
    </script>

    @livewireScripts
    <script>
        (function() {
            const registerHook = () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });
            };
            if (window.Livewire) {
                registerHook();
            } else {
                document.addEventListener('livewire:init', registerHook);
            }
        })();
    </script>
</body>
</html>
