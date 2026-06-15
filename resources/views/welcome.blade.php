<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        .card-checkout::after {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.18), transparent 70%);
        }
        .card-checkout:hover {
            border-color: rgba(16, 185, 129, 0.55);
            box-shadow: var(--shadow-card), 0 0 50px rgba(16, 185, 129, 0.15);
            background: var(--bg-card-hover);
            transform: translateY(-4px) scale(1.012);
        }
        .card-checkout .card-icon-wrap {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.08));
            border: 1px solid rgba(16, 185, 129, 0.28);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.12);
        }
        .card-checkout .card-icon-wrap svg { color: #10b981; }
        .card-checkout .card-cta { color: #10b981; }
        .card-checkout .ripple { background: rgba(16, 185, 129, 0.18); }


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

        /* ============================================================
           OVERLAY MODALS
        ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(5, 10, 24, 0.85);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: #0d1730;
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 1.5rem;
            padding: 2rem;
            width: min(92vw, 480px);
            position: relative;
            box-shadow: 0 0 80px rgba(99,102,241,0.2);
            animation: fadeUp 0.3s ease both;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #f0f4ff;
            margin-bottom: 0.4rem;
        }
        .modal-sub {
            font-size: 0.82rem;
            color: #8899bb;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            position: absolute;
            top: 1rem; right: 1rem;
            background: rgba(255,255,255,0.06);
            border: none;
            color: #8899bb;
            border-radius: 0.5rem;
            width: 2rem; height: 2rem;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.12); color: #f0f4ff; }

        /* Method picker buttons */
        .method-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            border: 1px solid rgba(99,102,241,0.25);
            background: rgba(99,102,241,0.07);
            color: #f0f4ff;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-bottom: 0.75rem;
            font-family: 'Poppins', sans-serif;
            text-align: left;
        }
        .method-btn:hover {
            border-color: rgba(99,102,241,0.6);
            background: rgba(99,102,241,0.15);
            transform: translateX(4px);
        }
        .method-btn .mb-icon {
            width: 2.8rem; height: 2.8rem;
            border-radius: 0.75rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .method-btn .mb-icon svg { width: 1.4rem; height: 1.4rem; }
        .method-btn .mb-text strong { display: block; font-size: 0.95rem; font-weight: 600; }
        .method-btn .mb-text span  { font-size: 0.75rem; color: #8899bb; }
        .method-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

        /* Face scan modal */
        .face-modal-box {
            width: min(92vw, 400px);
            padding: 1.5rem 1.5rem 2rem;
        }

        /* Success popup */
        .success-box {
            width: min(94vw, 520px);
            text-align: center;
        }
        .success-icon {
            width: 5rem; height: 5rem;
            border-radius: 50%;
            background: rgba(16,185,129,0.15);
            border: 2px solid #10b981;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .success-icon svg { width: 2.5rem; height: 2.5rem; color: #10b981; }
        .success-heading { font-size: 1.5rem; font-weight: 700; color: #f0f4ff; margin-bottom: 0.3rem; }
        .success-sub     { font-size: 0.85rem; color: #8899bb; margin-bottom: 1.5rem; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .info-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
        }
        .info-item label { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: #6366f1; display: block; margin-bottom: 0.2rem; }
        .info-item span  { font-size: 0.9rem; font-weight: 600; color: #f0f4ff; }
        .btn-ok {
            padding: 0.9rem 3rem;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            border-radius: 0.875rem;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-ok:hover { opacity: 0.9; transform: scale(1.03); }
        .countdown-bar-wrap {
            height: 4px;
            background: rgba(255,255,255,0.07);
            border-radius: 2px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .countdown-bar {
            height: 100%;
            background: #6366f1;
            transition: width 1s linear;
            border-radius: 2px;
        }
        .countdown-text { font-size: 0.72rem; color: #8899bb; margin-bottom: 0.75rem; }

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

                <!-- Card 1: Sudah Ada Janji (Check-In) -->
                <div class="checkin-card card-appointment" onclick="handleCheckin('appointment')" role="button" tabindex="0" aria-label="Check-in dengan janji temu">
                    <div class="card-icon-wrap">
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

                <!-- Card 2: Check-Out Mandiri -->
                <div class="checkin-card card-checkout" onclick="openCheckoutFaceScan()" role="button" tabindex="0" aria-label="Check-out mandiri via wajah">
                    <div class="card-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                            <path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0" opacity=".3"/>
                        </svg>
                    </div>

                    <div class="card-body">
                        <div class="card-title">Check-Out</div>
                        <div class="card-sub">Selesai berkunjung? Scan wajah<br>untuk check-out mandiri</div>
                    </div>

                    <div class="card-cta">
                        MULAI CHECK-OUT
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Tamu Baru / Walk-in -->
                <div class="checkin-card card-walkin" onclick="handleCheckin('walkin')" role="button" tabindex="0" aria-label="Registrasi tamu baru walk-in">
                    <div class="card-icon-wrap">
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
           CARD HANDLER — buka method picker
        ------------------------------------------------------- */
        function handleCheckin(type) {
            if (type === 'appointment') {
                openMethodPicker();
            } else {
                openWalkinForm();
            }
        }

        /* -------------------------------------------------------
           METHOD PICKER MODAL
        ------------------------------------------------------- */
        function openMethodPicker() {
            document.getElementById('modal-method').classList.add('active');
        }
        function closeMethodPicker() {
            document.getElementById('modal-method').classList.remove('active');
        }

        /* -------------------------------------------------------
           WALKIN REGISTRATION MODAL
        ------------------------------------------------------- */
        function openWalkinForm() {
            document.getElementById('modal-walkin').classList.add('active');
        }
        function closeWalkinForm() {
            document.getElementById('modal-walkin').classList.remove('active');
        }

        // Event listener for Walk-in success
        document.addEventListener('walkin-success', function (e) {
            closeWalkinForm();
            const data = e.detail;
            document.getElementById('co-modal-title').textContent = 'Registrasi Berhasil';
            document.getElementById('co-modal-sub').textContent = 'Silakan masuk dan melapor ke pos keamanan';
            document.getElementById('co-si-name').textContent = data.visitorName || '-';
            document.getElementById('co-si-company').textContent = '-';
            document.getElementById('co-si-pic').textContent = data.picName || '-';
            document.getElementById('co-si-time').textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('modal-checkout').classList.add('active');
            
            // Auto close success modal after 10 seconds
            setTimeout(() => {
                document.getElementById('modal-checkout').classList.remove('active');
            }, 10000);
        });

        /* -------------------------------------------------------
           FACE SCAN MODAL
        ------------------------------------------------------- */
        let faceScanStream     = null;
        let faceScanActive     = false;
        let livenessStep       = 'straight'; // straight -> right -> passed
        let consecutiveNoFace  = 0;
        let faceInPlace        = false;
        let ciPhotoSnapshot    = null; // foto wajah lurus sebelum liveness
        let ciPreparingPhoto   = false; // flag agar capture hanya sekali
        let scanCountdown      = null;

        async function openFaceScan() {
            closeMethodPicker();
            document.getElementById('modal-face').classList.add('active');
            setFaceMessage('Memuat Model AI...', 'info');

            // Load face-api if needed
            if (typeof faceapi === 'undefined') {
                await loadScript('/js/face-api.min.js?v=' + Date.now());
            }
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
            ]);

            const video = document.getElementById('ci-face-video');
            try {
                faceScanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 } } });
                video.srcObject = faceScanStream;
                video.onloadedmetadata = () => {
                    video.play();
                    document.getElementById('face-loading').style.display = 'none';
                    document.getElementById('face-camera-wrap').style.display = 'flex';
                    livenessStep = 'straight';
                    faceInPlace  = false;
                    // Reset grid to visible
                    const grid = document.getElementById('ci-face-grid');
                    if (grid) grid.style.opacity = '1';
                    setFaceMessage('Posisikan wajah di dalam lingkaran', 'info');
                    updateFaceRingColor('blue');
                    faceScanActive = true;
                    faceScanLoop(video);
                };
            } catch(e) {
                setFaceMessage('Kamera tidak dapat diakses. Gunakan HTTPS.', 'error');
            }
        }

        function closeFaceScan() {
            faceScanActive  = false;
            ciPhotoSnapshot  = null; // reset untuk scan berikutnya
            ciPreparingPhoto = false;
            if (faceScanStream) { faceScanStream.getTracks().forEach(t => t.stop()); faceScanStream = null; }
            document.getElementById('modal-face').classList.remove('active');
            document.getElementById('face-loading').style.display = 'flex';
            document.getElementById('face-camera-wrap').style.display = 'none';
        }

        function captureKioskPhoto(video) {
            try {
                const canvas = document.createElement('canvas');
                canvas.width  = video.videoWidth  || 320;
                canvas.height = video.videoHeight || 240;
                const ctx = canvas.getContext('2d');
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1); // mirror balik sesuai tampilan
                ctx.drawImage(video, 0, 0);
                return canvas.toDataURL('image/jpeg', 0.80);
            } catch(e) { console.warn('captureKioskPhoto failed:', e); return null; }
        }

        async function faceScanLoop(video) {
            if (!faceScanActive) return;
            try {
                const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks().withFaceDescriptor();

                updateFaceRingColor('blue');

                if (!det) {
                    consecutiveNoFace++;
                    faceInPlace = false;
                    updateFaceGrid(false);
                    if (consecutiveNoFace > 3) setFaceMessage('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => faceScanLoop(video), 200); return;
                }
                consecutiveNoFace = 0;

                const box = det.alignedRect.box;
                const ratio = box.width / video.videoWidth;
                const faceCX = box.x + box.width / 2;
                const faceCY = box.y + box.height / 2;
                const midX = video.videoWidth / 2;
                const midY = video.videoHeight / 2;
                const offX = Math.abs(faceCX - midX) / video.videoWidth;
                const offY = Math.abs(faceCY - midY) / video.videoHeight;

                if (ratio < 0.28) { faceInPlace=false; updateFaceGrid(false); updateFaceRingColor('red'); setFaceMessage('Wajah terlalu jauh — maju sedikit.', 'error'); setTimeout(()=>faceScanLoop(video),200); return; }
                if (ratio > 0.65) { faceInPlace=false; updateFaceGrid(false); updateFaceRingColor('red'); setFaceMessage('Wajah terlalu dekat — mundur sedikit.', 'error'); setTimeout(()=>faceScanLoop(video),200); return; }
                if (offX > 0.20 || offY > 0.20) {
                    faceInPlace=false; updateFaceGrid(false); updateFaceRingColor('red');
                    const dx = faceCX - midX, dy = faceCY - midY;
                    let hint = 'Posisikan wajah di tengah lingkaran';
                    if (Math.abs(dx)>Math.abs(dy)) hint = dx>0 ? 'Geser ke kiri ←':'Geser ke kanan →';
                    else hint = dy>0 ? 'Geser ke atas ↑':'Geser ke bawah ↓';
                    setFaceMessage(hint, 'error'); setTimeout(()=>faceScanLoop(video),200); return;
                }

                // In place!
                faceInPlace = true;
                updateFaceGrid(true);
                updateFaceRingColor('green');

                // Head yaw liveness
                if (livenessStep !== 'passed') {
                    const pts = det.landmarks.positions;
                    const noseRatio = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);
                    if (livenessStep === 'straight') {
                        if (!ciPhotoSnapshot) {
                            // Belum ada foto: tampilkan pesan diam, ambil diam-diam
                            if (!ciPreparingPhoto) {
                                ciPreparingPhoto = true;
                                setFaceMessage('Diam sebentar...', 'info');
                                setTimeout(() => {
                                    ciPhotoSnapshot  = captureKioskPhoto(document.getElementById('ci-face-video'));
                                    ciPreparingPhoto = false;
                                }, 800);
                            }
                            setTimeout(()=>faceScanLoop(video),100); return;
                        }
                        setFaceMessage('Tengok ke kanan ➡', 'info');
                        showArrow('right');
                        if (noseRatio < 0.38) livenessStep = 'right';
                    } else if (livenessStep === 'right') {
                        setFaceMessage('⬅ Sekarang tengok ke kiri', 'info');
                        showArrow('left');
                        if (noseRatio > 0.62) {
                            livenessStep = 'passed';
                            setFaceMessage('Verifikasi berhasil! Memproses...', 'success');
                            showArrow('none');
                            faceScanActive = false;
                            submitFaceDescriptor(det.descriptor);
                            return;
                        }
                    }
                    setTimeout(()=>faceScanLoop(video),100); return;
                }
            } catch(e) { console.error(e); setTimeout(()=>faceScanLoop(video),500); }
        }

        async function submitFaceDescriptor(descriptor) {
            try {
                const res = await fetch('/kiosk/face-checkin', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ descriptor: Array.from(descriptor), face_photo: ciPhotoSnapshot })
                });
                const data = await res.json();
                if (data.success) {
                    closeFaceScan();
                    showSuccessPopup(data.appointment);
                } else {
                    setFaceMessage(data.message || 'Wajah tidak dikenali.', 'error');
                    setTimeout(() => { faceScanActive=true; livenessStep='straight'; faceInPlace=false; faceScanLoop(document.getElementById('ci-face-video')); }, 3000);
                }
            } catch(e) {
                setFaceMessage('Koneksi gagal. Coba lagi.', 'error');
                setTimeout(() => { faceScanActive=true; livenessStep='straight'; faceInPlace=false; faceScanLoop(document.getElementById('ci-face-video')); }, 3000);
            }
        }

        function setFaceMessage(msg, type) {
            const el = document.getElementById('ci-face-msg');
            if (!el) return;
            el.textContent = msg;
            el.style.background = type==='error'?'#ef4444':type==='success'?'#10b981':'#6366f1';
        }

        function updateFaceRingColor(color) {
            const svg = document.getElementById('ci-ring-svg');
            if (!svg) return;
            const arc  = svg.querySelector('.ci-ring-arc');
            const base = svg.querySelector('.ci-ring-base');
            const bdr  = document.getElementById('ci-face-border');
            const colors = { red:'#ef4444', green:'#10b981', blue:'#6366f1' };
            const c = colors[color] || '#6366f1';
            if (arc)  arc.setAttribute('stroke', c);
            if (base) base.setAttribute('stroke', c + '33');
            if (bdr)  bdr.setAttribute('stroke', color==='red' ? '#ef4444' : '#818cf8');
        }

        function updateFaceGrid(hide) {
            const grid = document.getElementById('ci-face-grid');
            const ring = document.getElementById('ci-inner-ring');
            if (grid) grid.style.opacity = hide ? '0' : '1';
            if (ring) ring.style.boxShadow = hide ? 'inset 0 0 0 3px #10b981' : '';
        }

        function showArrow(dir) {
            const r = document.getElementById('ci-arrow-right');
            const l = document.getElementById('ci-arrow-left');
            if (r) r.style.display = dir==='right' ? 'flex':'none';
            if (l) l.style.display = dir==='left'  ? 'flex':'none';
        }

        function loadScript(src) {
            return new Promise((res,rej) => {
                const s = document.createElement('script');
                s.src = src; s.onload = res; s.onerror = rej;
                document.head.appendChild(s);
            });
        }

        /* -------------------------------------------------------
           SUCCESS POPUP
        ------------------------------------------------------- */
        let successTimer = null;
        let successSecondsLeft = 180;

        function showSuccessPopup(appt) {
            document.getElementById('si-name').textContent  = appt.visitor_name  || '-';
            document.getElementById('si-pic').textContent   = appt.pic_name      || '-';
            document.getElementById('si-room').textContent  = appt.room_name     || '-';
            document.getElementById('si-date').textContent  = appt.visit_date    || '-';
            document.getElementById('si-time').textContent  = appt.visit_time    || '-';
            document.getElementById('si-checkin').textContent = appt.checkin_time || '-';
            document.getElementById('si-purpose').textContent = appt.purpose      || '-';

            document.getElementById('modal-success').classList.add('active');

            successSecondsLeft = 180;
            updateCountdown();
            successTimer = setInterval(() => {
                successSecondsLeft--;
                updateCountdown();
                if (successSecondsLeft <= 0) closeSuccessPopup();
            }, 1000);
        }

        function updateCountdown() {
            const pct = (successSecondsLeft / 180) * 100;
            document.getElementById('countdown-bar').style.width = pct + '%';
            document.getElementById('countdown-text').textContent =
                'Layar akan kembali otomatis dalam ' + successSecondsLeft + ' detik';
        }

        function closeSuccessPopup() {
            clearInterval(successTimer);
            document.getElementById('modal-success').classList.remove('active');
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


    <!-- ===== MODAL 1: METHOD PICKER ===== -->
    <div id="modal-method" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeMethodPicker()">✕</button>
            <div class="modal-title">Sudah Ada Janji?</div>
            <p class="modal-sub">Pilih cara verifikasi janji temu Anda</p>
            <button class="method-btn" onclick="alert(&apos;Fitur Scan QR segera hadir!&apos;)">
                <div class="mb-icon" style="background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);">
                    <svg fill="none" stroke="#818cf8" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h.01M18 14h.01M14 18h.01M18 18h.01M16 16v.01"/></svg>
                </div>
                <div class="mb-text"><strong>Scan QR Code</strong><span>Arahkan QR Code tiket Anda ke kamera</span></div>
            </button>
            <button class="method-btn" onclick="alert(&apos;Fitur Input Token segera hadir!&apos;)">
                <div class="mb-icon" style="background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);">
                    <svg fill="none" stroke="#fbbf24" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                </div>
                <div class="mb-text"><strong>Masukkan Token</strong><span>Ketik kode token reservasi Anda</span></div>
            </button>
            <button class="method-btn" onclick="openFaceScan()">
                <div class="mb-icon" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);">
                    <svg fill="none" stroke="#10b981" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 20c0-4 4-7 9-7s9 3 9 7"/><path stroke-linecap="round" d="M7 3.5A9 9 0 0 0 3 11M17 3.5A9 9 0 0 1 21 11"/></svg>
                </div>
                <div class="mb-text"><strong>Scan Wajah</strong><span>Untuk tamu yang pernah check-in sebelumnya</span></div>
            </button>
        </div>
    </div>

    <!-- ===== MODAL: WALKIN FORM ===== -->
    <div id="modal-walkin" class="modal-overlay">
        <div class="modal-box" style="max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <button class="modal-close" onclick="closeWalkinForm()">✕</button>
            @livewire('kiosk-walkin-form')
        </div>
    </div>

    <!-- ===== MODAL 2: FACE SCAN ===== -->
    <div id="modal-face" class="modal-overlay">
        <div class="modal-box face-modal-box">
            <button class="modal-close" onclick="closeFaceScan()">✕</button>
            <div class="modal-title" style="margin-bottom:0.2rem;">Verifikasi Wajah</div>
            <p class="modal-sub" style="margin-bottom:1rem;">Tengok kanan lalu kiri untuk verifikasi</p>
            <div id="face-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;gap:1rem;">
                <svg style="width:2.5rem;height:2.5rem;color:#6366f1;animation:kp-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/></svg>
                <span style="color:#8899bb;font-size:0.85rem;">Memuat model AI...</span>
            </div>
            <div id="face-camera-wrap" style="display:none;flex-direction:column;align-items:center;gap:0.5rem;">
                <div style="position:relative;width:272px;height:272px;flex-shrink:0;">
                    <!-- Spinning ring -->
                    <svg id="ci-ring-svg" style="position:absolute;inset:0;width:100%;height:100%;z-index:4;pointer-events:none;" viewBox="0 0 272 272">
                        <circle class="ci-ring-base" cx="136" cy="136" r="126" fill="none" stroke-width="3" stroke="#6366f133"/>
                        <circle class="ci-ring-arc"  cx="136" cy="136" r="126" fill="none" stroke-width="5" stroke-linecap="round" stroke="#6366f1" stroke-dasharray="110 692" style="animation:kp-spin-ring 1.6s linear infinite;transform-origin:center;"/>
                    </svg>
                    <!-- Circular crop -->
                    <div style="position:absolute;inset:8px;border-radius:50%;overflow:hidden;background:#111;z-index:2;">
                        <video id="ci-face-video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block;"></video>
                        <!-- Face grid -->
                        <div id="ci-face-grid" style="position:absolute;inset:0;pointer-events:none;transition:opacity 0.5s;opacity:1;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;display:block;" viewBox="0 0 256 256" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    <path id="ci-face-shape" d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                                    <mask id="ci-face-mask">
                                        <rect width="256" height="256" fill="white"/>
                                        <use href="#ci-face-shape" fill="black"/>
                                    </mask>
                                </defs>
                                <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#ci-face-mask)"/>
                                <use id="ci-face-border" href="#ci-face-shape" fill="none" stroke="#818cf8" stroke-width="2.5" stroke-dasharray="10 6" style="animation:kp-pulse 1.4s ease-in-out infinite;"/>
                            </svg>
                        </div>
                        <!-- Inner success ring -->
                        <div id="ci-inner-ring" style="position:absolute;inset:0;border-radius:50%;pointer-events:none;transition:box-shadow 0.4s;"></div>
                    </div>
                </div>
                <!-- Message badge (OUTSIDE the circle) -->
                <div style="min-height:36px;display:flex;align-items:center;justify-content:center;padding:0 0.5rem;">
                    <span id="ci-face-msg" style="display:inline-block;padding:0.4rem 1.1rem;border-radius:999px;font-size:0.78rem;font-weight:600;color:#fff;text-align:center;max-width:260px;transition:background 0.3s;background:#6366f1;"></span>
                </div>
                <!-- Arrows (OUTSIDE the circle) -->
                <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;min-height:52px;">
                    <div id="ci-arrow-right" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-r 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KANAN</span>
                    </div>
                    <div id="ci-arrow-left" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-l 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KIRI</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL 3: SUCCESS POPUP ===== -->
    <div id="modal-success" class="modal-overlay">
        <div class="modal-box success-box">
            <div class="success-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:2.5rem;height:2.5rem;color:#10b981;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="success-heading">Check-in Berhasil! 🎉</div>
            <p class="success-sub">Selamat datang, data kunjungan Anda telah dicatat.</p>
            <div class="info-grid">
                <div class="info-item"><label>Nama Tamu</label><span id="si-name">-</span></div>
                <div class="info-item"><label>PIC / Host</label><span id="si-pic">-</span></div>
                <div class="info-item"><label>Ruangan</label><span id="si-room">-</span></div>
                <div class="info-item"><label>Tanggal Kunjungan</label><span id="si-date">-</span></div>
                <div class="info-item"><label>Jam Janji</label><span id="si-time">-</span></div>
                <div class="info-item"><label>Jam Check-in</label><span id="si-checkin">-</span></div>
                <div class="info-item" style="grid-column:1/-1;"><label>Keperluan</label><span id="si-purpose">-</span></div>
            </div>
            <div class="countdown-bar-wrap"><div id="countdown-bar" class="countdown-bar" style="width:100%;"></div></div>
            <p id="countdown-text" class="countdown-text"></p>
            <button class="btn-ok" onclick="closeSuccessPopup()">OK, Terima Kasih</button>
        </div>
    </div>
    

    <style>
        @keyframes kp-spin      { to { transform: rotate(360deg); } }
        @keyframes kp-spin-ring { to { stroke-dashoffset: -680; } }
        @keyframes kp-pulse     { 0%,100%{opacity:0.15;}50%{opacity:0.75;} }
        @keyframes kp-bounce-r  { 0%,100%{transform:translateX(0);}50%{transform:translateX(6px);} }
        @keyframes kp-bounce-l  { 0%,100%{transform:translateX(0);}50%{transform:translateX(-6px);} }
    </style>


    <!-- ===== MODAL: CHECKOUT FACE SCAN ===== -->
    <div id="modal-checkout-face" class="modal-overlay">
        <div class="modal-box face-modal-box">
            <button class="modal-close" onclick="closeCheckoutFaceScan()">✕</button>
            <div class="modal-title" style="margin-bottom:0.2rem;">Check-Out Mandiri</div>
            <p class="modal-sub" style="margin-bottom:1rem;">Scan wajah untuk check-out — tengok kanan lalu kiri</p>
            <div id="co-face-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;gap:1rem;">
                <svg style="width:2.5rem;height:2.5rem;color:#10b981;animation:kp-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/></svg>
                <span style="color:#8899bb;font-size:0.85rem;">Memuat model AI...</span>
            </div>
            <div id="co-face-camera-wrap" style="display:none;flex-direction:column;align-items:center;gap:0.5rem;">
                <div style="position:relative;width:272px;height:272px;flex-shrink:0;">
                    <!-- Spinning ring -->
                    <svg id="co-ring-svg" style="position:absolute;inset:0;width:100%;height:100%;z-index:4;pointer-events:none;" viewBox="0 0 272 272">
                        <circle class="co-ring-base" cx="136" cy="136" r="126" fill="none" stroke-width="3" stroke="#10b98133"/>
                        <circle class="co-ring-arc"  cx="136" cy="136" r="126" fill="none" stroke-width="5" stroke-linecap="round" stroke="#10b981" stroke-dasharray="110 692" style="animation:kp-spin-ring 1.6s linear infinite;transform-origin:center;"/>
                    </svg>
                    <!-- Circular crop -->
                    <div style="position:absolute;inset:8px;border-radius:50%;overflow:hidden;background:#111;z-index:2;">
                        <video id="co-face-video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block;"></video>
                        <!-- Face grid -->
                        <div id="co-face-grid" style="position:absolute;inset:0;pointer-events:none;transition:opacity 0.5s;opacity:1;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;display:block;" viewBox="0 0 256 256" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    <path id="co-face-shape" d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                                    <mask id="co-face-mask">
                                        <rect width="256" height="256" fill="white"/>
                                        <use href="#co-face-shape" fill="black"/>
                                    </mask>
                                </defs>
                                <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#co-face-mask)"/>
                                <use id="co-face-border" href="#co-face-shape" fill="none" stroke="#818cf8" stroke-width="2.5" stroke-dasharray="10 6" style="animation:kp-pulse 1.4s ease-in-out infinite;"/>
                            </svg>
                        </div>
                        <!-- Inner success ring -->
                        <div id="co-inner-ring" style="position:absolute;inset:0;border-radius:50%;pointer-events:none;transition:box-shadow 0.4s;"></div>
                    </div>
                </div>
                <!-- Message badge (OUTSIDE the circle) -->
                <div style="min-height:36px;display:flex;align-items:center;justify-content:center;padding:0 0.5rem;">
                    <span id="co-face-msg" style="display:inline-block;padding:0.4rem 1.1rem;border-radius:999px;font-size:0.78rem;font-weight:600;color:#fff;text-align:center;max-width:260px;transition:background 0.3s;background:#10b981;"></span>
                </div>
                <!-- Arrows (OUTSIDE the circle) -->
                <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;min-height:52px;">
                    <div id="co-arrow-right" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-r 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KANAN</span>
                    </div>
                    <div id="co-arrow-left" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-l 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#818cf8;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        <span style="font-size:0.68rem;color:#818cf8;font-weight:700;letter-spacing:.04em;">KIRI</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: CHECKOUT SUCCESS POPUP ===== -->
    <div id="modal-checkout-success" class="modal-overlay">
        <div class="modal-box success-box">
            <div class="success-icon" style="background:rgba(16,185,129,0.15);border-color:#10b981;">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:2.5rem;height:2.5rem;color:#10b981;"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7M13 3a9 9 0 1 1 0 18"/></svg>
            </div>
            <div class="success-heading">Check-Out Berhasil! 👋</div>
            <p class="success-sub">Terima kasih telah berkunjung. Sampai jumpa!</p>
            <div class="info-grid">
                <div class="info-item"><label>Nama Tamu</label><span id="co-si-name">-</span></div>
                <div class="info-item"><label>PIC / Host</label><span id="co-si-pic">-</span></div>
                <div class="info-item"><label>Ruangan</label><span id="co-si-room">-</span></div>
                <div class="info-item"><label>Tanggal</label><span id="co-si-date">-</span></div>
                <div class="info-item"><label>Jam Check-in</label><span id="co-si-checkin">-</span></div>
                <div class="info-item"><label>Jam Check-out</label><span id="co-si-checkout">-</span></div>
            </div>
            <div class="countdown-bar-wrap"><div id="co-countdown-bar" class="countdown-bar" style="width:100%;background:#10b981;"></div></div>
            <p id="co-countdown-text" class="countdown-text"></p>
            <button class="btn-ok" onclick="closeCheckoutSuccess()" style="background:linear-gradient(135deg,#10b981,#059669);">OK, Selesai</button>
        </div>
    </div>

<script>
        /* -------------------------------------------------------
           CHECKOUT FACE SCAN
        ------------------------------------------------------- */
        let coScanStream    = null;
        let coScanActive    = false;
        let coLivenessStep  = 'straight';
        let coNoFace        = 0;
        let coFaceInPlace   = false;

        async function openCheckoutFaceScan() {
            document.getElementById('modal-checkout-face').classList.add('active');
            setCoMsg('Memuat Model AI...', 'info');

            if (typeof faceapi === 'undefined') {
                await loadScript('/js/face-api.min.js?v=' + Date.now());
            }
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
            ]);

            const video = document.getElementById('co-face-video');
            try {
                coScanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 } } });
                video.srcObject = coScanStream;
                video.onloadedmetadata = () => {
                    video.play();
                    document.getElementById('co-face-loading').style.display = 'none';
                    document.getElementById('co-face-camera-wrap').style.display = 'flex';
                    coLivenessStep = 'straight'; coFaceInPlace = false; coScanActive = true;
                    coScanLoop(video);
                };
            } catch(e) { setCoMsg('Kamera tidak dapat diakses.', 'error'); }
        }

        function closeCheckoutFaceScan() {
            coScanActive = false;
            if (coScanStream) { coScanStream.getTracks().forEach(t => t.stop()); coScanStream = null; }
            document.getElementById('modal-checkout-face').classList.remove('active');
            document.getElementById('co-face-loading').style.display = 'flex';
            document.getElementById('co-face-camera-wrap').style.display = 'none';
        }

        async function coScanLoop(video) {
            if (!coScanActive) return;
            try {
                const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks().withFaceDescriptor();
                updateCoRing('green');
                if (!det) {
                    coNoFace++; coFaceInPlace = false;
                    coGridVisible(false);
                    if (coNoFace > 3) setCoMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => coScanLoop(video), 200); return;
                }
                coNoFace = 0;
                const box = det.alignedRect.box;
                const ratio = box.width / video.videoWidth;
                const offX = Math.abs((box.x + box.width/2) - video.videoWidth/2) / video.videoWidth;
                const offY = Math.abs((box.y + box.height/2) - video.videoHeight/2) / video.videoHeight;
                if (ratio < 0.28) { coFaceInPlace=false; coGridVisible(false); updateCoRing('red'); setCoMsg('Terlalu jauh — maju sedikit.', 'error'); setTimeout(()=>coScanLoop(video),200); return; }
                if (ratio > 0.65) { coFaceInPlace=false; coGridVisible(false); updateCoRing('red'); setCoMsg('Terlalu dekat — mundur sedikit.', 'error'); setTimeout(()=>coScanLoop(video),200); return; }
                if (offX > 0.20 || offY > 0.20) {
                    coFaceInPlace=false; coGridVisible(false); updateCoRing('red');
                    setCoMsg('Posisikan wajah di tengah lingkaran.', 'error');
                    setTimeout(()=>coScanLoop(video),200); return;
                }
                coFaceInPlace = true; coGridVisible(true); updateCoRing('green');
                if (coLivenessStep !== 'passed') {
                    const pts = det.landmarks.positions;
                    const nr = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);
                    if (coLivenessStep === 'straight') {
                        setCoMsg('Tengok ke kanan ➡', 'info'); coShowArrow('right');
                        if (nr < 0.38) coLivenessStep = 'right';
                    } else if (coLivenessStep === 'right') {
                        setCoMsg('⬅ Tengok ke kiri', 'info'); coShowArrow('left');
                        if (nr > 0.62) {
                            coLivenessStep = 'passed';
                            setCoMsg('Verifikasi OK! Memproses...', 'success'); coShowArrow('none');
                            coScanActive = false; submitCoDescriptor(det.descriptor); return;
                        }
                    }
                    setTimeout(()=>coScanLoop(video),100); return;
                }
            } catch(e) { console.error(e); setTimeout(()=>coScanLoop(video),500); }
        }

        async function submitCoDescriptor(descriptor) {
            try {
                const res = await fetch('/kiosk/face-checkout', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ descriptor: Array.from(descriptor) })
                });
                const data = await res.json();
                if (data.success) {
                    closeCheckoutFaceScan();
                    showCoSuccess(data.data);
                } else {
                    setCoMsg(data.message || 'Tidak ditemukan sesi aktif.', 'error');
                    setTimeout(() => { coScanActive=true; coLivenessStep='straight'; coFaceInPlace=false; coScanLoop(document.getElementById('co-face-video')); }, 3000);
                }
            } catch(e) {
                setCoMsg('Koneksi gagal. Coba lagi.', 'error');
                setTimeout(() => { coScanActive=true; coLivenessStep='straight'; coFaceInPlace=false; coScanLoop(document.getElementById('co-face-video')); }, 3000);
            }
        }

        let coTimer = null, coSecs = 60;
        function showCoSuccess(d) {
            document.getElementById('co-si-name').textContent    = d.visitor_name  || '-';
            document.getElementById('co-si-pic').textContent     = d.pic_name      || '-';
            document.getElementById('co-si-room').textContent    = d.room_name     || '-';
            document.getElementById('co-si-date').textContent    = d.visit_date    || '-';
            document.getElementById('co-si-checkin').textContent  = d.checkin_time  || '-';
            document.getElementById('co-si-checkout').textContent = d.checkout_time || '-';
            document.getElementById('modal-checkout-success').classList.add('active');
            coSecs = 60; updateCoCountdown();
            coTimer = setInterval(() => { coSecs--; updateCoCountdown(); if(coSecs<=0) closeCheckoutSuccess(); }, 1000);
        }
        function updateCoCountdown() {
            document.getElementById('co-countdown-bar').style.width = (coSecs/60*100)+'%';
            document.getElementById('co-countdown-text').textContent = 'Layar kembali otomatis dalam '+coSecs+' detik';
        }
        function closeCheckoutSuccess() {
            clearInterval(coTimer);
            document.getElementById('modal-checkout-success').classList.remove('active');
        }

        function setCoMsg(msg, type) {
            const el = document.getElementById('co-face-msg'); if(!el) return;
            el.textContent = msg;
            el.style.background = type==='error'?'#ef4444':type==='success'?'#10b981':'#10b981';
        }
        function updateCoRing(color) {
            const map = {red:'#ef4444',green:'#10b981',blue:'#6366f1'};
            const c = map[color] || '#10b981';
            const arc  = document.querySelector('#co-ring-svg .co-ring-arc');
            const base = document.querySelector('#co-ring-svg .co-ring-base');
            const bdr  = document.getElementById('co-face-border');
            const ring = document.getElementById('co-inner-ring');
            if(arc)  arc.setAttribute('stroke', c);
            if(base) base.setAttribute('stroke', c + '33');
            if(bdr)  bdr.setAttribute('stroke', color==='red' ? '#ef4444' : '#34d399');
            if(ring) ring.style.boxShadow = color==='green' ? 'inset 0 0 0 3px #10b981' : '';
        }
        function coGridVisible(hide) {
            const g = document.getElementById('co-face-grid'); if(g) g.style.opacity = hide?'0':'1';
        }
        function coShowArrow(dir) {
            document.getElementById('co-arrow-right').style.display = dir==='right'?'flex':'none';
            document.getElementById('co-arrow-left').style.display  = dir==='left' ?'flex':'none';
        }

</script>

@livewire('interactive-chatbot')
</body>
</html>