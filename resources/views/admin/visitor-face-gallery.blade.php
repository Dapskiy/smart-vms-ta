<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foto Wajah — {{ $visitor->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        .page-header .visitor-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .page-header .visitor-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            max-width: 640px;
            width: 100%;
        }

        .photo-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .photo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.15);
            border-color: #6366f1;
        }

        .photo-card .photo-wrapper {
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .photo-card:hover img {
            transform: scale(1.05);
        }

        .photo-label {
            padding: 0.6rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .photo-label .label-number {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .photo-label .label-text {
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .photo-card.empty-card {
            opacity: 0.4;
        }

        .photo-card.empty-card .photo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .empty-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
        }

        .empty-icon svg {
            width: 2rem;
            height: 2rem;
            opacity: 0.5;
        }

        .back-link {
            margin-top: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.5rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #334155;
            color: #e2e8f0;
            border-color: #6366f1;
        }

        .badge-encrypted {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.5rem;
            padding: 0.25rem 0.6rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #34d399;
            letter-spacing: 0.05em;
        }

        .badge-encrypted svg {
            width: 12px;
            height: 12px;
        }

        .photo-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.3rem;
            padding: 0.2rem 0.5rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #818cf8;
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
            }

            .page-header h1 {
                font-size: 1.2rem;
            }

            .photo-label {
                padding: 0.4rem 0.5rem;
            }

            .photo-label .label-text {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>

    <div class="page-header">
        <h1>📸 Foto Verifikasi Wajah</h1>
        <div class="visitor-name">{{ $visitor->name }}</div>
        <div class="visitor-meta">
            {{ $visitor->identity_type ?? 'ID' }}: {{ $visitor->identity_number ?? '-' }}
            @if($visitor->company)
                &middot; {{ $visitor->company }}
            @endif
        </div>
        <div class="badge-encrypted">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Data Terenkripsi AES-256
        </div>
        <div class="photo-count-badge">
            {{ count($photos) }} foto tersimpan
        </div>
    </div>

    <div class="gallery-grid">
        @for($i = 0; $i < 4; $i++)
            @if(isset($photos[$i]))
                <div class="photo-card">
                    <div class="photo-wrapper">
                        <img src="{{ $photos[$i] }}" alt="{{ $labels[$i] ?? 'Foto ' . ($i + 1) }}" loading="lazy">
                    </div>
                    <div class="photo-label">
                        <span class="label-number">{{ $i + 1 }}</span>
                        <span class="label-text">{{ $labels[$i] ?? 'Foto ' . ($i + 1) }}</span>
                    </div>
                </div>
            @else
                <div class="photo-card empty-card">
                    <div class="photo-wrapper">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                            <span>Belum tersedia</span>
                        </div>
                    </div>
                    <div class="photo-label">
                        <span class="label-number">{{ $i + 1 }}</span>
                        <span class="label-text">{{ $labels[$i] ?? 'Foto ' . ($i + 1) }}</span>
                    </div>
                </div>
            @endif
        @endfor
    </div>

    <a href="javascript:window.close()" class="back-link" onclick="event.preventDefault(); window.close();">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
        Tutup
    </a>

</body>
</html>
