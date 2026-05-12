<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GuestStatsOverview;
use App\Filament\Widgets\LatestGuestsTable;
use App\Filament\Widgets\VisitTrendChart;
use App\Filament\Widgets\VisitPurposeChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Carbon\Carbon;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string | Htmlable
    {
        return 'Dashboard Tamu';
    }

    public function getSubheading(): string | Htmlable | null
    {
        Carbon::setLocale('id');
        $date = Carbon::now()->translatedFormat('l, d F Y');
        $time = Carbon::now()->hour < 12 ? 'Pagi' : (Carbon::now()->hour < 18 ? 'Sore' : 'Malam');
        return $date . ' — ' . $time . ' ini';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('daftarkan_tamu')
                ->label('+ Daftarkan Tamu')
                ->color('primary'),
        ];
    }

    /**
     * Urutan layout Dashboard:
     * 1. GuestStatsOverview  — Stats cards (sort: 1)
     * 2. VisitTrendChart     — Line chart, full width (sort: 3)
     * 3. VisitPurposeChart   — Doughnut chart, lebar 2 kolom (sort: 4)
     * 4. LatestGuestsTable   — Tabel tamu, full width (sort: 2 -> dinaikkan ke 5)
     */
    public function getWidgets(): array
    {
        return [
            GuestStatsOverview::class,
            VisitTrendChart::class,
            VisitPurposeChart::class,
            LatestGuestsTable::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'sm'  => 1,
            'md'  => 2,
            'xl'  => 4,
        ];
    }
}
