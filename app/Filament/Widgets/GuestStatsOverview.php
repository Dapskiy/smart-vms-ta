<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GuestStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        Carbon::setLocale('id');
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // ── Optimisasi: 4 query COUNT terpisah → 1 query conditional count ──
        // Cache 2 menit agar dashboard tidak overload saat multi-user akses bersamaan
        $stats = Cache::remember('dashboard_guest_stats_' . $today->toDateString(), 120, function () use ($today) {
            return DB::table('appointments')
                ->selectRaw("
                    COUNT(CASE WHEN visit_date = ? THEN 1 END) as today_total,
                    COUNT(CASE WHEN status IN ('checked_in', 'active') THEN 1 END) as active_count,
                    COUNT(CASE WHEN visit_date = ? AND status IN ('pending', 'approved') THEN 1 END) as waiting_count,
                    COUNT(CASE WHEN status IN ('completed', 'checkout', 'inactive')
                                AND EXTRACT(MONTH FROM visit_date) = ?
                                AND EXTRACT(YEAR FROM visit_date) = ? THEN 1 END) as completed_month
                ", [$today->toDateString(), $today->toDateString(), $today->month, $today->year])
                ->first();
        });

        $todayCount = $stats->today_total ?? 0;
        $activeCount = $stats->active_count ?? 0;
        $waitingCount = $stats->waiting_count ?? 0;
        $completedThisMonth = $stats->completed_month ?? 0;

        // Kemarin tetap query terpisah (ringan, 1 COUNT)
        $yesterdayCount = Cache::remember('dashboard_yesterday_count_' . $yesterday->toDateString(), 300, function () use ($yesterday) {
            return Appointment::whereDate('visit_date', $yesterday)->count();
        });

        $trendTamu = $todayCount - $yesterdayCount;
        $trendTamuIcon = $trendTamu >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendTamuColor = $trendTamu >= 0 ? 'success' : 'danger';
        $trendTamuDesc = $trendTamu >= 0 ? abs($trendTamu) . ' naik dari kemarin' : abs($trendTamu) . ' turun dari kemarin';

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
