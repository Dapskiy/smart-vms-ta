<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Kunjungan — VISITA</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); overflow:hidden;">
                    
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%); padding:28px 32px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:-0.3px;">
                                🏢 VISITA VMS
                            </h1>
                            <p style="margin:6px 0 0; color:rgba(255,255,255,0.85); font-size:13px;">
                                Visitor Management System
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px 32px;">
                            <h2 style="margin:0 0 6px; color:#1e293b; font-size:18px; font-weight:600;">
                                Permintaan Kunjungan Baru
                            </h2>
                            <p style="margin:0 0 20px; color:#64748b; font-size:14px; line-height:1.5;">
                                Halo <strong>{{ $pic->name ?? 'PIC' }}</strong>,<br>
                                Seorang tamu ingin menemui Anda dan membutuhkan persetujuan untuk masuk.
                            </p>

                            {{-- Visitor Info Card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                    Nama Tamu
                                                </td>
                                                <td style="padding:6px 0; color:#1e293b; font-size:14px; font-weight:600; text-align:right;">
                                                    {{ $visitor->name ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-bottom:1px solid #e2e8f0; padding:0; height:1px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                    Perusahaan
                                                </td>
                                                <td style="padding:6px 0; color:#1e293b; font-size:14px; text-align:right;">
                                                    {{ $visitor->company ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-bottom:1px solid #e2e8f0; padding:0; height:1px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                    Keperluan
                                                </td>
                                                <td style="padding:6px 0; color:#1e293b; font-size:14px; text-align:right;">
                                                    {{ $appointment->purpose ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-bottom:1px solid #e2e8f0; padding:0; height:1px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                    Jumlah Rombongan
                                                </td>
                                                <td style="padding:6px 0; color:#1e293b; font-size:14px; text-align:right;">
                                                    {{ $appointment->pax ?? 1 }} orang
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-bottom:1px solid #e2e8f0; padding:0; height:1px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                    Waktu Datang
                                                </td>
                                                <td style="padding:6px 0; color:#1e293b; font-size:14px; text-align:right;">
                                                    {{ now()->translatedFormat('d F Y, H:i') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Action Buttons --}}
                            <p style="margin:0 0 16px; color:#64748b; font-size:13px; text-align:center;">
                                Silakan pilih tindakan Anda:
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:0 8px 0 0; width:50%;">
                                        <a href="{{ $approveUrl }}" 
                                           style="display:block; padding:14px 24px; background:#10b981; color:#ffffff; text-decoration:none; border-radius:8px; font-size:15px; font-weight:700; text-align:center; letter-spacing:0.3px;">
                                            ✅ Setuju
                                        </a>
                                    </td>
                                    <td align="center" style="padding:0 0 0 8px; width:50%;">
                                        <a href="{{ $rejectUrl }}" 
                                           style="display:block; padding:14px 24px; background:#ef4444; color:#ffffff; text-decoration:none; border-radius:8px; font-size:15px; font-weight:700; text-align:center; letter-spacing:0.3px;">
                                            ❌ Tolak
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:center;">
                            <p style="margin:0; color:#94a3b8; font-size:11px; line-height:1.5;">
                                Email ini dikirim otomatis oleh sistem VISITA VMS.<br>
                                Harap segera merespon — tamu sedang menunggu di Kiosk.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
