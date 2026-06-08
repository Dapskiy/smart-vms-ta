<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AdminAiChatWidget extends Widget
{
    /**
     * Widget ini dirender via renderHook 'panels::body.end' di AdminPanelProvider
     * sebagai floating element — tidak masuk ke dalam grid widget halaman.
     *
     * Karena dirender via renderHook (bukan widget grid), class ini cukup
     * bertindak sebagai class placeholder. View sesungguhnya diregister di
     * AdminPanelProvider::panel() menggunakan ->renderHook(..., fn() => view(...))
     */
    protected static bool $isLazy = false;

    /**
     * Hanya tampil jika user sudah terautentikasi sebagai admin.
     * Filament sudah melindungi panel dengan auth middleware, method ini
     * sebagai double-check untuk keamanan ekstra.
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * Urutan render tinggi agar dimuat setelah semua widget lain.
     */
    public static function getSort(): int
    {
        return 99;
    }

    /**
     * Override getView() — lebih safe daripada static $view
     * karena tidak konflik dengan parent Widget::$view (non-static).
     */
    public function getView(): string
    {
        return 'filament.widgets.admin-chat-widget';
    }
}
