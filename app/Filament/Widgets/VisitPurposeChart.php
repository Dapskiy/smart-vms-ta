<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class VisitPurposeChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Keperluan Kunjungan';
    protected ?string $description = 'Proporsi tujuan kunjungan dari semua tamu yang telah selesai';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 2;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $currentUser = auth()->user();
        $isPic = $currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic;
        $picId = $isPic ? $currentUser->pic->id : null;

        $cacheKey = 'dashboard_visit_purpose' . ($isPic ? '_pic_' . $picId : '_admin');

        // Cache 5 menit — data distribusi keperluan berubah jarang
        $results = Cache::remember($cacheKey, 300, function () use ($isPic, $picId) {
            $query = Appointment::query()
                ->whereIn('status', ['completed', 'checkout', 'inactive'])
                ->whereNotNull('purpose')
                ->where('purpose', '!=', '');
            
            if ($isPic) {
                $query->where('pic_id', $picId);
            }
            
            return $query->selectRaw('purpose, COUNT(*) as total')
                ->groupBy('purpose')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'purpose')
                ->toArray();
        });

        // Jika tidak ada data, tampilkan placeholder
        if (empty($results)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['rgba(107, 114, 128, 0.4)'],
                        'borderColor' => ['rgba(107, 114, 128, 0.6)'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => ['Belum ada data'],
            ];
        }

        // Palet warna elegan yang kompatibel dengan dark mode
        $colorPalette = [
            'rgba(99, 102, 241, 0.85)',   // Indigo  - primary
            'rgba(16, 185, 129, 0.85)',   // Emerald - success
            'rgba(245, 158, 11, 0.85)',   // Amber   - warning
            'rgba(239, 68, 68, 0.85)',    // Red     - danger
            'rgba(6, 182, 212, 0.85)',    // Cyan    - info
            'rgba(168, 85, 247, 0.85)',   // Purple
            'rgba(249, 115, 22, 0.85)',   // Orange
            'rgba(20, 184, 166, 0.85)',   // Teal
        ];

        $borderPalette = [
            'rgb(99, 102, 241)',
            'rgb(16, 185, 129)',
            'rgb(245, 158, 11)',
            'rgb(239, 68, 68)',
            'rgb(6, 182, 212)',
            'rgb(168, 85, 247)',
            'rgb(249, 115, 22)',
            'rgb(20, 184, 166)',
        ];

        $labels = array_keys($results);
        $data   = array_values($results);
        $colors = array_slice($colorPalette, 0, count($labels));
        $borders = array_slice($borderPalette, 0, count($labels));

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
                    'borderWidth' => 2,
                    'hoverBorderWidth' => 3,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'padding' => 16,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'cutout' => '68%',
            'radius' => '90%',
            'maintainAspectRatio' => false,
        ];
    }
}
