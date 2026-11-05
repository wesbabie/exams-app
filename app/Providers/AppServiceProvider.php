<?php

namespace App\Providers;

use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One reader of the system settings per request
        $this->app->singleton(\App\Support\Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Staff sign-ins. Candidate and applicant sign-ins have their own records.
        Event::listen(Login::class, function (Login $e) {
            if ($e->guard === 'web') {
                Audit::record('auth.login', 'accounts', 'Signed in', ['actor' => $e->user]);
            }
        });

        Event::listen(Failed::class, function (Failed $e) {
            if ($e->guard === 'web') {
                // Nobody has proved who they are, so the failed attempt belongs to no one: only the email tried is kept.
                Audit::record('auth.failed', 'accounts', 'Failed sign-in for '.($e->credentials['email'] ?? 'unknown'), [
                    'actor' => null,
                    'meta' => ['email' => $e->credentials['email'] ?? null],
                ]);
            }
        });

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->guard === 'web' && $e->user) {
                Audit::record('auth.logout', 'accounts', 'Signed out', ['actor' => $e->user]);
            }
        });
    }
}
