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
            --bg-void:        #f8fafc; /* Slate 50 */
            --bg-deep:        #f1f5f9; /* Slate 100 */
            --bg-surface:     #ffffff;
            --bg-card:        #ffffff;
            --bg-card-hover:  #f8fafc;

            --accent-primary: #4f46e5;   /* indigo 600 */
            --accent-glow:    #6366f1;   /* indigo 500 */
            --accent-rose:    #e11d48;   /* rose 600 */
            --accent-gold:    #d97706;   /* amber 600 */

            --text-primary:   #0f172a;   /* Slate 900 */
            --text-secondary: #475569;   /* Slate 600 */
            --text-muted:     #94a3b8;   /* Slate 400 */

            --border-subtle:  rgba(79, 70, 229, 0.08);
            --border-card:    rgba(79, 70, 229, 0.12);
            --border-glow:    rgba(79, 70, 229, 0.3);

            --shadow-card:    0 10px 30px -10px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.03);
            --shadow-glow:    0 0 40px rgba(79, 70, 229, 0.08);
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
            z-index: 2.5; /* Floats over avatar background */
            pointer-events: none;
        }

        .bg-mesh {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                radial-gradient(ellipse 80% 50% at 15% 20%, rgba(99, 102, 241, 0.04) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 85% 75%, rgba(244, 63, 94, 0.03) 0%, transparent 55%);
        }

        /* Large Centered Looping Video Avatar Backdrop */
        .kiosk-bg-video-container {
            position: fixed;
            inset: 0;
            z-index: 1.5; /* Above mesh/particles, behind text/buttons */
            pointer-events: none;
            display: flex;
            align-items: flex-end; /* Align the video to the bottom edge */
            justify-content: center;
            overflow: hidden;
            background: #ffffff;
            mix-blend-mode: multiply;
        }

        .kiosk-bg-video {
            height: 108vh; /* Slightly taller than the screen */
            width: auto;
            max-width: 100vw;
            object-fit: contain;
            opacity: 0.96;
            margin-bottom: -8vh; /* Push the cut-off chest/shoulders below the screen */
            filter: brightness(1.12) contrast(1.1); /* Aggressively pushes near-white pixels to pure white for perfect multiply blending */
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
                rgba(99, 102, 241, 0.003) 3px,
                rgba(99, 102, 241, 0.003) 4px
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
            display: none;
        }

        /* ============================================================
           MAIN
        ============================================================ */
        .kiosk-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 1vh;
            padding: 1vh 0 0.5vh;
            min-height: 0;
            overflow: hidden;
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
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            width: 100%;
            max-width: 1200px;
            margin-top: auto;
            padding-bottom: 1.5vh;
            z-index: 10;
        }

        .checkin-card {
            flex: 1;
            min-width: 220px;
            max-width: 270px;
            min-height: 80px;
            height: 85px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 70, 229, 0.1);
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition:
                transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                background 0.25s ease;
            box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
        }

        /* Card shimmer stripe */
        .checkin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -60%;
            width: 40%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
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
            width: 80px;
            height: 80px;
            border-radius: 0 1rem 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card-appointment::after {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 70%);
        }

        .card-walkin::after {
            background: radial-gradient(circle at top right, rgba(244, 63, 94, 0.15), transparent 70%);
        }

        .checkin-card:hover::after {
            opacity: 1;
        }

        /* Hover states */
        .card-appointment:hover {
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: var(--shadow-card), 0 0 30px rgba(99, 102, 241, 0.1);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px) scale(1.02);
        }

        .card-walkin:hover {
            border-color: rgba(244, 63, 94, 0.4);
            box-shadow: var(--shadow-card), 0 0 30px rgba(244, 63, 94, 0.1);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px) scale(1.02);
        }

        /* Active / Touch press */
        .checkin-card:active {
            transform: scale(0.97) !important;
            transition: transform 0.08s ease;
        }

        /* Card icon bubble */
        .card-icon-wrap {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-appointment .card-icon-wrap {
            background: #e0e7ff;
            border: 1px solid rgba(99, 102, 241, 0.2);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.05);
        }

        .card-walkin .card-icon-wrap {
            background: #ffe4e6;
            border: 1px solid rgba(244, 63, 94, 0.2);
            box-shadow: 0 4px 10px rgba(244, 63, 94, 0.05);
        }

        .card-icon-wrap svg {
            width: 1.4rem;
            height: 1.4rem;
        }

        .card-appointment .card-icon-wrap svg { color: var(--accent-glow); }
        .card-walkin    .card-icon-wrap svg { color: #fb7185; }

        /* Card text */
        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .card-title {
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-primary);
            line-height: 1.25;
        }

        .card-sub {
            margin-top: 0.15rem;
            font-size: 0.7rem;
            font-weight: 400;
            color: var(--text-secondary);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Card CTA arrow */
        .card-cta {
            display: none !important;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        .kiosk-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 1.2vh 0 0.5vh;
            margin-bottom: 2.5rem; /* Prevents collision with fixed status bar */
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
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.15), transparent 70%);
        }
        .card-checkout:hover {
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: var(--shadow-card), 0 0 30px rgba(16, 185, 129, 0.1);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px) scale(1.02);
        }
        .card-checkout .card-icon-wrap {
            background: #d1fae5;
            border: 1px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.05);
        }
        .card-checkout .card-icon-wrap svg { color: #10b981; }
        .card-checkout .ripple { background: rgba(16, 185, 129, 0.1); }

        .card-attendance::after {
            background: radial-gradient(circle at top right, rgba(251, 191, 36, 0.15), transparent 70%);
        }
        .card-attendance:hover {
            border-color: rgba(251, 191, 36, 0.4);
            box-shadow: var(--shadow-card), 0 0 30px rgba(251, 191, 36, 0.1);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px) scale(1.02);
        }
        .card-attendance .card-icon-wrap {
            background: #fef3c7;
            border: 1px solid rgba(251, 191, 36, 0.2);
            box-shadow: 0 4px 10px rgba(251, 191, 36, 0.05);
        }
        .card-attendance .card-icon-wrap svg { color: var(--accent-gold); }
        .card-attendance .ripple { background: rgba(251, 191, 36, 0.1); }


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
        .checkin-card:nth-child(3) { animation: fadeUp 0.6s ease both; animation-delay: 0.61s; }
        .checkin-card:nth-child(4) { animation: fadeUp 0.6s ease both; animation-delay: 0.74s; }
        .kiosk-footer   { animation: fadeUp 0.6s ease both; animation-delay: 0.85s; }

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
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            padding: 2rem;
            width: min(92vw, 480px);
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            animation: fadeUp 0.3s ease both;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }
        .modal-sub {
            font-size: 0.82rem;
            color: #475569;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            position: absolute;
            top: 1rem; right: 1rem;
            background: #f1f5f9;
            border: none;
            color: #475569;
            border-radius: 0.5rem;
            width: 2rem; height: 2rem;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .modal-close:hover { background: #e2e8f0; color: #0f172a; }

        /* Method picker buttons */
        .method-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-bottom: 0.75rem;
            font-family: 'Poppins', sans-serif;
            text-align: left;
        }
        .method-btn:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
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
        .method-btn .mb-text span  { font-size: 0.75rem; color: #475569; }
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
        .success-heading { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 0.3rem; }
        .success-sub     { font-size: 0.85rem; color: #475569; margin-bottom: 1.5rem; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
        }
        .info-item label { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: #4f46e5; display: block; margin-bottom: 0.2rem; }
        .info-item span  { font-size: 0.9rem; font-weight: 600; color: #0f172a; }
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

        /* Waiting approval modal */
        .waiting-box {
            width: min(94vw, 440px);
            text-align: center;
            padding: 2.5rem 2rem;
        }
        .waiting-icon {
            width: 6rem; height: 6rem;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            border: 2px solid rgba(99, 102, 241, 0.4);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
        }
        .waiting-icon::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--accent-primary);
            animation: waiting-spin 1.2s linear infinite;
        }
        @keyframes waiting-spin {
            to { transform: rotate(360deg); }
        }
        .waiting-icon svg {
            width: 2.5rem; height: 2.5rem;
            color: var(--accent-glow);
            animation: waiting-pulse 2s ease-in-out infinite;
        }
        @keyframes waiting-pulse {
            0%, 100% { opacity: 0.6; transform: scale(0.9); }
            50%      { opacity: 1;   transform: scale(1.05); }
        }
        .waiting-heading {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .waiting-sub {
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.4rem;
            line-height: 1.5;
        }
        .waiting-timer {
            font-size: 0.75rem;
            color: #6366f1;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .waiting-dots::after {
            content: '';
            animation: waiting-dots 1.5s steps(4, end) infinite;
        }
        @keyframes waiting-dots {
            0%  { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }
        .btn-cancel-waiting {
            padding: 0.7rem 2rem;
            background: transparent;
            border: 1px solid rgba(244, 63, 94, 0.4);
            border-radius: 0.75rem;
            color: #f43f5e;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel-waiting:hover {
            background: rgba(244, 63, 94, 0.1);
            border-color: rgba(244, 63, 94, 0.6);
        }

        /* Rejected overlay */
        .rejected-box {
            width: min(94vw, 440px);
            text-align: center;
            padding: 2.5rem 2rem;
        }
        .rejected-icon {
            width: 5rem; height: 5rem;
            border-radius: 50%;
            background: rgba(244, 63, 94, 0.15);
            border: 2px solid #f43f5e;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .rejected-icon svg { width: 2.5rem; height: 2.5rem; color: #f43f5e; }
        .rejected-heading { font-size: 1.4rem; font-weight: 700; color: #f43f5e; margin-bottom: 0.4rem; }
        .rejected-sub { font-size: 0.85rem; color: #8899bb; margin-bottom: 1.5rem; line-height: 1.5; }

    </style>
    @livewireStyles
</head>
<body>

    <!-- Background layers -->
    <canvas id="particle-canvas"></canvas>
    <div class="bg-mesh"></div>
    <div class="kiosk-bg-video-container">
        <video autoplay loop muted playsinline class="kiosk-bg-video">
            <source src="{{ asset('assets/images/chatbot/avatar-greeting.mp4') }}" type="video/mp4">
        </video>
    </div>
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
                    @if(\App\Helpers\KioskHelper::isKioskLocal())
                        <div class="logo-tagline">Enterprise Visitor Management • <span id="kiosk-location-display" style="font-weight: 700; color: var(--accent-primary);">SA</span></div>
                    @else
                        <div class="logo-tagline">Enterprise Visitor Management • <span style="font-weight: 700; color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.75rem;">Offsite (Terbatas)</span></div>
                    @endif
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

            @livewire('interactive-chatbot')

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

                <!-- Card 4: Absensi Karyawan (PIC) -->
                <div class="checkin-card card-attendance" onclick="openAttendanceModal()" role="button" tabindex="0" aria-label="Absensi Karyawan">
                    <div class="card-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                    </div>

                    <div class="card-body">
                        <div class="card-title">Absensi Karyawan</div>
                        <div class="card-sub">Khusus karyawan (PIC) untuk<br>check-in & check-out wajah</div>
                    </div>

                    <div class="card-cta">
                        ABSENSI PIC
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

    {{-- PIC Attendance (invisible event listener) --}}
    @livewire('kiosk.pic-attendance')

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
            Livewire.dispatch('resetWalkinForm');
        }

        // Event listener for Walk-in success
        document.addEventListener('walkin-success', function (e) {
            closeWalkinForm();
            const data = e.detail.appt;
            document.querySelector('#modal-success .success-heading').textContent = 'Registrasi Berhasil! 🎉';
            document.querySelector('#modal-success .success-sub').textContent = 'Silakan masuk dan melapor ke pos keamanan';
            document.getElementById('si-name').textContent = data.visitorName || '-';
            document.getElementById('si-company').textContent = data.company || '-';
            document.getElementById('si-phone').textContent = data.phone || '-';
            document.getElementById('si-pic').textContent = data.picName || '-';
            document.getElementById('si-department').textContent = data.department || '-';
            document.getElementById('si-date').textContent = data.visit_date || '-';
            document.getElementById('si-time').textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('si-purpose').textContent = data.purpose || '-';
            
            document.getElementById('modal-success').classList.add('active');
            
            successSecondsLeft = 5;
            updateCountdown();
            clearInterval(successTimer);
            successTimer = setInterval(() => {
                successSecondsLeft--;
                updateCountdown();
                if (successSecondsLeft <= 0) closeSuccessPopup();
            }, 1000);
        });

        // Polling state variables for PIC approval
        let approvalPollInterval = null;
        let approvalTimerInterval = null;
        let approvalSecondsElapsed = 0;

        // Event listener for Walk-in pending approval
        document.addEventListener('walkin-pending-approval', function (e) {
            closeWalkinForm();
            
            const data = e.detail;
            const token = data.token;
            const visitorName = data.visitorName;
            const picName = data.picName;

            // Show intermediate success modal first
            document.querySelector('#modal-success .success-heading').textContent = 'Verifikasi Wajah Berhasil! 🎉';
            document.querySelector('#modal-success .success-sub').textContent = 'Data Anda telah tercatat. Melanjutkan ke proses persetujuan...';
            document.getElementById('si-name').textContent = visitorName || '-';
            document.getElementById('si-company').textContent = data.company || '-';
            document.getElementById('si-phone').textContent = data.phone || '-';
            document.getElementById('si-pic').textContent = picName || '-';
            document.getElementById('si-department').textContent = data.department || '-';
            document.getElementById('si-date').textContent = data.visit_date || new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            document.getElementById('si-time').textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('si-purpose').textContent = 'Bertamu Sekarang (Walk-In)';
            
            // Hide action bar for intermediate step
            document.querySelector('#modal-success .countdown-bar-wrap').style.display = 'none';
            document.getElementById('countdown-text').style.display = 'none';
            const okBtn = document.querySelector('#modal-success .btn-ok');
            if(okBtn) okBtn.style.display = 'none';

            document.getElementById('modal-success').classList.add('active');

            // Wait 3 seconds, then switch to waiting approval modal
            setTimeout(() => {
                document.getElementById('modal-success').classList.remove('active');
                
                // Restore elements for future use
                document.querySelector('#modal-success .countdown-bar-wrap').style.display = 'block';
                document.getElementById('countdown-text').style.display = 'block';
                if(okBtn) okBtn.style.display = 'inline-block';

                startWaitingApproval(token, picName, data);
            }, 3000);
        });

        function startWaitingApproval(token, picName, detailData) {
            document.getElementById('wa-pic-name').textContent = picName || 'PIC';
            document.getElementById('wa-timer').textContent = 'Menunggu respon (0 detik)...';
            document.getElementById('modal-waiting-approval').classList.add('active');

            approvalSecondsElapsed = 0;
            clearInterval(approvalPollInterval);
            clearInterval(approvalTimerInterval);

            // Timer display
            approvalTimerInterval = setInterval(() => {
                approvalSecondsElapsed++;
                document.getElementById('wa-timer').textContent = `Menunggu respon (${approvalSecondsElapsed} detik)...`;
            }, 1000);

            // Poll appointment approval status
            approvalPollInterval = setInterval(() => {
                fetch(`/appointments/status/${token}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Status check failed');
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'active') {
                            stopApprovalPolling();
                            document.getElementById('modal-waiting-approval').classList.remove('active');

                            // Show standard success modal
                            document.querySelector('#modal-success .success-heading').textContent = 'Kunjungan Disetujui! 🎉';
                            document.querySelector('#modal-success .success-sub').textContent = 'Silakan masuk dan melapor ke pos keamanan.';
                            document.getElementById('si-name').textContent = detailData.visitorName || '-';
                            document.getElementById('si-company').textContent = detailData.company || '-';
                            document.getElementById('si-phone').textContent = detailData.phone || '-';
                            document.getElementById('si-pic').textContent = picName || '-';
                            document.getElementById('si-department').textContent = detailData.department || '-';
                            document.getElementById('si-date').textContent = detailData.visit_date || new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                            document.getElementById('si-time').textContent = data.check_in_time || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                            document.getElementById('si-purpose').textContent = 'Walk-in (Disetujui)';

                            document.getElementById('modal-success').classList.add('active');

                            successSecondsLeft = 5;
                            updateCountdown();
                            clearInterval(successTimer);
                            successTimer = setInterval(() => {
                                successSecondsLeft--;
                                updateCountdown();
                                if (successSecondsLeft <= 0) closeSuccessPopup();
                            }, 1000);
                        } else if (data.status === 'rejected') {
                            stopApprovalPolling();
                            document.getElementById('modal-waiting-approval').classList.remove('active');

                            // Show rejected modal
                            document.getElementById('modal-rejected').classList.add('active');

                            let rejectedSecondsLeft = 5;
                            const updateRejectedCountdown = () => {
                                const pct = (rejectedSecondsLeft / 5) * 100;
                                document.getElementById('rejected-countdown-bar').style.width = pct + '%';
                                document.getElementById('rejected-countdown-text').textContent =
                                    'Layar akan kembali otomatis dalam ' + rejectedSecondsLeft + ' detik';
                            };

                            updateRejectedCountdown();
                            clearInterval(successTimer);
                            successTimer = setInterval(() => {
                                rejectedSecondsLeft--;
                                updateRejectedCountdown();
                                if (rejectedSecondsLeft <= 0) closeRejectedPopup();
                            }, 1000);
                        }
                    })
                    .catch(err => console.error('Error polling status:', err));
            }, 3000);
        }

        function stopApprovalPolling() {
            clearInterval(approvalPollInterval);
            clearInterval(approvalTimerInterval);
        }

        function cancelWaitingApproval() {
            stopApprovalPolling();
            document.getElementById('modal-waiting-approval').classList.remove('active');
            openWalkinForm();
        }

        function closeRejectedPopup() {
            clearInterval(successTimer);
            document.getElementById('modal-rejected').classList.remove('active');
        }


        // Event listener for Appointment scheduling success
        document.addEventListener('appointment-success', function (e) {
            closeWalkinForm();
            const data = e.detail.appt;
            document.querySelector('#modal-success .success-heading').textContent = 'Janji Temu Dibuat 📅';
            document.querySelector('#modal-success .success-sub').textContent = 'Menunggu konfirmasi dari karyawan. Token/Tiket akan dikirimkan ke Whatsapp Anda.';
            document.getElementById('si-name').textContent = data.visitorName || '-';
            document.getElementById('si-company').textContent = data.company || '-';
            document.getElementById('si-phone').textContent = data.phone || '-';
            document.getElementById('si-pic').textContent = data.picName || '-';
            document.getElementById('si-department').textContent = data.department || '-';
            document.getElementById('si-date').textContent = data.visit_date || '-';
            document.getElementById('si-time').textContent = data.visit_time || '-';
            document.getElementById('si-purpose').textContent = data.purpose || '-';
            
            document.getElementById('modal-success').classList.add('active');
            
            successSecondsLeft = 5;
            updateCountdown();
            clearInterval(successTimer);
            successTimer = setInterval(() => {
                successSecondsLeft--;
                updateCountdown();
                if (successSecondsLeft <= 0) closeSuccessPopup();
            }, 1000);
        });

        // Event listeners for Livewire trigger events
        document.addEventListener('trigger-face-scan', function () {
            openFaceScan('walkin');
        });

        document.addEventListener('trigger-face-search', function () {
            openFaceScan('walkin-search');
        });

        document.addEventListener('walkin-error', function () {
            closeFaceScan();
            openWalkinForm(); // Kembali ke form walkin untuk melihat error
        });

        document.addEventListener('walkin-form-reopen', function () {
            closeFaceScan();
            openWalkinForm();
        });

        /* -------------------------------------------------------
           FACE SCAN MODAL
        ------------------------------------------------------- */
        let faceScanStream     = null;
        let faceScanActive     = false;
        let ciLandmarkRAF      = null;  // fast landmark loop handle
        let livenessStep       = 'straight'; // straight -> right -> passed
        let consecutiveNoFace  = 0;
        let faceInPlace        = false;
        let ciPhotoSnapshot    = null;
        let ciPreparingPhoto   = false;
        let earPrevOpen        = true;    // blink detection: were eyes open?
        let blinkDetected      = false;   // blink detection: completed?
        let scanCountdown      = null;
        let faceScanMode       = 'checkin'; // 'checkin' atau 'walkin'

        async function openFaceScan(mode = 'checkin') {
            faceScanMode = mode;
            if (mode === 'checkin') {
                closeMethodPicker();
            } else if (mode === 'walkin' || mode === 'walkin-search') {
                document.getElementById('modal-walkin').classList.remove('active');
            }
            document.getElementById('modal-face').classList.add('active');
            setFaceMessage('Memuat Model AI...', 'info');

            // Show skip/fallback button if we are doing a walk-in registration
            const skipBtn = document.getElementById('btn-skip-face');
            if (skipBtn) {
                skipBtn.style.display = (mode === 'walkin') ? 'inline-block' : 'none';
            }

            // Load face-api if needed
            if (typeof faceapi === 'undefined') {
                await loadScript('/js/face-api.min.js');
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
                    earPrevOpen  = true;
                    blinkDetected = false;
                    // Reset grid to visible
                    const grid = document.getElementById('ci-face-grid');
                    if (grid) grid.style.opacity = '1';
                    setFaceMessage('Posisikan wajah di dalam lingkaran', 'info');
                    updateFaceRingColor('blue');
                    faceScanActive = true;
                    ciLandmarkLoop(video);  // fast landmark rendering
                    faceScanLoop(video);    // slower full detection
                };
            } catch(e) {
                setFaceMessage('Kamera tidak dapat diakses. Gunakan HTTPS.', 'error');
                // Hide loading overlay so the user can see the skip/fallback button
                document.getElementById('face-loading').style.display = 'none';
            }
        }

        function closeFaceScan(isSkip = false) {
            faceScanActive   = false;
            ciPhotoSnapshot  = null;
            ciPreparingPhoto = false;
            if (ciLandmarkRAF) { cancelAnimationFrame(ciLandmarkRAF); ciLandmarkRAF = null; }
            if (faceScanStream) { faceScanStream.getTracks().forEach(t => t.stop()); faceScanStream = null; }
            clearKioskLandmarks('ci-landmark-canvas');
            document.getElementById('modal-face').classList.remove('active');
            document.getElementById('face-loading').style.display = 'flex';
            document.getElementById('face-camera-wrap').style.display = 'none';

            // FALLBACK: Reopen Walk-in Form if we came from Walk-in and it was NOT a skipped submission
            if (!isSkip && (faceScanMode === 'walkin' || faceScanMode === 'walkin-search')) {
                openWalkinForm();
            }
        }

        function skipFaceScan() {
            closeFaceScan(true); // Close face scan without reopening walk-in form
            Livewire.dispatch('submitWithoutFace');
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

        /* Eye Aspect Ratio (EAR) — used for Blink Detection / Liveness */
        function calcEAR(pts, idx) {
            const p = idx.map(i => pts[i]);
            const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
            // EAR = (|p1-p5| + |p2-p4|) / (2 * |p0-p3|)
            return (dist(p[1], p[5]) + dist(p[2], p[4])) / (2 * dist(p[0], p[3]));
        }

        /* FAST landmark-only loop (requestAnimationFrame, no descriptor) */
        async function ciLandmarkLoop(video) {
            if (!faceScanActive) return;
            try {
                const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.4 }))
                    .withFaceLandmarks();
                if (det) drawKioskLandmarks(det.landmarks.positions, video, 'ci-landmark-canvas');
                else     clearKioskLandmarks('ci-landmark-canvas');
            } catch(e) {}
            if (faceScanActive) ciLandmarkRAF = requestAnimationFrame(() => ciLandmarkLoop(video));
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
                    clearKioskLandmarks('ci-landmark-canvas');
                    if (consecutiveNoFace > 3) setFaceMessage('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => faceScanLoop(video), 200); return;
                }
                consecutiveNoFace = 0;

                // Draw real landmarks
                drawKioskLandmarks(det.landmarks.positions, video, 'ci-landmark-canvas');

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
                            livenessStep = 'blink';
                            earPrevOpen = true;
                            blinkDetected = false;
                            showArrow('none');
                        }
                    } else if (livenessStep === 'blink') {
                        showArrow('none');
                        setFaceMessage('Berkedip sekali 👁️', 'info');
                        const leftEAR  = calcEAR(pts, [36,37,38,39,40,41]);
                        const rightEAR = calcEAR(pts, [42,43,44,45,46,47]);
                        const avgEAR   = (leftEAR + rightEAR) / 2;
                        if (earPrevOpen && avgEAR < 0.21) {
                            earPrevOpen = false; // mata baru saja menutup
                        } else if (!earPrevOpen && avgEAR > 0.25) {
                            blinkDetected = true; // mata terbuka lagi = kedipan terdeteksi!
                        }
                        if (blinkDetected) {
                            livenessStep = 'passed';
                            setFaceMessage('Verifikasi berhasil! Memproses...', 'success');
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
            if (faceScanMode === 'walkin') {
                // Return data to Livewire component
                closeFaceScan();
                Livewire.dispatch('finalizeWalkin', { 
                    descriptor: Array.from(descriptor), 
                    photoBase64: ciPhotoSnapshot 
                });
                return;
            }

            if (faceScanMode === 'walkin-search') {
                // Return data to Livewire component to search for visitor
                closeFaceScan();
                Livewire.dispatch('findVisitorByFace', { 
                    descriptor: Array.from(descriptor) 
                });
                return;
            }

            // Normal check-in flow via API
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
        let successMaxSeconds = 180;

        function showSuccessPopup(appt) {
            document.getElementById('si-name').textContent  = appt.visitor_name  || '-';
            document.getElementById('si-pic').textContent   = appt.pic_name      || '-';
            document.getElementById('si-room').textContent  = appt.room_name     || '-';
            document.getElementById('si-date').textContent  = appt.visit_date    || '-';
            document.getElementById('si-time').textContent  = appt.visit_time    || '-';
            document.getElementById('si-checkin').textContent = appt.checkin_time || '-';
            document.getElementById('si-purpose').textContent = appt.purpose      || '-';

            document.getElementById('modal-success').classList.add('active');

            successMaxSeconds = 180;
            successSecondsLeft = 180;
            updateCountdown();
            clearInterval(successTimer);
            successTimer = setInterval(() => {
                successSecondsLeft--;
                updateCountdown();
                if (successSecondsLeft <= 0) closeSuccessPopup();
            }, 1000);
        }

        function updateCountdown() {
            const maxSec = successSecondsLeft > 5 && successSecondsLeft <= 180 ? 180 : 5;
            const pct = (successSecondsLeft / maxSec) * 100;
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
                        <!-- Real landmark canvas -->
                        <canvas id="ci-landmark-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;transform:scaleX(-1);"></canvas>
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
            <!-- Fallback/Skip Button -->
            <div id="face-fallback-wrap" style="margin-top: 1rem; display: flex; justify-content: center;">
                <button id="btn-skip-face" onclick="skipFaceScan()" class="btn-ok" style="background: #475569; font-size: 0.85rem; padding: 0.6rem 1.5rem; display: none; border-radius: 0.5rem; border: none; color: #fff; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                    Daftar Tanpa Wajah (Manual)
                </button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL 3: SUCCESS POPUP ===== -->
    <div id="modal-success" class="modal-overlay">
        <div class="modal-box success-box">
            <button class="modal-close" onclick="closeSuccessPopup()" title="Tutup" style="position: absolute; top: 1rem; right: 1rem; background: #f1f5f9; border: none; color: #475569; border-radius: 0.5rem; width: 2rem; height: 2rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
            <div class="success-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:2.5rem;height:2.5rem;color:#10b981;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="success-heading">Check-in Berhasil! 🎉</div>
            <p class="success-sub">Selamat datang, data kunjungan Anda telah dicatat.</p>
            <div class="info-grid">
                <div class="info-item"><label>Nama Tamu</label><span id="si-name">-</span></div>
                <div class="info-item"><label>Instansi</label><span id="si-company">-</span></div>
                <div class="info-item"><label>No. Telepon</label><span id="si-phone">-</span></div>
                <div class="info-item"><label>PIC / Host</label><span id="si-pic">-</span></div>
                <div class="info-item"><label>Departemen</label><span id="si-department">-</span></div>
                <div class="info-item"><label>Tanggal</label><span id="si-date">-</span></div>
                <div class="info-item"><label>Jam</label><span id="si-time">-</span></div>
                <div class="info-item" style="grid-column:1/-1;"><label>Keperluan</label><span id="si-purpose">-</span></div>
            </div>
            <div class="countdown-bar-wrap"><div id="countdown-bar" class="countdown-bar" style="width:100%;"></div></div>
            <p id="countdown-text" class="countdown-text"></p>
            <button class="btn-ok" onclick="closeSuccessPopup()">OK, Terima Kasih</button>
        </div>
    </div>
    
    <!-- ===== MODAL 4: WAITING PIC APPROVAL ===== -->
    <div id="modal-waiting-approval" class="modal-overlay">
        <div class="modal-box waiting-box">
            <div class="waiting-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="waiting-heading">Menunggu Persetujuan PIC<span class="waiting-dots"></span></div>
            <p class="waiting-sub">
                Notifikasi telah dikirim ke <strong id="wa-pic-name">PIC</strong>.<br>
                Layar ini akan otomatis berubah setelah PIC merespon.
            </p>
            <p class="waiting-timer" id="wa-timer">Menunggu respon...</p>
            <button class="btn-cancel-waiting" onclick="cancelWaitingApproval()">Batalkan</button>
        </div>
    </div>

    <!-- ===== MODAL 5: REJECTED BY PIC ===== -->
    <div id="modal-rejected" class="modal-overlay">
        <div class="modal-box rejected-box">
            <div class="rejected-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="rejected-heading">Kunjungan Ditolak</div>
            <p class="rejected-sub">
                Mohon maaf, PIC tidak dapat menerima kunjungan Anda saat ini.<br>
                Silakan hubungi Resepsionis untuk informasi lebih lanjut.
            </p>
            <div class="countdown-bar-wrap"><div id="rejected-countdown-bar" class="countdown-bar" style="width:100%;background:#f43f5e;"></div></div>
            <p id="rejected-countdown-text" class="countdown-text"></p>
            <button class="btn-ok" onclick="closeRejectedPopup()">Kembali</button>
        </div>
    </div>


    <style>
        @keyframes kp-spin      { to { transform: rotate(360deg); } }
        @keyframes kp-spin-ring { to { stroke-dashoffset: -680; } }
        @keyframes kp-pulse     { 0%,100%{opacity:0.15;}50%{opacity:0.75;} }
        @keyframes kp-bounce-r  { 0%,100%{transform:translateX(0);}50%{transform:translateX(6px);} }
        @keyframes kp-bounce-l  { 0%,100%{transform:translateX(0);}50%{transform:translateX(-6px);} }
    </style>

    <script>
        /* ---- Shared Real Face Landmark Drawing ---- */
        /**
         * drawKioskLandmarks — draws real 68-point face landmarks on canvas.
         *
         * The video uses object-fit:cover so the raw landmark coords (in video space)
         * must be transformed: scale by the cover ratio, then offset for the crop.
         * Canvas is drawn at display size so CSS doesn't distort dots further.
         */
        function drawKioskLandmarks(pts, video, canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            // Display size (CSS rendered size of the canvas/video container)
            const dw = canvas.offsetWidth  || 256;
            const dh = canvas.offsetHeight || 256;

            // Set canvas resolution to display size (1:1 pixel mapping)
            canvas.width  = dw;
            canvas.height = dh;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, dw, dh);

            const vw = video.videoWidth  || 640;
            const vh = video.videoHeight || 480;

            // object-fit: cover → scale so that video fills display, then center-crop
            const scale   = Math.max(dw / vw, dh / vh);
            const offsetX = (dw - vw * scale) / 2;
            const offsetY = (dh - vh * scale) / 2;

            // Helper: transform one point from video-space → display-space
            const tx = p => ({ x: p.x * scale + offsetX, y: p.y * scale + offsetY });

            // Dot radius scales with display size
            const dotR = Math.max(1.5, dw / 140);
            const lw   = Math.max(0.8, dw / 200);

            // Landmark groups for 68-point model
            const groups = [
                { s:  0, e: 17, close: false, color: 'rgba(99,102,241,0.75)',  lw: lw },       // jawline
                { s: 17, e: 22, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },       // right eyebrow
                { s: 22, e: 27, close: false, color: 'rgba(99,180,241,0.85)',  lw: lw },       // left eyebrow
                { s: 27, e: 31, close: false, color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 }, // nose bridge
                { s: 30, e: 36, close: true,  color: 'rgba(220,220,255,0.65)', lw: lw * 0.9 }, // nose bottom
                { s: 36, e: 42, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },       // right eye
                { s: 42, e: 48, close: true,  color: 'rgba(52,211,153,0.9)',   lw: lw },       // left eye
                { s: 48, e: 60, close: true,  color: 'rgba(251,191,36,0.8)',   lw: lw },       // outer lips
                { s: 60, e: 68, close: true,  color: 'rgba(251,191,36,0.65)',  lw: lw * 0.9 }, // inner lips
            ];

            // Draw connecting lines between landmark points
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

            // Draw glowing dot at each of the 68 landmark points
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

        function clearKioskLandmarks(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        }

    </script>


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
                        <!-- Real landmark canvas -->
                        <canvas id="co-landmark-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;transform:scaleX(-1);"></canvas>
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


    <!-- ===== MODAL: PIC ATTENDANCE ===== -->
    <div id="modal-attendance" class="modal-overlay">
        <div class="modal-box face-modal-box" style="border-color:rgba(251,191,36,0.4);box-shadow:0 0 80px rgba(251,191,36,0.15);">
            <button class="modal-close" onclick="closeAttendanceModal()">✕</button>
            <div class="modal-title" style="margin-bottom:0.2rem;">Absensi Karyawan</div>
            <p class="modal-sub" style="margin-bottom:1rem;">Tengok kanan lalu kiri untuk verifikasi</p>
            <div id="at-face-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;gap:1rem;">
                <svg style="width:2.5rem;height:2.5rem;color:#fbbf24;animation:kp-spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="10"/></svg>
                <span style="color:#8899bb;font-size:0.85rem;">Memuat model AI...</span>
            </div>
            <div id="at-face-camera-wrap" style="display:none;flex-direction:column;align-items:center;gap:0.5rem;">
                <div style="position:relative;width:272px;height:272px;flex-shrink:0;">
                    <svg id="at-ring-svg" style="position:absolute;inset:0;width:100%;height:100%;z-index:4;pointer-events:none;" viewBox="0 0 272 272">
                        <circle class="at-ring-base" cx="136" cy="136" r="126" fill="none" stroke-width="3" stroke="#fbbf2433"/>
                        <circle class="at-ring-arc" cx="136" cy="136" r="126" fill="none" stroke-width="5" stroke-linecap="round" stroke="#fbbf24" stroke-dasharray="110 692" style="animation:kp-spin-ring 1.6s linear infinite;transform-origin:center;"/>
                    </svg>
                    <div style="position:absolute;inset:8px;border-radius:50%;overflow:hidden;background:#111;z-index:2;">
                        <video id="at-face-video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);display:block;"></video>
                        <canvas id="at-landmark-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;transform:scaleX(-1);z-index:3;"></canvas>
                        <div id="at-face-grid" style="position:absolute;inset:0;pointer-events:none;transition:opacity 0.5s;opacity:1;z-index:2;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;display:block;" viewBox="0 0 256 256" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    <path id="at-face-shape" d="M128,20 C166,20 196,52 196,96 C210,96 210,120 196,122 C188,158 160,192 128,197 C96,192 68,158 60,122 C46,120 46,96 60,96 C60,52 90,20 128,20 Z"/>
                                    <mask id="at-face-mask">
                                        <rect width="256" height="256" fill="white"/>
                                        <use href="#at-face-shape" fill="black"/>
                                    </mask>
                                </defs>
                                <rect width="256" height="256" fill="rgba(0,0,0,0.62)" mask="url(#at-face-mask)"/>
                                <use id="at-face-border" href="#at-face-shape" fill="none" stroke="#fcd34d" stroke-width="2.5" stroke-dasharray="10 6" style="animation:kp-pulse 1.4s ease-in-out infinite;"/>
                            </svg>
                        </div>
                        <div id="at-inner-ring" style="position:absolute;inset:0;border-radius:50%;pointer-events:none;transition:box-shadow 0.4s;z-index:5;"></div>
                    </div>
                </div>
                <div style="min-height:36px;display:flex;align-items:center;justify-content:center;padding:0 0.5rem;">
                    <span id="at-face-msg" style="display:inline-block;padding:0.4rem 1.1rem;border-radius:999px;font-size:0.78rem;font-weight:600;color:#fff;text-align:center;max-width:260px;transition:background 0.3s;background:#fbbf24;"></span>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;min-height:52px;">
                    <div id="at-arrow-right" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-r 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#fcd34d;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <span style="font-size:0.68rem;color:#fcd34d;font-weight:700;letter-spacing:.04em;">KANAN</span>
                    </div>
                    <div id="at-arrow-left" style="display:none;flex-direction:column;align-items:center;gap:4px;animation:kp-bounce-l 0.8s ease-in-out infinite;">
                        <svg style="width:2rem;height:2rem;color:#fcd34d;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        <span style="font-size:0.68rem;color:#fcd34d;font-weight:700;letter-spacing:.04em;">KIRI</span>
                    </div>
                </div>
            </div>
            <div id="at-result" style="display:none;text-align:center;padding:1rem 0 0.5rem;">
                <div id="at-result-icon" style="font-size:2.5rem;margin-bottom:0.4rem;"></div>
                <div id="at-result-name" style="font-size:1.1rem;font-weight:700;color:#f0f4ff;margin-bottom:0.2rem;"></div>
                <div id="at-result-status" style="font-size:0.82rem;color:#8899bb;"></div>
            </div>
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
                await loadScript('/js/face-api.min.js');
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
                    clearKioskLandmarks('co-landmark-canvas');
                    if (coNoFace > 3) setCoMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
                    setTimeout(() => coScanLoop(video), 200); return;
                }
                coNoFace = 0;

                // Draw real landmarks
                drawKioskLandmarks(det.landmarks.positions, video, 'co-landmark-canvas');
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

<script>
/* -------------------------------------------------------
   PIC ATTENDANCE MODAL - Dual-loop: fast landmarks + slow recognition
------------------------------------------------------- */
let atScanStream   = null;
let atLandmarkRAF  = null;
let atScanActive   = false;
let atLivenessStep = 'straight';
let atNoFace       = 0;
let atFaceInPlace  = false;
let atProcessing   = false;

async function openAttendanceModal() {
    document.getElementById('modal-attendance').classList.add('active');
    setAtMsg('Memuat Model AI...', 'info');
    if (typeof faceapi === 'undefined') {
        await loadScript('/js/face-api.min.js');
    }
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
    ]);
    const video = document.getElementById('at-face-video');
    try {
        atScanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 } } });
        video.srcObject = atScanStream;
        video.onloadedmetadata = () => {
            video.play();
            document.getElementById('at-face-loading').style.display     = 'none';
            document.getElementById('at-face-camera-wrap').style.display = 'flex';
            document.getElementById('at-result').style.display           = 'none';
            atLivenessStep = 'straight'; atFaceInPlace = false; atScanActive = true; atProcessing = false;
            updateAtRing('blue');
            setAtMsg('Posisikan wajah di dalam lingkaran', 'info');
            atLandmarkLoop(video);
            atDetectionLoop(video);
        };
    } catch(e) { setAtMsg('Kamera tidak dapat diakses.', 'error'); }
}

function closeAttendanceModal() {
    atScanActive = false;
    if (atLandmarkRAF) { cancelAnimationFrame(atLandmarkRAF); atLandmarkRAF = null; }
    if (atScanStream)  { atScanStream.getTracks().forEach(t => t.stop()); atScanStream = null; }
    clearKioskLandmarks('at-landmark-canvas');
    document.getElementById('modal-attendance').classList.remove('active');
    document.getElementById('at-face-loading').style.display     = 'flex';
    document.getElementById('at-face-camera-wrap').style.display = 'none';
    document.getElementById('at-result').style.display           = 'none';
}

/* FAST landmark loop using requestAnimationFrame */
async function atLandmarkLoop(video) {
    if (!atScanActive) return;
    try {
        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.4 }))
            .withFaceLandmarks();
        if (det) drawKioskLandmarks(det.landmarks.positions, video, 'at-landmark-canvas');
        else     clearKioskLandmarks('at-landmark-canvas');
    } catch(e) {}
    if (atScanActive) atLandmarkRAF = requestAnimationFrame(() => atLandmarkLoop(video));
}

/* SLOW detection loop: liveness + descriptor, every ~150ms */
async function atDetectionLoop(video) {
    if (!atScanActive) return;
    if (atProcessing) { setTimeout(() => atDetectionLoop(video), 150); return; }
    try {
        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks().withFaceDescriptor();
        if (!det) {
            atNoFace++; atFaceInPlace = false;
            document.getElementById('at-face-grid').style.opacity = '0';
            updateAtRing('red');
            if (atNoFace > 3) setAtMsg('Wajah tidak terdeteksi. Masukkan ke lingkaran.', 'error');
            setTimeout(() => atDetectionLoop(video), 150); return;
        }
        atNoFace = 0;
        const box  = det.alignedRect.box;
        const ratio = box.width / video.videoWidth;
        const offX  = Math.abs((box.x + box.width/2) - video.videoWidth/2) / video.videoWidth;
        const offY  = Math.abs((box.y + box.height/2) - video.videoHeight/2) / video.videoHeight;
        if (ratio < 0.28) { atFaceInPlace=false; document.getElementById('at-face-grid').style.opacity='0'; updateAtRing('red'); setAtMsg('Wajah terlalu jauh - maju sedikit.','error'); setTimeout(()=>atDetectionLoop(video),150); return; }
        if (ratio > 0.65) { atFaceInPlace=false; document.getElementById('at-face-grid').style.opacity='0'; updateAtRing('red'); setAtMsg('Wajah terlalu dekat - mundur sedikit.','error'); setTimeout(()=>atDetectionLoop(video),150); return; }
        if (offX > 0.20 || offY > 0.20) {
            atFaceInPlace=false; document.getElementById('at-face-grid').style.opacity='0'; updateAtRing('red');
            setAtMsg('Posisikan wajah di tengah lingkaran.','error');
            setTimeout(()=>atDetectionLoop(video),150); return;
        }
        atFaceInPlace = true;
        document.getElementById('at-face-grid').style.opacity = '1';
        updateAtRing('green');
        const pts = det.landmarks.positions;
        const nr  = (pts[30].x - pts[0].x) / (pts[16].x - pts[0].x);
        if (atLivenessStep === 'straight') {
            setAtMsg('Tengok ke kanan >>>', 'info'); atShowArrow('right');
            if (nr < 0.38) atLivenessStep = 'right';
        } else if (atLivenessStep === 'right') {
            setAtMsg('<<< Sekarang tengok ke kiri', 'info'); atShowArrow('left');
            if (nr > 0.62) {
                atLivenessStep = 'passed';
                setAtMsg('Verifikasi OK! Memproses...', 'success');
                atShowArrow('none');
                atScanActive = false;
                if (atLandmarkRAF) { cancelAnimationFrame(atLandmarkRAF); atLandmarkRAF = null; }
                atProcessing = true;
                Livewire.dispatch('process-pic-face', { descriptor: Array.from(det.descriptor) });
                return;
            }
        }
        setTimeout(() => atDetectionLoop(video), 150);
    } catch(e) { setTimeout(() => atDetectionLoop(video), 500); }
}

function setAtMsg(msg, type) {
    const el = document.getElementById('at-face-msg'); if (!el) return;
    el.textContent = msg;
    el.style.background = type==='error'?'#ef4444':type==='success'?'#10b981':'#fbbf24';
}
function updateAtRing(color) {
    const arc  = document.querySelector('#at-ring-svg .at-ring-arc');
    const base = document.querySelector('#at-ring-svg .at-ring-base');
    const bdr  = document.getElementById('at-face-border');
    const ring = document.getElementById('at-inner-ring');
    const map  = { red:'#ef4444', green:'#10b981', blue:'#fbbf24' };
    const c    = map[color] || '#fbbf24';
    if (arc)  arc.setAttribute('stroke', c);
    if (base) base.setAttribute('stroke', c + '33');
    if (bdr)  bdr.setAttribute('stroke', color==='red' ? '#ef4444' : '#fcd34d');
    if (ring) ring.style.boxShadow = color==='green' ? 'inset 0 0 0 3px #10b981' : '';
}
function atShowArrow(dir) {
    document.getElementById('at-arrow-right').style.display = dir==='right'?'flex':'none';
    document.getElementById('at-arrow-left').style.display  = dir==='left' ?'flex':'none';
}

window.addEventListener('attendance-success', event => {
    const d = (event.detail[0] !== undefined) ? event.detail[0] : event.detail;
    const modal = document.getElementById('modal-attendance');
    if (!modal || !modal.classList.contains('active')) return;
    document.getElementById('at-face-camera-wrap').style.display = 'none';
    const result = document.getElementById('at-result');
    document.getElementById('at-result-icon').textContent   = d.type === 'checkin' ? '\u2705' : '\uD83D\uDC4B';
    document.getElementById('at-result-name').textContent   = d.message;
    document.getElementById('at-result-status').textContent = d.type === 'checkin' ? 'Check-In berhasil dicatat!' : 'Check-Out berhasil dicatat!';
    result.style.display = 'block';
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator(), gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = d.type === 'checkin' ? 880 : 660;
        gain.gain.setValueAtTime(0, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.1, ctx.currentTime + 0.1);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
    setTimeout(() => window.location.reload(), 3000);
});

window.addEventListener('attendance-error', event => {
    const d = (event.detail[0] !== undefined) ? event.detail[0] : event.detail;
    const modal = document.getElementById('modal-attendance');
    if (!modal || !modal.classList.contains('active')) return;
    setAtMsg(d.message || 'Wajah tidak dikenali dalam sistem.', 'error');
    updateAtRing('red');
    setTimeout(() => {
        atLivenessStep = 'straight'; atFaceInPlace = false; atProcessing = false; atScanActive = true;
        updateAtRing('blue');
        setAtMsg('Posisikan wajah di dalam lingkaran', 'info');
        atShowArrow('none');
        const video = document.getElementById('at-face-video');
        if (video) { atLandmarkLoop(video); atDetectionLoop(video); }
    }, 3000);
});
</script>

{{-- Service Worker Registration for Face API and Models Caching --}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((reg) => console.log('[Service Worker] Registered successfully:', reg.scope))
                .catch((err) => console.error('[Service Worker] Registration failed:', err));
        });
    }

    // Restore saved location on load
    (function() {
        const savedLoc = localStorage.getItem('kiosk-location') || 'SA';
        const displayEl = document.getElementById('kiosk-location-display');
        if (displayEl) {
            displayEl.textContent = savedLoc;
        }
    })();
</script>

{{-- Livewire Scripts (includes Alpine.js v3 automatically in Livewire v3) --}}
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