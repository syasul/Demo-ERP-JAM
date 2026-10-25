<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
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
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Blade::directive('rupiah', function ($expression) {
            return "<?php echo \App\Support\Money::format((int) ({$expression})); ?>";
        });
    }
}
