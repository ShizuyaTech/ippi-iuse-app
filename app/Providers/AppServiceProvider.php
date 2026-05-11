<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        // Pastikan role + permissions sudah di-load saat render sidebar
        \Illuminate\Support\Facades\View::composer('layouts.app', function () {
            if (auth()->check()) {
                auth()->user()->loadRoleWithPermissions();
            }
        });
    }
}
