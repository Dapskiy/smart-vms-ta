<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

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
