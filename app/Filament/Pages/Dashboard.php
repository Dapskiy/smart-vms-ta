<?php

namespace App\Filament\Pages;

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
}
