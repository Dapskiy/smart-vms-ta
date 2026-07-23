<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hybrid Visitor E-Pass — {{ $visitor?->name ?? 'Tamu' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-800 border border-slate-700 rounded-3xl shadow-2xl overflow-hidden text-slate-100">
        
        <!-- Header Badge -->
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white font-black text-xl backdrop-blur-sm">
                    V
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white leading-tight">VISITA ENTERPRISE</h1>
                    <p class="text-xs text-indigo-100 font-medium">Hybrid Visitor E-Pass Card</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold rounded-full uppercase tracking-wider">
                {{ strtoupper($appointment->status ?? 'ACTIVE') }}
            </span>
        </div>

        <!-- Main Card Body -->
        <div class="p-6 space-y-6">
            
            <!-- Visitor Profile Section -->
            <div class="flex items-start space-x-4 pb-5 border-b border-slate-700/60">
                <div class="w-16 h-16 rounded-2xl bg-indigo-950 border-2 border-indigo-500/40 flex items-center justify-center text-indigo-300 text-2xl font-bold overflow-hidden shadow-inner">
                    @if($visitor && $visitor->face_photo_url)
                        <img src="{{ $visitor->face_photo_url }}" alt="{{ $visitor->name }}" class="w-full h-full object-cover">
                    @else
                        <span>{{ substr($visitor?->name ?? 'V', 0, 1) }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-white truncate">{{ $visitor?->name ?? '-' }}</h2>
                    <p class="text-sm text-slate-400 font-medium truncate">{{ $visitor?->company ?? 'Instansi Umum' }}</p>
                    <div class="mt-2 inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-md bg-slate-700/60 text-xs font-mono text-indigo-300 border border-slate-600/50">
                        <span>ID Kunjungan:</span>
                        <strong class="text-white">{{ $appointment->visit_id ?? 'VST-PENDING' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Visit Details Grid -->
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700/40">
                    <span class="text-xs text-slate-400 block font-medium">Menemui (PIC)</span>
                    <strong class="text-slate-100 font-semibold block mt-0.5">{{ $pic?->name ?? '-' }}</strong>
                </div>

                <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700/40">
                    <span class="text-xs text-slate-400 block font-medium">Ruang Meeting</span>
                    <strong class="text-slate-100 font-semibold block mt-0.5">{{ $room?->name ?? 'Lobi Utama' }}</strong>
                </div>

                <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700/40">
                    <span class="text-xs text-slate-400 block font-medium">Tanggal Kunjungan</span>
                    <strong class="text-slate-100 font-semibold block mt-0.5">{{ $appointment->visit_date?->translatedFormat('d F Y') ?? '-' }}</strong>
                </div>

                <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700/40">
                    <span class="text-xs text-slate-400 block font-medium">Jam Rencana</span>
                    <strong class="text-slate-100 font-semibold block mt-0.5">{{ $appointment->visit_time ?? '-' }} WIB</strong>
                </div>
            </div>

            <!-- Purpose -->
            <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700/40 text-sm">
                <span class="text-xs text-slate-400 block font-medium">Keperluan / Tujuan</span>
                <p class="text-slate-200 mt-1 text-xs leading-relaxed">{{ $appointment->purpose ?? '-' }}</p>
            </div>

            <!-- HYBRID ACCESS INSTRUCTIONS -->
            <div class="space-y-4 pt-2">
                
                <!-- Primary Mode: Touchless Face Scan -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-950/80 to-slate-900 border border-indigo-500/30 flex items-start space-x-3.5 shadow-sm">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5 border border-indigo-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wide">Metode Utama (Touchless Face Scan)</h3>
                            <span class="px-1.5 py-0.5 text-[10px] bg-indigo-500/20 text-indigo-300 rounded font-semibold">PRIMARY</span>
                        </div>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            Cukup berdiri di depan kamera Kiosk lobi. Sistem <strong>Active Liveness Detection</strong> akan memverifikasi wajah Anda secara otomatis tanpa perlu menyentuh HP atau layar.
                        </p>
                    </div>
                </div>

                <!-- Secondary Mode: Fallback QR Code -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center shrink-0 mt-0.5 border border-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wide">Metode Cadangan (Fallback QR Pass)</h3>
                            <span class="px-1.5 py-0.5 text-[10px] bg-slate-700 text-slate-300 rounded font-semibold">FAILOVER</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Jika scan wajah bermasalah (foto HP <em>low-res</em>), tekan <strong>"Scan QR"</strong> di Kiosk dan tunjukkan QR Code / Token di bawah ini. Kiosk akan otomatis mendaftarkan ulang foto wajah Anda.
                        </p>

                        <!-- Embedded QR SVG / Code Box -->
                        <div class="mt-3 bg-white p-3 rounded-xl flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($appointment->token) }}" alt="QR Pass" class="w-16 h-16 rounded-lg border border-slate-200">
                                <div>
                                    <span class="text-[10px] text-slate-500 font-mono block">QR PASS TOKEN:</span>
                                    <code class="text-xs font-bold text-slate-900 font-mono tracking-wider block mt-0.5">{{ $appointment->token }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <div class="bg-slate-900/80 px-6 py-4 border-t border-slate-700/60 text-center">
            <p class="text-[11px] text-slate-400">
                Dokumen ini diterbitkan otomatis oleh <strong>VISITA Smart VMS</strong>. Berlaku untuk tanggal kunjungan di atas.
            </p>
        </div>

    </div>

</body>
</html>
