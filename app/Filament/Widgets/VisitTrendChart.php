<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Kunjungan 7 Hari Terakhir';
    protected ?string $description = 'Jumlah tamu yang telah menyelesaikan kunjungan';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        Carbon::setLocale('id');

        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();

        // ── Optimisasi: 7 query dalam loop → 1 query GROUP BY ──
        // Cache 5 menit (data historis berubah jarang)
        $results = Cache::remember(
            'dashboard_visit_trend_' . $endDate->toDateString(),
            300,
            function () use ($startDate, $endDate) {
                return DB::table('appointments')
                    ->selectRaw('visit_date::date as date, COUNT(*) as total')
                    ->whereIn('status', ['completed', 'checkout', 'inactive'])
                    ->whereBetween('visit_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->groupBy(DB::raw('visit_date::date'))
                    ->pluck('total', 'date')
                    ->toArray();
            }
        );

        $labels = [];
        $data = [];

        // Bangun array 7 hari (termasuk hari tanpa data = 0)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();

            $labels[] = $date->translatedFormat('d M'); // contoh: "06 Mei"
            $data[] = $results[$dateStr] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tamu Selesai',
                    'data' => $data,
                    'borderColor' => 'rgb(99, 102, 241)',       // Indigo-500
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(99, 102, 241)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
