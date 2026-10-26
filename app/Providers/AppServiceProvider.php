<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));

        RateLimiter::for('mcp', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by('mcp:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
