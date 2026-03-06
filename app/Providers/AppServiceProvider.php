<?php

namespace App\Providers;

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
        $shouldForceHttps = app()->environment('production');

        if (!$shouldForceHttps && !app()->runningInConsole()) {
            $host = (string) request()->getHost();
            if ($host !== '' && str_ends_with($host, '.onrender.com')) {
                $shouldForceHttps = true;
                URL::forceRootUrl("https://{$host}");
            }
        }

        if ($shouldForceHttps) {
            $appUrl = (string) config('app.url', '');
            if ($appUrl !== '' && !str_contains($appUrl, '.onrender.com')) {
                $forcedUrl = preg_replace('/^http:/i', 'https:', $appUrl);
                URL::forceRootUrl($forcedUrl ?: $appUrl);
            }
            URL::forceScheme('https');
        }
    }
}
