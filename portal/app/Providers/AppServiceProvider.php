<?php

namespace App\Providers;

use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\PlanOffer;
use App\Services\ServerAnalytics;
use App\Services\StripeCatalog;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cookie\Middleware\EncryptCookies;
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

        // Cookies que escribe el JS (consentimiento y PostHog): Laravel no debe intentar descifrarlas
        EncryptCookies::except(array_filter([ServerAnalytics::CONSENT_COOKIE, ServerAnalytics::posthogCookie()]));

        // Comprobar disponibilidad revela si una dirección existe: límite por IP contra barridos.
        // Ojo: si el apex pasa a ir por el proxy de Cloudflare, hay que confiar en sus IPs (TrustProxies).
        // Altas por IP: frena la creación masiva de cuentas (spam) sin molestar a una familia que comparte IP
        RateLimiter::for('signup', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        // Catálogo en Stripe: al guardar un plan, tramo u oferta en el admin se sincroniza. Si Stripe falla,
        // se guarda igual y se avisa (se puede repetir con `php artisan stripe:sync`).
        $sync = function (string $method) {
            return function ($model) use ($method) {
                $catalog = app(StripeCatalog::class);
                if (! $catalog->enabled()) {
                    return;
                }
                try {
                    $catalog->{$method}($model);
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('No se pudo sincronizar con Stripe')->body($e->getMessage())->send();
                }
            };
        };
        Plan::saved($sync('syncPlan'));
        NamePriceTier::saved($sync('syncTier'));
        PlanOffer::saved($sync('syncOffer'));

        RateLimiter::for('availability', fn (Request $request) => [
            Limit::perMinute(30)->by($request->ip()),
            Limit::perDay(500)->by($request->ip()),
        ]);
    }
}
