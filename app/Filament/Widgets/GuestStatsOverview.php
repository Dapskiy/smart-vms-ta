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

        $currentUser = auth()->user();
        $isPic = $currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic;
        $picId = $isPic ? $currentUser->pic->id : null;

        $cacheKey = 'dashboard_guest_stats_' . $today->toDateString() . ($isPic ? '_pic_' . $picId : '_admin');

        // ── Optimisasi: 4 query COUNT terpisah → 1 query conditional count ──
        $stats = Cache::remember($cacheKey, 60, function () use ($today, $isPic, $picId) {
            $query = DB::table('appointments');
            if ($isPic) {
                $query->where('pic_id', $picId);
            }
            
            return $query->selectRaw("
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

        $yesterdayCacheKey = 'dashboard_yesterday_count_' . $yesterday->toDateString() . ($isPic ? '_pic_' . $picId : '_admin');
        $yesterdayCount = Cache::remember($yesterdayCacheKey, 60, function () use ($yesterday, $isPic, $picId) {
            $q = Appointment::whereDate('visit_date', $yesterday);
            if ($isPic) {
                $q->where('pic_id', $picId);
            }
            return $q->count();
        });

        $trendTamu = $todayCount - $yesterdayCount;
        $trendTamuIcon = $trendTamu >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendTamuColor = $trendTamu >= 0 ? 'success' : 'danger';
        $trendTamuDesc = $trendTamu >= 0 ? abs($trendTamu) . ' naik dari kemarin' : abs($trendTamu) . ' turun dari kemarin';

        // 5. Dummy data array untuk Sparkline chart (agar visual lebih keren)
        $sparklineData = [7, 4, 6, 10, 14, 7, $todayCount];

        // ── Metrik Akurasi Biometrik (Face Logs) ──
        $totalScans = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])->count();
        $successScans = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])->where('is_success', true)->count();
        $successRate = $totalScans > 0 ? round(($successScans / $totalScans) * 100, 1) : 100.0;
        
        $avgDistance = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])
            ->where('is_success', true)
            ->avg('euclidean_distance');
        $avgDistanceStr = $avgDistance !== null ? number_format($avgDistance, 4) : '0.0000';

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

            Stat::make('Akurasi Scan Wajah', $successRate . '%')
                ->description($totalScans . ' total pemindaian wajah')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Rata-rata Euclidean Distance', $avgDistanceStr)
                ->description('Threshold batas: 0.50')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }
}
