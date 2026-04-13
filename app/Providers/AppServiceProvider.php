<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;


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
        // 👉 Locale para fechas (Carbon)
        Carbon::setLocale('es');

        Gate::define('create-invitations', function ($user) {
            return $user->role === 'admin';
        });
    }

}
