<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — VISITA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Status-based backgrounds */
        body.bg-approved  { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
        body.bg-rejected  { background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); }
        body.bg-error     { background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%); }
        body.bg-already   { background: linear-gradient(135deg, #f0f9ff 0%, #bae6fd 100%); }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-width: 480px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
        }
        .icon-approved  { background: #d1fae5; }
        .icon-rejected  { background: #fecaca; }
        .icon-error     { background: #fef3c7; }
        .icon-already   { background: #dbeafe; }

        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1e293b;
        }

        .message {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-bottom: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; font-weight: 500; }
        .info-value { color: #1e293b; font-weight: 600; }

        .footer-text {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 16px;
        }
    </style>
</head>
<body class="{{ match($status) {
    'approved' => 'bg-approved',
    'rejected' => 'bg-rejected',
    'error'    => 'bg-error',
    default    => 'bg-already',
} }}">
    <div class="card">
        <div class="icon {{ match($status) {
            'approved' => 'icon-approved',
            'rejected' => 'icon-rejected',
            'error'    => 'icon-error',
            default    => 'icon-already',
        } }}">
            {{ match($status) {
                'approved' => '✅',
                'rejected' => '❌',
                'error'    => '⚠️',
                default    => 'ℹ️',
            } }}
        </div>

        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        @if(isset($appointment))
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Nama Tamu</span>
                    <span class="info-value">{{ $appointment->visitor?->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Perusahaan</span>
                    <span class="info-value">{{ $appointment->visitor?->company ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Keperluan</span>
                    <span class="info-value">{{ $appointment->purpose ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PIC</span>
                    <span class="info-value">{{ $appointment->pic?->name ?? '-' }}</span>
                </div>
                @if($status === 'approved' && $appointment->check_in_time)
                    <div class="info-row">
                        <span class="info-label">Jam Check-in</span>
                        <span class="info-value">{{ $appointment->check_in_time }}</span>
                    </div>
                @endif
            </div>
        @endif

        <p class="footer-text">VISITA Visitor Management System</p>
    </div>
</body>
</html>
