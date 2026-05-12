<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuestStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        Carbon::setLocale('id');
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Hitung Tamu Hari Ini & Kemarin (Trend)
        $todayCount = Appointment::whereDate('visit_date', $today)->count();
        $yesterdayCount = Appointment::whereDate('visit_date', $yesterday)->count();
        $trendTamu = $todayCount - $yesterdayCount;
        $trendTamuIcon = $trendTamu >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendTamuColor = $trendTamu >= 0 ? 'success' : 'danger';
        $trendTamuDesc = $trendTamu >= 0 ? abs($trendTamu) . ' naik dari kemarin' : abs($trendTamu) . ' turun dari kemarin';

        // 2. Hitung Sedang Berkunjung (Active / Checked In)
        $activeCount = Appointment::whereIn('status', ['checked_in', 'active'])->count();

        // 3. Hitung Menunggu Masuk (Pending / Approved hari ini)
        $waitingCount = Appointment::whereIn('status', ['pending', 'approved'])
            ->whereDate('visit_date', $today)
            ->count();

        // 4. Total Selesai Bulan Ini
        $completedThisMonth = Appointment::whereIn('status', ['completed', 'checkout', 'inactive'])
            ->whereMonth('visit_date', $today->month)
            ->whereYear('visit_date', $today->year)
            ->count();

        // 5. Dummy data array untuk Sparkline chart (agar visual lebih keren)
        $sparklineData = [7, 4, 6, 10, 14, 7, $todayCount];

        return [
            Stat::make('Tamu Hari Ini', $todayCount)
                ->description($trendTamuDesc)
                ->descriptionIcon($trendTamuIcon)
                ->chart($sparklineData)
                ->color($trendTamuColor),

            Stat::make('Sedang Berkunjung', $activeCount)
                ->description('Tamu di dalam area')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Menunggu Masuk', $waitingCount)
                ->description('Jadwal belum check-in')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Selesai (Bulan Ini)', $completedThisMonth)
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
