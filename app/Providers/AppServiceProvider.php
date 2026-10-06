<?php

namespace App\Providers;



use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = config('app.url');

        if (! empty($appUrl)) {
            $scheme = parse_url((string) $appUrl, PHP_URL_SCHEME) ?: 'https';
            URL::forceRootUrl(rtrim((string) $appUrl, '/'));
            URL::forceScheme($scheme);
        }

        if (app()->environment('production')) {
            config()->set('session.secure', true);
        }
    }
}
