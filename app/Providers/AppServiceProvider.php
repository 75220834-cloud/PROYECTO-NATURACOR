<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // BUG #3 FIX: Gate para anular ventas — solo admin
        Gate::define('delete-ventas', function ($user) {
            return $user->hasRole('admin');
        });
    }
}
