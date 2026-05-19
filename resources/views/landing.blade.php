<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VISITA — Sistem Manajemen Tamu Cerdas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink: #0d0d0d;
            --ink-muted: #5a5a5a;
            --ink-faint: #a0a0a0;
            --paper: #f7f5f0;
            --paper-warm: #ede9e0;
            --accent: #1a56db;
            --accent-glow: rgba(26, 86, 219, 0.12);
            --gold: #c8a84b;
            --red: #d13e2a;
            --surface: #ffffff;
            --border: rgba(13,13,13,0.10);
            --border-strong: rgba(13,13,13,0.20);
            --radius: 16px;
            --radius-sm: 8px;
            --radius-pill: 100px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* NOISE TEXTURE */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 9999;
            opacity: 0.5;
        }

        /* NAV */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 20px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(247, 245, 240, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            transition: all 0.3s;
        }

        .nav-logo {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--ink);
            text-decoration: none;
        }
        .nav-logo span { color: var(--accent); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 36px;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--ink-muted);
            font-size: 14px;
            font-weight: 400;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--ink); }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: var(--radius-pill);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-ghost {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--border-strong);
        }
        .btn-ghost:hover { background: var(--paper-warm); }
        .btn-primary {
            background: var(--ink);
            color: #fff;
        }
        .btn-primary:hover { background: #222; transform: translateY(-1px); }
        .btn-accent {
            background: var(--accent);
            color: #fff;
        }
        .btn-accent:hover { background: #1446c0; transform: translateY(-2px); }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 24px 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
            opacity: 0.5;
        }

        .hero-blob {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
        }
        .hero-blob-1 { background: var(--accent); top: -200px; left: -100px; }
        .hero-blob-2 { background: var(--gold); bottom: -200px; right: -100px; opacity: 0.15; }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: var(--surface);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-pill);
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-muted);
            margin-bottom: 32px;
            letter-spacing: 0.02em;
            animation: fadeUp 0.6s ease both;
        }
        .hero-badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 2s ease infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(48px, 8vw, 96px);
            font-weight: 800;
            line-height: 1.0;
            letter-spacing: -3px;
            color: var(--ink);
            max-width: 900px;
            margin-bottom: 24px;
            animation: fadeUp 0.7s 0.1s ease both;
        }
        .hero h1 em {
            font-style: normal;
            color: var(--accent);
            position: relative;
        }
        .hero h1 em::after {
            content: '';
            position: absolute;
            bottom: 4px; left: 0; right: 0;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
            opacity: 0.3;
        }

        .hero-sub {
            font-size: 18px;
            color: var(--ink-muted);
            max-width: 540px;
            line-height: 1.7;
            margin-bottom: 48px;
            font-weight: 300;
            animation: fadeUp 0.7s 0.2s ease both;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 80px;
            animation: fadeUp 0.7s 0.3s ease both;
        }

        .hero-stats {
            display: flex;
            align-items: center;
            gap: 48px;
            animation: fadeUp 0.7s 0.4s ease both;
        }
        .hero-stat {
            text-align: center;
        }
        .hero-stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--ink);
        }
        .hero-stat-label {
            font-size: 12px;
            color: var(--ink-faint);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .hero-stat-divider {
            width: 1px;
            height: 36px;
            background: var(--border-strong);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* DASHBOARD PREVIEW */
        .preview-section {
            padding: 0 48px 100px;
            position: relative;
        }

        .dashboard-mockup {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 24px;
            border: 1px solid var(--border-strong);
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.12), 0 0 0 1px var(--border);
            animation: fadeUp 0.8s 0.5s ease both;
        }

        .mockup-bar {
            background: #f0ede8;
            border-bottom: 1px solid var(--border);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mockup-dots { display: flex; gap: 6px; }
        .mockup-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
        }
        .mockup-dot:nth-child(1) { background: #f47474; }
        .mockup-dot:nth-child(2) { background: #f4c474; }
        .mockup-dot:nth-child(3) { background: #74c474; }
        .mockup-url {
            flex: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 12px;
            color: var(--ink-faint);
            max-width: 360px;
        }

        .mockup-body {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: 480px;
        }

        .mockup-sidebar {
            background: #f9f7f4;
            border-right: 1px solid var(--border);
            padding: 24px 0;
        }
        .sidebar-logo {
            padding: 0 24px 24px;
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
            border-bottom: 1px solid var(--border);
            margin-bottom: 12px;
        }
        .sidebar-logo span { color: var(--accent); }

        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            font-size: 13px;
            color: var(--ink-muted);
            cursor: pointer;
            transition: all 0.15s;
            border-radius: 0;
        }
        .sidebar-nav-item.active {
            background: var(--accent-glow);
            color: var(--accent);
            font-weight: 500;
            border-right: 2px solid var(--accent);
        }
        .sidebar-nav-icon {
            width: 16px; height: 16px;
            opacity: 0.6;
        }
        .sidebar-nav-item.active .sidebar-nav-icon { opacity: 1; }

        .mockup-main {
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .mockup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .mockup-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
        }
        .mockup-date {
            font-size: 12px;
            color: var(--ink-faint);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        .kpi-card {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }
        .kpi-label {
            font-size: 11px;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
        }
        .kpi-delta {
            font-size: 11px;
            color: #2a9d4f;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .kpi-delta.down { color: var(--red); }

        .visitor-table {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-header {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr 80px;
            padding: 10px 16px;
            background: var(--paper);
            border-bottom: 1px solid var(--border);
        }
        .table-header span {
            font-size: 11px;
            font-weight: 500;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .table-row {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr 80px;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            align-items: center;
            font-size: 12px;
            transition: background 0.15s;
        }
        .table-row:last-child { border-bottom: none; }
        .table-row:hover { background: var(--paper); }
        .visitor-name {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .visitor-avatar {
            width: 26px; height: 26px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px;
            font-weight: 600;
            color: #fff;
            flex-shrink: 0;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 500;
        }
        .badge-green { background: #e6f9ee; color: #1a7a3e; }
        .badge-amber { background: #fef6e6; color: #8a5c00; }
        .badge-blue { background: #e6eeff; color: #1a40a0; }
        .badge-red { background: #fde8e8; color: #a02020; }

        /* AI AVATAR SECTION */
        .ai-section {
            padding: 80px 48px;
            position: relative;
            overflow: hidden;
        }

        .ai-section-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .ai-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-avatar-container {
            position: relative;
            width: 320px;
            height: 320px;
        }

        .ai-orbit {
            position: absolute;
            border: 1px dashed var(--border-strong);
            border-radius: 50%;
            animation: spin linear infinite;
        }
        .ai-orbit-1 { inset: 0; animation-duration: 20s; }
        .ai-orbit-2 { inset: 30px; animation-duration: 15s; animation-direction: reverse; }
        .ai-orbit-3 { inset: 60px; animation-duration: 30s; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .ai-orbit-dot {
            position: absolute;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--accent);
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .ai-core {
            position: absolute;
            inset: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a56db, #0d3a9e);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 60px rgba(26, 86, 219, 0.4), 0 0 120px rgba(26, 86, 219, 0.15);
        }

        .ai-face {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 6px;
        }

        .ai-eyes {
            display: flex;
            gap: 16px;
        }
        .ai-eye {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            animation: blink 4s ease infinite;
        }
        @keyframes blink {
            0%, 90%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }

        .ai-mouth {
            width: 28px;
            height: 2px;
            background: rgba(255,255,255,0.6);
            border-radius: 2px;
        }

        .ai-pulse-ring {
            position: absolute;
            inset: 80px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            animation: aiPulse 2s ease infinite;
        }
        @keyframes aiPulse {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        /* Floating AI chips */
        .ai-chip {
            position: absolute;
            background: var(--surface);
            border: 1px solid var(--border-strong);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ink);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            white-space: nowrap;
        }
        .ai-chip-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
        }
        .ai-chip-1 { top: 10px; right: -20px; animation: floatChip 3s ease-in-out infinite; }
        .ai-chip-2 { bottom: 30px; left: -30px; animation: floatChip 3s 1s ease-in-out infinite; }
        .ai-chip-3 { bottom: 80px; right: -10px; animation: floatChip 3s 0.5s ease-in-out infinite; }

        @keyframes floatChip {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* Chat widget */
        .ai-chat-widget {
            position: absolute;
            top: -20px;
            left: -40px;
            width: 220px;
            background: var(--surface);
            border: 1px solid var(--border-strong);
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.10);
            animation: floatChip 4s 0.3s ease-in-out infinite;
        }
        .chat-widget-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .chat-widget-avatar {
            width: 24px; height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a56db, #0d3a9e);
            display: flex; align-items: center; justify-content: center;
        }
        .chat-widget-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--ink);
        }
        .chat-widget-status {
            font-size: 10px;
            color: #2a9d4f;
        }
        .chat-bubble {
            background: var(--paper);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 11px;
            color: var(--ink-muted);
            line-height: 1.5;
        }
        .chat-bubble.user {
            background: var(--accent);
            color: #fff;
            margin-top: 6px;
        }

        .ai-content h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--ink);
        }

        .ai-content p {
            font-size: 16px;
            color: var(--ink-muted);
            font-weight: 300;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .ai-features {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .ai-feature {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .ai-feature-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--accent-glow);
            border: 1px solid rgba(26, 86, 219, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .ai-feature-text h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 2px;
        }
        .ai-feature-text p {
            font-size: 13px;
            color: var(--ink-muted);
            margin: 0;
            line-height: 1.5;
            font-weight: 400;
        }

        /* FEATURES */
        .features-section {
            padding: 100px 48px;
            background: var(--surface);
            position: relative;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-strong) 30%, var(--border-strong) 70%, transparent);
        }

        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent);
            margin-bottom: 16px;
        }
        .section-label::before {
            content: '';
            width: 16px; height: 1px;
            background: var(--accent);
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.1;
            color: var(--ink);
            max-width: 600px;
            margin-bottom: 16px;
        }
        .section-sub {
            font-size: 16px;
            color: var(--ink-muted);
            font-weight: 300;
            max-width: 500px;
            line-height: 1.7;
            margin-bottom: 60px;
        }

        .features-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.25s;
            cursor: default;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 20px 60px rgba(0,0,0,0.08); }
        .feature-card:hover::before { opacity: 1; }

        .feature-number {
            font-family: 'Syne', sans-serif;
            font-size: 64px;
            font-weight: 800;
            color: var(--border-strong);
            line-height: 1;
            margin-bottom: 20px;
            letter-spacing: -3px;
        }

        .feature-icon-box {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: var(--surface);
            border: 1px solid var(--border-strong);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--ink);
            margin-bottom: 10px;
        }
        .feature-card p {
            font-size: 14px;
            color: var(--ink-muted);
            line-height: 1.7;
            font-weight: 300;
        }

        /* HOW IT WORKS */
        .how-section {
            padding: 100px 48px;
            position: relative;
            overflow: hidden;
        }

        .how-section-inner {
            max-width: 1100px;
            margin: 0 auto;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            position: relative;
            margin-top: 60px;
        }

        .steps-grid::before {
            content: '';
            position: absolute;
            top: 28px;
            left: 10%;
            right: 10%;
            height: 1px;
            background: var(--border-strong);
            z-index: 0;
        }

        .step {
            text-align: center;
            padding: 0 16px;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: var(--surface);
            border: 2px solid var(--border-strong);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--ink-muted);
            transition: all 0.2s;
        }
        .step:hover .step-circle {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .step h4 {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 8px;
        }
        .step p {
            font-size: 13px;
            color: var(--ink-muted);
            line-height: 1.6;
            font-weight: 300;
        }

        /* TESTIMONIALS */
        .testimonials-section {
            padding: 100px 48px;
            background: var(--ink);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .testimonials-section .section-label { color: var(--gold); }
        .testimonials-section .section-label::before { background: var(--gold); }
        .testimonials-section .section-title { color: #fff; }

        .testimonials-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .testimonial-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.2s;
        }
        .testimonial-card:hover {
            background: rgba(255,255,255,0.08);
            transform: translateY(-2px);
        }

        .stars {
            display: flex;
            gap: 3px;
            margin-bottom: 20px;
        }
        .star {
            width: 12px; height: 12px;
            color: var(--gold);
            font-size: 14px;
        }

        .testimonial-text {
            font-size: 15px;
            line-height: 1.7;
            color: rgba(255,255,255,0.8);
            font-weight: 300;
            margin-bottom: 24px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .author-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }
        .author-name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }
        .author-role {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }

        /* PRICING */
        .pricing-section {
            padding: 100px 48px;
            background: var(--surface);
            position: relative;
        }

        .pricing-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-strong) 30%, var(--border-strong) 70%, transparent);
        }

        .pricing-grid {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .pricing-card {
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--border-strong);
            background: var(--paper);
            transition: all 0.25s;
        }
        .pricing-card.featured {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
            transform: scale(1.02);
        }
        .pricing-card:hover:not(.featured) {
            border-color: var(--accent);
            transform: translateY(-4px);
        }

        .pricing-tier {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--ink-faint);
            margin-bottom: 16px;
        }
        .pricing-card.featured .pricing-tier { color: rgba(255,255,255,0.5); }

        .pricing-price {
            font-family: 'Syne', sans-serif;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: -2px;
            color: var(--ink);
            margin-bottom: 4px;
        }
        .pricing-card.featured .pricing-price { color: #fff; }

        .pricing-period {
            font-size: 13px;
            color: var(--ink-faint);
            margin-bottom: 24px;
        }
        .pricing-card.featured .pricing-period { color: rgba(255,255,255,0.4); }

        .pricing-divider {
            height: 1px;
            background: var(--border);
            margin-bottom: 24px;
        }
        .pricing-card.featured .pricing-divider { background: rgba(255,255,255,0.12); }

        .pricing-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 28px;
        }
        .pricing-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--ink-muted);
        }
        .pricing-card.featured .pricing-feature { color: rgba(255,255,255,0.7); }
        .pricing-check {
            width: 16px; height: 16px;
            border-radius: 50%;
            background: var(--accent-glow);
            border: 1px solid var(--accent);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .pricing-card.featured .pricing-check {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }

        .pricing-btn {
            width: 100%;
            padding: 12px;
            border-radius: var(--radius-pill);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid var(--border-strong);
            background: transparent;
            color: var(--ink);
            cursor: pointer;
            transition: all 0.2s;
        }
        .pricing-btn:hover { background: var(--paper-warm); }
        .pricing-card.featured .pricing-btn {
            background: #fff;
            color: var(--ink);
            border-color: transparent;
        }
        .pricing-card.featured .pricing-btn:hover { background: #f0ede8; }

        /* CTA */
        .cta-section {
            padding: 100px 48px;
            text-align: center;
            background: var(--paper);
        }

        .cta-inner {
            max-width: 700px;
            margin: 0 auto;
        }

        .cta-inner h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 5vw, 64px);
            font-weight: 800;
            letter-spacing: -2.5px;
            line-height: 1.0;
            color: var(--ink);
            margin-bottom: 20px;
        }
        .cta-inner p {
            font-size: 16px;
            color: var(--ink-muted);
            font-weight: 300;
            margin-bottom: 40px;
            line-height: 1.7;
        }

        /* FOOTER */
        footer {
            background: var(--ink);
            color: rgba(255,255,255,0.5);
            padding: 60px 48px 40px;
        }

        .footer-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-brand h3 {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
        }
        .footer-brand h3 span { color: var(--accent); }
        .footer-brand p {
            font-size: 13px;
            line-height: 1.7;
            max-width: 240px;
        }

        .footer-col h4 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.6);
            margin-bottom: 16px;
        }
        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 10px; }
        .footer-col a {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            transition: color 0.15s;
        }
        .footer-col a:hover { color: rgba(255,255,255,0.8); }

        .footer-bottom {
            max-width: 1100px;
            margin: 0 auto;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }

        /* SCROLL REVEAL */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav { padding: 16px 24px; }
            .nav-links { display: none; }
            .hero h1 { letter-spacing: -2px; }
            .hero-stats { gap: 24px; flex-wrap: wrap; justify-content: center; }
            .mockup-body { grid-template-columns: 1fr; }
            .mockup-sidebar { display: none; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .ai-section-inner { grid-template-columns: 1fr; gap: 40px; }
            .ai-avatar-container { width: 240px; height: 240px; }
            .features-grid { grid-template-columns: 1fr; }
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
            .steps-grid::before { display: none; }
            .testimonials-grid { grid-template-columns: 1fr; }
            .pricing-grid { grid-template-columns: 1fr; }
            .pricing-card.featured { transform: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
            .preview-section, .ai-section, .features-section, .how-section, .testimonials-section, .pricing-section, .cta-section { padding-left: 24px; padding-right: 24px; }
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav>
        <a href="#" class="nav-logo">VISITA<span>.</span></a>
        <ul class="nav-links">
            <li><a href="#fitur">Fitur</a></li>
            <li><a href="#cara-kerja">Cara Kerja</a></li>
            <li><a href="#harga">Harga</a></li>
            <li><a href="#testimoni">Testimoni</a></li>
        </ul>
        <div class="nav-cta">
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-primary">Mulai Gratis →</a>
            @endif
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-grid-bg"></div>
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>

        <div class="hero-badge">
            <div class="hero-badge-dot"></div>
            Sistem Manajemen Tamu Bertenaga AI — 2026
        </div>

        <h1>Sambut Setiap Tamu <em>Lebih Cerdas</em></h1>

        <p class="hero-sub">
            Platform manajemen tamu generasi berikutnya dengan kecerdasan buatan yang meningkatkan pengalaman kunjungan dari registrasi hingga kepulangan.
        </p>

        <div class="hero-actions">
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-accent" style="font-size:15px;padding:12px 28px;">
                    Coba Gratis 14 Hari →
                </a>
            @endif
            <a href="#fitur" class="btn btn-ghost" style="font-size:15px;padding:12px 28px;">
                Lihat Demo
            </a>
        </div>

        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-num">50K+</div>
                <div class="hero-stat-label">Tamu Terdaftar</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <div class="hero-stat-num">98%</div>
                <div class="hero-stat-label">Kepuasan</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <div class="hero-stat-num">3 Det</div>
                <div class="hero-stat-label">Check-in Rata-rata</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <div class="hero-stat-num">500+</div>
                <div class="hero-stat-label">Perusahaan</div>
            </div>
        </div>
    </section>

    <!-- DASHBOARD PREVIEW -->
    <section class="preview-section">
        <div class="dashboard-mockup reveal">
            <div class="mockup-bar">
                <div class="mockup-dots">
                    <div class="mockup-dot"></div>
                    <div class="mockup-dot"></div>
                    <div class="mockup-dot"></div>
                </div>
                <div class="mockup-url">visita.app/dashboard</div>
            </div>
            <div class="mockup-body">
                <div class="mockup-sidebar">
                    <div class="sidebar-logo">VISITA<span>.</span></div>
                    <div class="sidebar-nav-item active">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
                        Dashboard
                    </div>
                    <div class="sidebar-nav-item">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a3 3 0 100 6 3 3 0 000-6zM3 13c0-2.76 2.24-5 5-5s5 2.24 5 5H3z"/></svg>
                        Tamu
                    </div>
                    <div class="sidebar-nav-item">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2z" opacity=".2"/><path d="M5 1v2M11 1v2M1 7h14M2 2h12a1 1 0 011 1v10a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1z" fill="none" stroke="currentColor" stroke-linecap="round"/></svg>
                        Jadwal
                    </div>
                    <div class="sidebar-nav-item">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><path d="M13 2H3a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V3a1 1 0 00-1-1zM7 11l-3-3 1.4-1.4L7 8.2l3.6-3.6L12 6l-5 5z"/></svg>
                        Persetujuan
                    </div>
                    <div class="sidebar-nav-item">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L6 5H2l3 2.5L3.5 12 8 9l4.5 3L11 7.5 14 5h-4L8 1z"/></svg>
                        AI Asisten
                    </div>
                    <div class="sidebar-nav-item">
                        <svg class="sidebar-nav-icon" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zM7 5h2v4H7zm0 6h2v2H7z"/></svg>
                        Laporan
                    </div>
                </div>
                <div class="mockup-main">
                    <div class="mockup-header">
                        <div>
                            <div class="mockup-title">Dashboard Tamu</div>
                            <div class="mockup-date">Minggu, 26 April 2026 — Pagi ini</div>
                        </div>
                        <button class="btn btn-accent" style="font-size:12px;padding:8px 16px;">+ Daftarkan Tamu</button>
                    </div>
                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-label">Tamu Hari Ini</div>
                            <div class="kpi-value">47</div>
                            <div class="kpi-delta">↑ 12% dari kemarin</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Sedang Berkunjung</div>
                            <div class="kpi-value">18</div>
                            <div class="kpi-delta">↑ 3 dari sejam lalu</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Menunggu Masuk</div>
                            <div class="kpi-value">5</div>
                            <div class="kpi-delta down">↓ 2 dari kemarin</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Rata-rata Durasi</div>
                            <div class="kpi-value">45m</div>
                            <div class="kpi-delta">↑ stabil</div>
                        </div>
                    </div>
                    <div class="visitor-table">
                        <div class="table-header">
                            <span>Nama Tamu</span>
                            <span>Tujuan</span>
                            <span>Masuk</span>
                            <span>Status</span>
                            <span>Aksi</span>
                        </div>
                        <div class="table-row">
                            <div class="visitor-name">
                                <div class="visitor-avatar" style="background:#1a56db;">AS</div>
                                <span>Arya Santoso</span>
                            </div>
                            <span>Wawancara HR</span>
                            <span>08:30</span>
                            <span><div class="badge badge-green">Di dalam</div></span>
                            <span style="font-size:11px;color:#1a56db;cursor:pointer;">Detail</span>
                        </div>
                        <div class="table-row">
                            <div class="visitor-name">
                                <div class="visitor-avatar" style="background:#c8a84b;">DK</div>
                                <span>Dewi Kurniawati</span>
                            </div>
                            <span>Rapat Vendor</span>
                            <span>09:00</span>
                            <span><div class="badge badge-amber">Menunggu</div></span>
                            <span style="font-size:11px;color:#1a56db;cursor:pointer;">Detail</span>
                        </div>
                        <div class="table-row">
                            <div class="visitor-name">
                                <div class="visitor-avatar" style="background:#2a9d4f;">BR</div>
                                <span>Budi Rahardjo</span>
                            </div>
                            <span>Presentasi Produk</span>
                            <span>09:15</span>
                            <span><div class="badge badge-blue">Terjadwal</div></span>
                            <span style="font-size:11px;color:#1a56db;cursor:pointer;">Detail</span>
                        </div>
                        <div class="table-row">
                            <div class="visitor-name">
                                <div class="visitor-avatar" style="background:#d13e2a;">SL</div>
                                <span>Siti Lestari</span>
                            </div>
                            <span>Kunjungan Klien</span>
                            <span>10:00</span>
                            <span><div class="badge badge-green">Di dalam</div></span>
                            <span style="font-size:11px;color:#1a56db;cursor:pointer;">Detail</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI SECTION -->
    <section class="ai-section" id="fitur">
        <div class="ai-section-inner">
            <div class="ai-visual reveal">
                <div class="ai-avatar-container">
                    <div class="ai-orbit ai-orbit-1"><div class="ai-orbit-dot"></div></div>
                    <div class="ai-orbit ai-orbit-2"><div class="ai-orbit-dot" style="background:var(--gold);top:auto;bottom:-4px;left:50%;"></div></div>
                    <div class="ai-orbit ai-orbit-3"><div class="ai-orbit-dot" style="background:#2a9d4f;top:50%;left:-4px;transform:none;"></div></div>
                    <div class="ai-pulse-ring"></div>
                    <div class="ai-core">
                        <div class="ai-face">
                            <div class="ai-eyes">
                                <div class="ai-eye"></div>
                                <div class="ai-eye"></div>
                            </div>
                            <div class="ai-mouth"></div>
                        </div>
                    </div>

                    <!-- Floating chips -->
                    <div class="ai-chip ai-chip-1">
                        <div class="ai-chip-dot" style="background:#2a9d4f;"></div>
                        Tamu diverifikasi
                    </div>
                    <div class="ai-chip ai-chip-2">
                        <div class="ai-chip-dot" style="background:var(--accent);"></div>
                        Analisis real-time
                    </div>
                    <div class="ai-chip ai-chip-3">
                        <div class="ai-chip-dot" style="background:var(--gold);"></div>
                        97% akurasi
                    </div>

                    <!-- Chat widget -->
                    <div class="ai-chat-widget">
                        <div class="chat-widget-header">
                            <div class="chat-widget-avatar">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="white"><path d="M6 1l1.5 3 3.5.5-2.5 2.5.5 3.5L6 9l-3 1.5.5-3.5L1 4.5l3.5-.5L6 1z"/></svg>
                            </div>
                            <div>
                                <div class="chat-widget-name">VISITA AI</div>
                                <div class="chat-widget-status">● Online</div>
                            </div>
                        </div>
                        <div class="chat-bubble">Halo! Siapa yang ingin Anda kunjungi?</div>
                        <div class="chat-bubble user">Saya ingin bertemu Pak Budi, Direktur IT</div>
                    </div>
                </div>
            </div>

            <div class="ai-content reveal">
                <div class="section-label">AI-Powered</div>
                <h2>Asisten AI yang Selalu Siap Membantu</h2>
                <p>VISITA AI memahami konteks kunjungan, memverifikasi identitas, dan memandu tamu dengan natural — seperti resepsionis terbaik Anda, 24 jam sehari.</p>

                <div class="ai-features">
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="#1a56db" stroke-width="1.5"/><path d="M6 9l2 2 4-4" stroke="#1a56db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="ai-feature-text">
                            <h4>Verifikasi Otomatis</h4>
                            <p>AI memverifikasi identitas tamu dan mencocokkan dengan jadwal kunjungan secara real-time.</p>
                        </div>
                    </div>
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M3 12V6a2 2 0 012-2h8a2 2 0 012 2v6" stroke="#1a56db" stroke-width="1.5" stroke-linecap="round"/><path d="M1 12h16" stroke="#1a56db" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                        <div class="ai-feature-text">
                            <h4>Notifikasi Cerdas</h4>
                            <p>Tuan rumah otomatis diberitahu saat tamu tiba, dengan ringkasan konteks kunjungan.</p>
                        </div>
                    </div>
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 2v14M2 9h14" stroke="#1a56db" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                        <div class="ai-feature-text">
                            <h4>Analisis & Laporan</h4>
                            <p>Laporan kunjungan otomatis dengan insight pola kunjungan dan rekomendasi operasional.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features-section">
        <div style="max-width:1100px;margin:0 auto;">
            <div class="section-label reveal">Kemampuan Platform</div>
            <div class="section-title reveal">Semua yang Anda Butuhkan</div>
            <div class="section-sub reveal">Dirancang untuk perusahaan modern yang menghargai waktu tamu dan keamanan gedung.</div>

            <div class="features-grid">
                <div class="feature-card reveal">
                    <div class="feature-number">01</div>
                    <h3>Check-in Digital</h3>
                    <p>Tamu mendaftar mandiri melalui tablet, QR code, atau pre-registration link. Tidak ada antrian panjang di resepsionis.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-number">02</div>
                    <h3>Manajemen Akses</h3>
                    <p>Integrasikan dengan sistem akses gedung. Badge digital otomatis diterbitkan sesuai zona yang diizinkan.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-number">03</div>
                    <h3>NDA & Dokumen</h3>
                    <p>Tandatangani NDA, peraturan gedung, dan dokumen legal lainnya secara digital saat check-in.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-number">04</div>
                    <h3>Screening Keamanan</h3>
                    <p>Verifikasi watchlist otomatis dan screening keamanan terintegrasi untuk menjaga keamanan gedung.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-number">05</div>
                    <h3>Laporan Real-time</h3>
                    <p>Dashboard live menampilkan siapa saja yang ada di gedung saat ini, riwayat, dan analitik mendalam.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-number">06</div>
                    <h3>Integrasi Kalender</h3>
                    <p>Sinkronisasi dengan Google Calendar, Outlook, dan sistem meeting perusahaan untuk pra-registrasi otomatis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="how-section" id="cara-kerja">
        <div class="how-section-inner">
            <div class="section-label reveal">Proses</div>
            <div class="section-title reveal">Sederhana, Cepat, Aman</div>
            <div class="section-sub reveal">Dari tamu tiba hingga check-out, semua berjalan otomatis.</div>

            <div class="steps-grid">
                <div class="step reveal">
                    <div class="step-circle">1</div>
                    <h4>Pra-Registrasi</h4>
                    <p>Tamu menerima undangan digital dan mengisi data sebelum tiba.</p>
                </div>
                <div class="step reveal">
                    <div class="step-circle">2</div>
                    <h4>Check-in Digital</h4>
                    <p>Scan QR atau selfie untuk verifikasi identitas otomatis.</p>
                </div>
                <div class="step reveal">
                    <div class="step-circle">3</div>
                    <h4>Notifikasi Host</h4>
                    <p>Tuan rumah langsung diberitahu dan badge akses diterbitkan.</p>
                </div>
                <div class="step reveal">
                    <div class="step-circle">4</div>
                    <h4>Check-out & Laporan</h4>
                    <p>Check-out dicatat otomatis dan laporan kunjungan tersimpan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials-section" id="testimoni">
        <div style="max-width:1100px;margin:0 auto;">
            <div class="section-label reveal">Testimoni</div>
            <div class="section-title reveal" style="color:#fff;margin-bottom:16px;">Dipercaya Ratusan Perusahaan</div>
            <div class="section-sub reveal" style="color:rgba(255,255,255,0.5);margin-bottom:60px;">Dari startup hingga korporasi, VISITA mengubah cara mereka menyambut tamu.</div>

            <div class="testimonials-grid">
                <div class="testimonial-card reveal">
                    <div class="stars">★★★★★</div>
                    <div class="testimonial-text">"VISITA mengubah total pengalaman tamu di kantor kami. Check-in yang dulu 5 menit sekarang hanya 20 detik. Tamu kami sangat terkesan."</div>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:#1a56db;">RH</div>
                        <div>
                            <div class="author-name">Reza Hartawan</div>
                            <div class="author-role">COO, TechCorp Indonesia</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card reveal">
                    <div class="stars">★★★★★</div>
                    <div class="testimonial-text">"Fitur AI-nya luar biasa. Sistem mendeteksi anomali kunjungan dan langsung alert tim keamanan kami. Investasi terbaik tahun ini."</div>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:#c8a84b;">MS</div>
                        <div>
                            <div class="author-name">Maya Sari</div>
                            <div class="author-role">Facility Manager, BCA Group</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card reveal">
                    <div class="stars">★★★★★</div>
                    <div class="testimonial-text">"Setup dalam 2 jam, langsung bisa digunakan. Tim support mereka responsif dan selalu siap membantu. Sangat direkomendasikan!"</div>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background:#2a9d4f;">AP</div>
                        <div>
                            <div class="author-name">Andi Purnomo</div>
                            <div class="author-role">IT Director, Pertamina</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="pricing-section" id="harga">
        <div style="max-width:1100px;margin:0 auto;text-align:center;">
            <div class="section-label reveal" style="justify-content:center;">Harga</div>
            <div class="section-title reveal" style="margin:0 auto 16px;">Transparan, Tanpa Kejutan</div>
            <div class="section-sub reveal" style="margin:0 auto 60px;">Mulai gratis, upgrade kapan saja.</div>
        </div>

        <div class="pricing-grid">
            <div class="pricing-card reveal">
                <div class="pricing-tier">Starter</div>
                <div class="pricing-price">Gratis</div>
                <div class="pricing-period">Selamanya, tanpa kartu kredit</div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Hingga 50 tamu/bulan
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Dashboard dasar
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Notifikasi email
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Support komunitas
                    </li>
                </ul>
                <button class="pricing-btn">Mulai Sekarang</button>
            </div>

            <div class="pricing-card featured reveal">
                <div class="pricing-tier">Professional</div>
                <div class="pricing-price">Rp 499K</div>
                <div class="pricing-period">per bulan, billed annually</div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Tamu tak terbatas
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        AI Asisten penuh
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Integrasi kalender
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        NDA & dokumen digital
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Priority support 24/7
                    </li>
                </ul>
                <button class="pricing-btn">Coba 14 Hari Gratis</button>
            </div>

            <div class="pricing-card reveal">
                <div class="pricing-tier">Enterprise</div>
                <div class="pricing-price">Custom</div>
                <div class="pricing-period">Harga sesuai kebutuhan</div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Multi-lokasi & multi-tenant
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        On-premise deployment
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Custom AI training
                    </li>
                    <li class="pricing-feature">
                        <div class="pricing-check"><svg width="8" height="8" viewBox="0 0 8 8"><path d="M1 4l2 2 4-4" stroke="#1a56db" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></div>
                        Dedicated account manager
                    </li>
                </ul>
                <button class="pricing-btn">Hubungi Sales</button>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-inner reveal">
            <h2>Siap Mengubah Pengalaman Tamu Anda?</h2>
            <p>Bergabung dengan 500+ perusahaan yang sudah menggunakan VISITA. Mulai gratis, tanpa kartu kredit.</p>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-primary" style="font-size:16px;padding:14px 36px;margin-right:12px;">
                    Mulai Gratis Sekarang →
                </a>
            @endif
            <a href="#" class="btn btn-ghost" style="font-size:16px;padding:14px 36px;">
                Jadwalkan Demo
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>VISITA<span>.</span></h3>
                <p>Sistem manajemen tamu bertenaga AI untuk perusahaan modern Indonesia.</p>
            </div>
            <div class="footer-col">
                <h4>Produk</h4>
                <ul>
                    <li><a href="#">Fitur</a></li>
                    <li><a href="#">Keamanan</a></li>
                    <li><a href="#">Integrasi</a></li>
                    <li><a href="#">API</a></li>
                    <li><a href="#">Changelog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Perusahaan</h4>
                <ul>
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Karir</a></li>
                    <li><a href="#">Kontak</a></li>
                    <li><a href="#">Press</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Dukungan</h4>
                <ul>
                    <li><a href="#">Dokumentasi</a></li>
                    <li><a href="#">Status</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat Layanan</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 VISITA. Dibuat dengan ♥ di Indonesia.</span>
            <span>Semarang, Jawa Tengah 🇮🇩</span>
        </div>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, 80);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            nav.style.boxShadow = window.scrollY > 20 ? '0 4px 24px rgba(0,0,0,0.08)' : 'none';
        });

        document.querySelectorAll('.pricing-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const plan = this.closest('.pricing-card').querySelector('.pricing-tier').textContent;
                if (plan === 'Enterprise') {
                    window.location.href = 'mailto:sales@visita.app';
                } else {
                    window.location.href = '{{ Route::has("register") ? route("register") : "#" }}';
                }
            });
        });
    </script>
</body>
</html>