<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Comprobar disponibilidad revela si una dirección existe: límite por IP contra barridos.
        // Ojo: si el apex pasa a ir por el proxy de Cloudflare, hay que confiar en sus IPs (TrustProxies).
        // Altas por IP: frena la creación masiva de cuentas (spam) sin molestar a una familia que comparte IP
        RateLimiter::for('signup', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        RateLimiter::for('availability', fn (Request $request) => [
            Limit::perMinute(30)->by($request->ip()),
            Limit::perDay(500)->by($request->ip()),
        ]);
    }
}
