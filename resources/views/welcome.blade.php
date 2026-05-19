<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VISITA — Selamat Datang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           RESET & BASE
        ============================================================ */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-void:        #050a18;
            --bg-deep:        #080f22;
            --bg-surface:     #0d1730;
            --bg-card:        #101d38;
            --bg-card-hover:  #14234a;

            --accent-primary: #6366f1;   /* indigo */
            --accent-glow:    #818cf8;
            --accent-rose:    #f43f5e;
            --accent-gold:    #fbbf24;

            --text-primary:   #f0f4ff;
            --text-secondary: #8899bb;
            --text-muted:     #445577;

            --border-subtle:  rgba(99, 102, 241, 0.15);
            --border-card:    rgba(99, 102, 241, 0.25);
            --border-glow:    rgba(99, 102, 241, 0.6);

            --shadow-card:    0 8px 48px rgba(0, 0, 0, 0.5);
            --shadow-glow:    0 0 60px rgba(99, 102, 241, 0.18);
        }

        html, body {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-void);
            color: var(--text-primary);
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            cursor: default;
        }

        /* ============================================================
           BACKGROUND — CANVAS PARTICLES + GRADIENT MESH
        ============================================================ */
        #particle-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .bg-mesh {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                radial-gradient(ellipse 80% 50% at 15% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 85% 75%, rgba(244, 63, 94, 0.07) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 50% 50%, rgba(14, 25, 55, 0.6) 0%, transparent 80%);
        }

        /* Horizontal scan line shimmer */
        .bg-scanline {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 3px,
                rgba(99, 102, 241, 0.012) 3px,
                rgba(99, 102, 241, 0.012) 4px
            );
        }

        /* ============================================================
           LAYOUT WRAPPER
        ============================================================ */
        .kiosk-shell {
            position: relative;
            z-index: 10;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 2vh 3vw;
        }

        /* ============================================================
           HEADER
        ============================================================ */
        .kiosk-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            padding: 0 0.5vw;
            height: 9vh;
        }

        /* --- Logo --- */
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--accent-primary), #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 24px rgba(99, 102, 241, 0.5);
            flex-shrink: 0;
        }

        .logo-icon svg {
            width: 1.6rem;
            height: 1.6rem;
            fill: #fff;
        }

        .logo-text {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            line-height: 1;
        }

        .logo-dot {
            color: var(--accent-primary);
            text-shadow: 0 0 12px var(--accent-primary);
        }

        .logo-tagline {
            font-size: 0.65rem;
            font-weight: 400;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }

        /* --- Clock / Date --- */
        .header-clock {
            text-align: right;
        }

        .clock-time {
            font-size: clamp(2rem, 3.5vw, 2.8rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .clock-colon {
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }

        .clock-date {
            font-size: clamp(0.7rem, 1.1vw, 0.9rem);
            font-weight: 400;
            color: var(--text-secondary);
            margin-top: 0.2rem;
            letter-spacing: 0.05em;
        }

        /* --- Divider line --- */
        .header-divider {
            height: 1px;
            background: linear-gradient(90deg,
                transparent 0%,
                var(--border-subtle) 20%,
                var(--accent-primary) 50%,
                var(--border-subtle) 80%,
                transparent 100%
            );
            flex-shrink: 0;
            margin: 0 0.5vw;
            opacity: 0.6;
        }

        /* ============================================================
           MAIN
        ============================================================ */
        .kiosk-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4vh;
            padding: 2vh 0;
        }

        /* --- Welcome text --- */
        .welcome-block {
            text-align: center;
        }

        .welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.65rem, 0.9vw, 0.78rem);
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            margin-bottom: 1.2rem;
        }

        .welcome-label::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-primary);
            box-shadow: 0 0 8px var(--accent-primary);
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(0.7); }
        }

        .welcome-heading {
            font-size: clamp(2rem, 4.5vw, 3.8rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: var(--text-primary);
        }

        .welcome-heading .highlight {
            background: linear-gradient(90deg, var(--accent-glow), var(--accent-primary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-sub {
            margin-top: 0.8rem;
            font-size: clamp(0.9rem, 1.6vw, 1.25rem);
            font-weight: 400;
            color: var(--text-secondary);
            letter-spacing: 0.02em;
        }

        /* --- Cards row --- */
        .cards-row {
            display: flex;
            gap: clamp(1.5rem, 3vw, 2.5rem);
            width: 100%;
            max-width: 1000px;
            justify-content: center;
        }

        .checkin-card {
            flex: 1;
            max-width: 440px;
            min-height: 28vh;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 1.5rem;
            padding: clamp(1.5rem, 3vh, 2.5rem) clamp(1.5rem, 2.5vw, 2.5rem);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition:
                transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                background 0.25s ease;
            box-shadow: var(--shadow-card);
        }

        /* Card shimmer stripe */
        .checkin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -60%;
            width: 40%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.04), transparent);
            transform: skewX(-15deg);
            transition: left 0.5s ease;
        }

        .checkin-card:hover::before {
            left: 130%;
        }

        /* Corner glow accent */
        .checkin-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            border-radius: 0 1.5rem 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card-appointment::after {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.2), transparent 70%);
        }

        .card-walkin::after {
            background: radial-gradient(circle at top right, rgba(244, 63, 94, 0.18), transparent 70%);
        }

        .checkin-card:hover::after {
            opacity: 1;
        }

        /* Hover states */
        .card-appointment:hover {
            border-color: var(--border-glow);
            box-shadow: var(--shadow-card), var(--shadow-glow);
            background: var(--bg-card-hover);
            transform: translateY(-4px) scale(1.012);
        }

        .card-walkin:hover {
            border-color: rgba(244, 63, 94, 0.55);
            box-shadow: var(--shadow-card), 0 0 50px rgba(244, 63, 94, 0.15);
            background: var(--bg-card-hover);
            transform: translateY(-4px) scale(1.012);
        }

        /* Active / Touch press */
        .checkin-card:active {
            transform: scale(0.96) !important;
            transition: transform 0.08s ease;
        }

        /* Card icon bubble */
        .card-icon-wrap {
            width: clamp(3.5rem, 5vw, 4.5rem);
            height: clamp(3.5rem, 5vw, 4.5rem);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-appointment .card-icon-wrap {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(99, 102, 241, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
        }

        .card-walkin .card-icon-wrap {
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.2), rgba(244, 63, 94, 0.08));
            border: 1px solid rgba(244, 63, 94, 0.28);
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.12);
        }

        .card-icon-wrap svg {
            width: clamp(1.6rem, 2.5vw, 2.2rem);
            height: clamp(1.6rem, 2.5vw, 2.2rem);
        }

        .card-appointment .card-icon-wrap svg { color: var(--accent-glow); }
        .card-walkin    .card-icon-wrap svg { color: #fb7185; }

        /* Card text */
        .card-body {
            flex: 1;
        }

        .card-title {
            font-size: clamp(1.1rem, 2vw, 1.6rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .card-sub {
            margin-top: 0.4rem;
            font-size: clamp(0.75rem, 1.1vw, 0.95rem);
            font-weight: 400;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        /* Card CTA arrow */
        .card-cta {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: clamp(0.7rem, 1vw, 0.85rem);
            font-weight: 600;
            letter-spacing: 0.06em;
            transition: gap 0.2s ease;
        }

        .card-appointment .card-cta { color: var(--accent-glow); }
        .card-walkin    .card-cta { color: #fb7185; }

        .checkin-card:hover .card-cta { gap: 0.7rem; }

        .card-cta svg {
            width: 1rem;
            height: 1rem;
            transition: transform 0.2s ease;
        }

        .checkin-card:hover .card-cta svg { transform: translateX(3px); }

        /* ============================================================
           FOOTER
        ============================================================ */
        .kiosk-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 1.2vh 0 0.5vh;
        }

        .footer-copy {
            font-size: clamp(0.6rem, 0.85vw, 0.75rem);
            color: var(--text-muted);
            letter-spacing: 0.1em;
        }

        .footer-copy strong {
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* ============================================================
           RIPPLE EFFECT (touch feedback)
        ============================================================ */
        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-expand 0.55s linear;
            pointer-events: none;
        }

        .card-appointment .ripple {
            background: rgba(99, 102, 241, 0.2);
        }

        .card-walkin .ripple {
            background: rgba(244, 63, 94, 0.18);
        }

        @keyframes ripple-expand {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* ============================================================
           ENTRY ANIMATIONS
        ============================================================ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .kiosk-header   { animation: fadeUp 0.6s ease both; animation-delay: 0.05s; }
        .welcome-block  { animation: fadeUp 0.6s ease both; animation-delay: 0.2s; }
        .checkin-card:nth-child(1) { animation: fadeUp 0.6s ease both; animation-delay: 0.35s; }
        .checkin-card:nth-child(2) { animation: fadeUp 0.6s ease both; animation-delay: 0.48s; }
        .kiosk-footer   { animation: fadeUp 0.6s ease both; animation-delay: 0.55s; }

        /* ============================================================
           STATUS BAR (online indicator)
        ============================================================ */
        .status-bar {
            position: fixed;
            bottom: 1.2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.65rem;
            color: var(--text-muted);
            letter-spacing: 0.12em;
            z-index: 20;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 8px #22c55e;
            animation: pulse-dot 2.5s ease-in-out infinite;
        }

    </style>
</head>
<body>

    <!-- Background layers -->
    <canvas id="particle-canvas"></canvas>
    <div class="bg-mesh"></div>
    <div class="bg-scanline"></div>

    <!-- ==================== KIOSK SHELL ==================== -->
    <div class="kiosk-shell">

        <!-- HEADER -->
        <header class="kiosk-header">
            <div class="logo-wrap">
                <div class="logo-icon">
                    <!-- V-shield logo mark -->
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L3 6.5V12c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V6.5L12 2zm-1.5 13.5L7 12l1.41-1.41L10.5 13.17l5.09-5.08L17 9.5 10.5 15.5z"/>
                    </svg>
                </div>
                <div>
                    <div class="logo-text">VISITA<span class="logo-dot">.</span></div>
                    <div class="logo-tagline">Enterprise Visitor Management</div>
                </div>
            </div>

            <div class="header-clock">
                <div class="clock-time">
                    <span id="clock-h">--</span><span class="clock-colon">:</span><span id="clock-m">--</span>
                </div>
                <div class="clock-date" id="clock-date">Memuat tanggal...</div>
            </div>
        </header>

        <div class="header-divider"></div>

        <!-- MAIN -->
        <main class="kiosk-main">

            <!-- Welcome text -->
            <div class="welcome-block">
                <div class="welcome-label">Sistem Check-in Otomatis</div>
                <h1 class="welcome-heading">
                    Selamat Datang di <span class="highlight">VISITA</span>
                </h1>
                <p class="welcome-sub">Silakan pilih metode check-in Anda untuk melanjutkan</p>
            </div>

            <!-- Action cards -->
            <div class="cards-row">

                <!-- Card 1: Sudah Ada Janji -->
                <div class="checkin-card card-appointment" onclick="handleCheckin('appointment')" role="button" tabindex="0" aria-label="Check-in dengan janji temu">
                    <div class="card-icon-wrap">
                        <!-- QR Code icon -->
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <path d="M14 14h.01M18 14h.01M14 18h.01M18 18h.01M16 16v.01"/>
                        </svg>
                    </div>

                    <div class="card-body">
                        <div class="card-title">Sudah Ada Janji</div>
                        <div class="card-sub">Scan QR Code atau masukkan<br>kode token reservasi Anda</div>
                    </div>

                    <div class="card-cta">
                        MULAI CHECK-IN
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Tamu Baru / Walk-in -->
                <div class="checkin-card card-walkin" onclick="handleCheckin('walkin')" role="button" tabindex="0" aria-label="Registrasi tamu baru walk-in">
                    <div class="card-icon-wrap">
                        <!-- User Plus icon -->
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <line x1="19" y1="8" x2="19" y2="14"/>
                            <line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </div>

                    <div class="card-body">
                        <div class="card-title">Tamu Baru / Walk-in</div>
                        <div class="card-sub">Isi formulir registrasi secara<br>langsung di layar ini</div>
                    </div>

                    <div class="card-cta">
                        DAFTAR SEKARANG
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

            </div>
        </main>

        <!-- FOOTER -->
        <footer class="kiosk-footer">
            <p class="footer-copy">
                &copy; <span id="footer-year"></span> <strong>VISITA</strong> — Enterprise Visitor Management System &nbsp;·&nbsp; Semua hak dilindungi undang-undang
            </p>
        </footer>

    </div>

    <!-- Online status bar -->
    <div class="status-bar">
        <div class="status-dot"></div>
        SISTEM AKTIF &amp; TERHUBUNG
    </div>

    <!-- ==================== JAVASCRIPT ==================== -->
    <script>
        /* -------------------------------------------------------
           CLOCK & DATE
        ------------------------------------------------------- */
        const DAYS_ID   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni',
                           'Juli','Agustus','September','Oktober','November','Desember'];

        function padTwo(n) { return String(n).padStart(2, '0'); }

        function updateClock() {
            const now  = new Date();
            const h    = padTwo(now.getHours());
            const m    = padTwo(now.getMinutes());
            const day  = DAYS_ID[now.getDay()];
            const date = now.getDate();
            const mon  = MONTHS_ID[now.getMonth()];
            const yr   = now.getFullYear();

            document.getElementById('clock-h').textContent    = h;
            document.getElementById('clock-m').textContent    = m;
            document.getElementById('clock-date').textContent = `${day}, ${date} ${mon} ${yr}`;
            document.getElementById('footer-year').textContent = yr;
        }

        updateClock();
        setInterval(updateClock, 1000);

        /* -------------------------------------------------------
           RIPPLE EFFECT
        ------------------------------------------------------- */
        document.querySelectorAll('.checkin-card').forEach(card => {
            card.addEventListener('pointerdown', function (e) {
                const rect    = this.getBoundingClientRect();
                const x       = e.clientX - rect.left;
                const y       = e.clientY - rect.top;
                const size    = Math.max(rect.width, rect.height) * 1.5;

                const ripple  = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x - size / 2}px;
                    top: ${y - size / 2}px;
                `;
                this.appendChild(ripple);

                ripple.addEventListener('animationend', () => ripple.remove());
            });
        });

        /* -------------------------------------------------------
           CARD HANDLER — redirect ke route Laravel
        ------------------------------------------------------- */
        function handleCheckin(type) {
            if (type === 'appointment') {
                // window.location.href = "/kiosk/appointment";
                console.log('[VISITA] Navigate → Appointment Check-in');
                alert('Fitur Scan Token akan segera hadir!');
            } else {
                // window.location.href = "/kiosk/walkin";
                console.log('[VISITA] Navigate → Walk-in Registration');
                alert('Fitur Form Registrasi akan segera hadir!');
            }
        }

        /* -------------------------------------------------------
           PARTICLE CANVAS
        ------------------------------------------------------- */
        (function initParticles() {
            const canvas = document.getElementById('particle-canvas');
            const ctx    = canvas.getContext('2d');

            let W, H, particles;

            const CONFIG = {
                count:         70,
                baseRadius:    1.2,
                maxSpeed:      0.25,
                connectDist:   160,
                baseOpacity:   0.35,
                colors:        ['#6366f1', '#818cf8', '#a5b4fc', '#f43f5e'],
            };

            function resize() {
                W = canvas.width  = window.innerWidth;
                H = canvas.height = window.innerHeight;
            }

            class Particle {
                constructor() { this.reset(true); }

                reset(init = false) {
                    this.x    = Math.random() * W;
                    this.y    = init ? Math.random() * H : (Math.random() > 0.5 ? -5 : H + 5);
                    this.r    = CONFIG.baseRadius + Math.random() * 1.2;
                    this.vx   = (Math.random() - 0.5) * CONFIG.maxSpeed;
                    this.vy   = (Math.random() - 0.5) * CONFIG.maxSpeed;
                    this.color = CONFIG.colors[Math.floor(Math.random() * CONFIG.colors.length)];
                    this.alpha = 0.1 + Math.random() * 0.5;
                    this.pulse = Math.random() * Math.PI * 2;
                    this.pulseSpeed = 0.008 + Math.random() * 0.01;
                }

                update() {
                    this.x += this.vx;
                    this.y += this.vy;
                    this.pulse += this.pulseSpeed;

                    if (this.x < -10 || this.x > W + 10 || this.y < -10 || this.y > H + 10) {
                        this.reset();
                    }
                }

                draw() {
                    const a = this.alpha * (0.7 + 0.3 * Math.sin(this.pulse));
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                    ctx.fillStyle = this.color;
                    ctx.globalAlpha = a;
                    ctx.fill();
                    ctx.globalAlpha = 1;
                }
            }

            function buildParticles() {
                particles = Array.from({ length: CONFIG.count }, () => new Particle());
            }

            function drawLines() {
                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const a = particles[i], b = particles[j];
                        const dx = a.x - b.x, dy = a.y - b.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < CONFIG.connectDist) {
                            const alpha = (1 - dist / CONFIG.connectDist) * 0.12;
                            ctx.beginPath();
                            ctx.moveTo(a.x, a.y);
                            ctx.lineTo(b.x, b.y);
                            ctx.strokeStyle = `rgba(99,102,241,${alpha})`;
                            ctx.lineWidth = 0.7;
                            ctx.stroke();
                        }
                    }
                }
            }

            function loop() {
                ctx.clearRect(0, 0, W, H);
                drawLines();
                particles.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(loop);
            }

            resize();
            buildParticles();
            loop();

            window.addEventListener('resize', () => { resize(); buildParticles(); });
        })();

        /* -------------------------------------------------------
           PREVENT CONTEXT MENU (kiosk mode)
        ------------------------------------------------------- */
        document.addEventListener('contextmenu', e => e.preventDefault());
    </script>

</body>
</html>