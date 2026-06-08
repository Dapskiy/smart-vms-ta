<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AdminAiChatWidget extends Widget
{
    /**
     * Widget ini dirender di luar grid kolom standar Filament
     * via renderHook panels::body.end di AdminPanelProvider.
     */
    protected static bool $isLazy = false;

    public static function getSort(): int
    {
        return 99;
    }

    public function getView(): string
    {
        return 'filament.widgets.admin-chat-widget';
    }
}
