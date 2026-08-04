<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Kunjungan — VISITA</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; padding:40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:16px; box-shadow:0 10px 25px rgba(15, 23, 42, 0.05); overflow:hidden; border: 1px solid #e2e8f0;">
                    
                    {{-- Header --}}
                    <tr>
                        <td style="background: url('https://www.transparenttextures.com/patterns/cubes.png'), linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); padding:40px 32px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:28px; font-weight:800; letter-spacing:-0.5px;">
                                VISITA<span style="font-weight: 300; opacity: 0.9;">VMS</span>
                            </h1>
                            <p style="margin:8px 0 0; color:#e0e7ff; font-size:14px; font-weight:500; letter-spacing:0.5px; text-transform:uppercase;">
                                Permintaan Kunjungan Baru
                            </p>
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:32px 32px 16px; text-align:center;">
                            <div style="display:inline-block; padding:12px; background:#eff6ff; border-radius:50%; margin-bottom:16px;">
                                <span style="font-size:24px;">👋</span>
                            </div>
                            <h2 style="margin:0 0 8px; color:#0f172a; font-size:20px; font-weight:700;">
                                Halo, {{ $pic->name ?? 'PIC' }}
                            </h2>
                            <p style="margin:0; color:#475569; font-size:15px; line-height:1.6;">
                                Ada tamu yang sedang menunggu persetujuan Anda di lobby.
                            </p>
                        </td>
                    </tr>

                    {{-- Visitor Details Card --}}
                    <tr>
                        <td style="padding:16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                                <tr>
                                    <td style="padding:24px;">
                                        
                                        <!-- Row 1: Nama & Perusahaan -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                            <tr>
                                                <td width="50%" valign="top">
                                                    <p style="margin:0 0 4px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Nama Tamu</p>
                                                    <p style="margin:0; color:#0f172a; font-size:16px; font-weight:700;">{{ $visitor->name ?? '-' }}</p>
                                                </td>
                                                <td width="50%" valign="top">
                                                    <p style="margin:0 0 4px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Instansi</p>
                                                    <p style="margin:0; color:#0f172a; font-size:15px; font-weight:600;">{{ $visitor->company ?? '-' }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <!-- Divider -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:16px;"></td></tr><tr><td style="height:16px;"></td></tr></table>

                                        <!-- Row 2: Keperluan & Pax -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                            <tr>
                                                <td width="50%" valign="top">
                                                    <p style="margin:0 0 4px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Keperluan</p>
                                                    <p style="margin:0; color:#0f172a; font-size:15px; font-weight:600;">{{ $appointment->purpose ?? '-' }}</p>
                                                </td>
                                                <td width="50%" valign="top">
                                                    <p style="margin:0 0 4px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Jumlah Orang</p>
                                                    <p style="margin:0; color:#0f172a; font-size:15px; font-weight:600;">
                                                        <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1d4ed8; border-radius:12px; font-size:13px;">{{ $appointment->pax ?? 1 }} Pax</span>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Divider -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:16px;"></td></tr><tr><td style="height:16px;"></td></tr></table>

                                        <!-- Row 3: Waktu -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="100%" valign="top">
                                                    <p style="margin:0 0 4px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Waktu Permintaan</p>
                                                    <p style="margin:0; color:#0f172a; font-size:15px; font-weight:600;">
                                                        @if($appointment->type === 'walk-in' || $appointment->type === 'walkin')
                                                            {{ \Carbon\Carbon::parse($appointment->created_at)->translatedFormat('d F Y • H:i') }} WIB
                                                        @else
                                                            {{ \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y') }} • {{ \Carbon\Carbon::parse($appointment->visit_time)->format('H:i') }} WIB
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Action Buttons --}}
                    <tr>
                        <td style="padding:16px 32px 40px;">
                            <p style="margin:0 0 20px; color:#475569; font-size:14px; text-align:center;">
                                Silakan respon permintaan ini untuk memberikan akses masuk.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:0 8px 0 0; width:50%;">
                                        <a href="{{ $approveUrl }}" 
                                           style="display:block; padding:14px 20px; background:#10b981; color:#ffffff; text-decoration:none; border-radius:10px; font-size:15px; font-weight:700; text-align:center; box-shadow:0 4px 6px rgba(16, 185, 129, 0.25);">
                                            Terima Kunjungan
                                        </a>
                                    </td>
                                    <td align="center" style="padding:0 0 0 8px; width:50%;">
                                        <a href="{{ $rejectUrl }}" 
                                           style="display:block; padding:14px 20px; background:#ffffff; color:#ef4444; border:1px solid #ef4444; text-decoration:none; border-radius:10px; font-size:15px; font-weight:700; text-align:center;">
                                            Tolak Kunjungan
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px; background:#f1f5f9; border-top:1px solid #e2e8f0; text-align:center;">
                            <p style="margin:0 0 8px; color:#94a3b8; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase;">
                                VISITA VMS System
                            </p>
                            <p style="margin:0; color:#94a3b8; font-size:11px; line-height:1.6;">
                                Email ini dikirim otomatis karena ada tamu di Kiosk.<br>
                                Harap segera merespon agar tamu tidak menunggu terlalu lama.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Container -->
                
                <table role="presentation" width="600" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px; text-align:center;">
                            <p style="margin:0; color:#cbd5e1; font-size:11px;">
                                &copy; {{ date('Y') }} Visitor Management System. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
