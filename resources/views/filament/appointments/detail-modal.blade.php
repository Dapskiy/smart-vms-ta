@php
    use Carbon\Carbon;
    $companions = $record->companions ?? [];
    $visitDate  = $record->visit_date  ? Carbon::parse($record->visit_date)->translatedFormat('d F Y') : '-';
    $visitTime  = $record->visit_time  ? substr($record->visit_time, 0, 5) : '-';

    $typeLabel = match($record->type) {
        'appointment' => 'Appointment',
        'walk-in'     => 'Walk-in',
        default       => ucfirst($record->type ?? '-'),
    };
    $typeBg = match($record->type) {
        'appointment' => '#dcfce7; color: #166534',
        'walk-in'     => '#fef9c3; color: #854d0e',
        default       => '#f3f4f6; color: #374151',
    };
    $statusLabel = match($record->status) {
        'pending'   => 'Menunggu',
        'active'    => 'Di Dalam',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        default     => ucfirst($record->status ?? '-'),
    };
    $statusBg = match($record->status) {
        'pending'   => '#fef9c3; color: #854d0e',
        'active'    => '#dcfce7; color: #166534',
        'completed' => '#f3f4f6; color: #374151',
        'cancelled' => '#fee2e2; color: #991b1b',
        default     => '#f3f4f6; color: #374151',
    };
@endphp

<style>
    .vd-wrap        { font-family: inherit; padding: 4px 0; }
    .vd-badges      { display: flex; gap: 8px; margin-bottom: 16px; }
    .vd-badge       { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .vd-grid        { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

    /* Light mode */
    .vd-card        { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #f9fafb; }
    .vd-card-title  { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .vd-dl          { display: flex; flex-direction: column; gap: 6px; }
    .vd-row         { display: flex; font-size: 13px; gap: 4px; }
    .vd-dt          { color: #6b7280; width: 140px; flex-shrink: 0; }
    .vd-dd          { color: #111827; font-weight: 500; }
    .vd-table-wrap  { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
    .vd-table-head  { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #ffffff; }
    .vd-table-title { font-size: 13px; font-weight: 600; color: #374151; }
    .vd-count-badge { font-size: 11px; font-weight: 600; background: #eff6ff; color: #1d4ed8; padding: 2px 10px; border-radius: 999px; }
    .vd-table       { width: 100%; border-collapse: collapse; font-size: 13px; }
    .vd-table thead tr { background: #f9fafb; }
    .vd-table th    { padding: 8px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .vd-table td    { padding: 10px 16px; color: #374151; border-top: 1px solid #f3f4f6; }
    .vd-utama       { display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #dcfce7; color: #166534; margin-left: 6px; }
    .vd-name        { font-weight: 500; color: #111827; }
    .vd-muted       { color: #9ca3af; }
    .vd-token-row   { display: flex; align-items: center; gap: 6px; }
    .vd-copy-btn    { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; transition: all .15s ease; }
    .vd-copy-btn:hover { background: #f3f4f6; border-color: #9ca3af; }
    .vd-copy-btn.copied { background: #dcfce7; border-color: #86efac; color: #166534; }

    /* Dark mode — Filament adds class="dark" on <html> */
    .dark .vd-card          { background: #1f2937; border-color: #374151; }
    .dark .vd-card-title    { color: #e5e7eb; border-bottom-color: #374151; }
    .dark .vd-dt            { color: #9ca3af; }
    .dark .vd-dd            { color: #f3f4f6; }
    .dark .vd-table-wrap    { border-color: #374151; }
    .dark .vd-table-head    { background: #1f2937; border-bottom-color: #374151; }
    .dark .vd-table-title   { color: #e5e7eb; }
    .dark .vd-count-badge   { background: #1e3a5f; color: #93c5fd; }
    .dark .vd-table thead tr { background: #111827; }
    .dark .vd-table th      { color: #9ca3af; }
    .dark .vd-table td      { color: #d1d5db; border-top-color: #1f2937; }
    .dark .vd-name          { color: #f3f4f6; }
    .dark .vd-utama         { background: #14532d; color: #86efac; }
    .dark .vd-muted         { color: #6b7280; }
    .dark .vd-copy-btn      { background: #374151; border-color: #4b5563; color: #d1d5db; }
    .dark .vd-copy-btn:hover { background: #4b5563; }
    .dark .vd-copy-btn.copied { background: #14532d; border-color: #166534; color: #86efac; }

    @media (max-width: 640px) { .vd-grid { grid-template-columns: 1fr; } }
</style>

<div class="vd-wrap">

    {{-- Badge Tipe & Status --}}
    <div class="vd-badges">
        <span class="vd-badge" style="background: {{ $typeBg }}">{{ $typeLabel }}</span>
        <span class="vd-badge" style="background: {{ $statusBg }}">{{ $statusLabel }}</span>
    </div>

    {{-- Grid 2 Kolom --}}
    <div class="vd-grid">

        {{-- Kiri: Data Administratif --}}
        <div class="vd-card">
            <div class="vd-card-title">Data Administratif</div>
            <div class="vd-dl">
                @foreach([
                    'Tanggal'         => $visitDate,
                    'Jam Kunjungan'   => $visitTime,
                    'Instansi'        => $record->visitor?->company ?? '-',
                    'Nopol Kendaraan' => $record->vehicle_number ?? '-',
                ] as $label => $value)
                <div class="vd-row">
                    <span class="vd-dt">{{ $label }}</span>
                    <span class="vd-dd">: {{ $value }}</span>
                </div>
                @endforeach
                {{-- Token dengan tombol copy --}}
                <div class="vd-row">
                    <span class="vd-dt">Token</span>
                    <span class="vd-dd vd-token-row">
                        : {{ $record->token ?? '-' }}
                        @if($record->token)
                        <button
                            class="vd-copy-btn"
                            onclick="
                                navigator.clipboard.writeText('{{ $record->token }}');
                                this.classList.add('copied');
                                this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'20 6 9 17 4 12\'></polyline></svg> Tersalin!';
                                setTimeout(() => {
                                    this.classList.remove('copied');
                                    this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><rect x=\'9\' y=\'9\' width=\'13\' height=\'13\' rx=\'2\' ry=\'2\'></rect><path d=\'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1\'></path></svg> Copy';
                                }, 2000);
                            ">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            Copy
                        </button>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Kanan: Detail Tujuan Kunjungan --}}
        <div class="vd-card">
            <div class="vd-card-title">Detail Tujuan Kunjungan</div>
            <table class="vd-table">
                <thead>
                    <tr>
                        <th>Ruangan</th>
                        <th>PIC</th>
                        <th>Keperluan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $record->room?->name ?? '-' }}</td>
                        <td>{{ $record->pic?->name ?? '-' }}</td>
                        <td>{{ $record->purpose ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    {{-- Informasi Personal Pengunjung --}}
    <div class="vd-table-wrap">
        <div class="vd-table-head">
            <span class="vd-table-title">Informasi Personal Pengunjung</span>
            <span class="vd-count-badge">Total: {{ 1 + count($companions) }} orang</span>
        </div>
        <table class="vd-table">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Nama</th>
                    <th>Identitas</th>
                    <th>No. Telepon</th>
                </tr>
            </thead>
            <tbody>
                {{-- Tamu Utama --}}
                <tr>
                    <td class="vd-muted">1</td>
                    <td>
                        <span class="vd-name">{{ $record->visitor?->name ?? '-' }}</span>
                        <span class="vd-utama">Utama</span>
                    </td>
                    <td>
                        @if($record->visitor?->identity_type)
                            {{ $record->visitor->identity_type }}{{ $record->visitor->identity_number ? ' — ' . $record->visitor->identity_number : '' }}
                        @else
                            <span class="vd-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $record->visitor?->phone ?? '-' }}</td>
                </tr>

                {{-- Anggota Rombongan --}}
                @foreach($companions as $i => $companion)
                <tr>
                    <td class="vd-muted">{{ $i + 2 }}</td>
                    <td><span class="vd-name">{{ $companion['name'] ?? '-' }}</span></td>
                    <td class="vd-muted">—</td>
                    <td class="vd-muted">—</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
