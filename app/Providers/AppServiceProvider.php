<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Google2FA::class, fn () => new Google2FA);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->useLog('auth')
                    ->withProperties(['ip' => request()->ip()])
                    ->log('login');
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->useLog('auth')
                    ->withProperties(['ip' => request()->ip()])
                    ->log('logout');
            }
        });
    }
}
