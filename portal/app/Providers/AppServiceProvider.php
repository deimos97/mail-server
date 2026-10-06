<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
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
        // La BD guarda UTC; el admin muestra y pide las fechas (ofertas, planes programados) en hora de Madrid.
        FilamentTimezone::set('Europe/Madrid');
    }
}
