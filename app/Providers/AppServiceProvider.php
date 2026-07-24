<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Evita "Specified key too long" en indices/unique sobre columnas string
        // sin longitud explicita, bajo utf8mb4 (limite de 767 bytes en MySQL/MariaDB).
        Schema::defaultStringLength(191);
    }
}
