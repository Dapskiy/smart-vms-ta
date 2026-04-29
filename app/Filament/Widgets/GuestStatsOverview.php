<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuestStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('TAMU HARI INI', '47')
                ->description('12% dari kemarin')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('SEDANG BERKUNJUNG', '18')
                ->description('3 dari sejam lalu')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('MENUNGGU MASUK', '5')
                ->description('2 dari kemarin')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('RATA-RATA DURASI', '45m')
                ->description('stabil')
                ->descriptionIcon('heroicon-m-minus')
                ->color('success'),
        ];
    }
}
