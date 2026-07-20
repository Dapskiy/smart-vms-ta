<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Jadikan APP_URL dinamis sesuai IP/Host yang mengakses (berguna saat diakses via IP LAN)
        if (request()->server('HTTP_HOST')) {
            $scheme = request()->isSecure() ? 'https://' : 'http://';
            $url = $scheme . request()->server('HTTP_HOST');
            \Illuminate\Support\Facades\URL::forceRootUrl($url);
            config(['app.url' => $url]);
        }

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

    // Reset PIC availability at 00:00 daily
    if (
        Schema::hasTable('pics') &&
        cache()->get('pic_availability_reset_date') !== today()->toDateString()
    ) {
        \App\Models\Pic::query()->update([
            'is_available' => false
        ]);

        cache()->forever(
            'pic_availability_reset_date',
            today()->toDateString()
        );
    }

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::body.end',
            fn () => new \Illuminate\Support\HtmlString("
                <script>
                    window.addEventListener('copy-to-clipboard', event => {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(event.detail.text);
                        } else {
                            const textArea = document.createElement('textarea');
                            textArea.value = event.detail.text;
                            document.body.appendChild(textArea);
                            textArea.select();
                            try {
                                document.execCommand('copy');
                            } catch (err) {
                                console.error('Fallback copy failed', err);
                            }
                            document.body.removeChild(textArea);
                        }
                    });
                </script>
            ")
        );
    }
}
