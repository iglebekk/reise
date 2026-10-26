<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Passkeys::ignoreRoutes();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->email.'|'.$request->ip());
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::loginView(static fn (): View => view('auth.login'));
        Fortify::registerView(static fn (): View => view('auth.register'));
        Fortify::requestPasswordResetLinkView(static fn (): View => view('auth.forgot-password'));
        Fortify::resetPasswordView(static fn (Request $request): View => view('auth.reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));
        Fortify::verifyEmailView(static fn (): View => view('auth.verify-email'));
    }
}
