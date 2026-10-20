<?php

namespace App\Services;

/**
 * Píxel de X en el navegador (D-030). El servidor deja en la sesión los eventos de conversión (alta, compra) y la
 * siguiente página los pasa a `window.UGL.x`; el JS (resources/js/analytics.js) solo carga el píxel y los envía si
 * la persona aceptó las cookies. Complementa a AdConversions (desde el servidor, cuando haya claves de la Ads API).
 */
class AdPixel
{
    private const SESSION_KEY = 'ad_pixel_events';

    public function enabled(): bool
    {
        return (bool) config('ads.x.browser_pixel') && filled(config('ads.x.pixel_id'));
    }

    public function signup(): void
    {
        $this->queue(config('ads.x.signup_event'));
    }

    public function purchase(int $valueCents, ?string $conversionId = null): void
    {
        $this->queue(config('ads.x.purchase_event'), array_filter([
            'value' => round($valueCents / 100, 2),
            'currency' => 'EUR',
            'conversion_id' => $conversionId,
        ], fn ($v) => $v !== null));
    }

    /** Configuración para la página (y vacía la cola: cada evento se envía una sola vez). */
    public function forPage(): ?array
    {
        if (! $this->enabled()) {
            return null;
        }
        $events = request()->hasSession() ? (array) request()->session()->pull(self::SESSION_KEY, []) : [];

        return ['pixel' => config('ads.x.pixel_id'), 'events' => $events];
    }

    private function queue(?string $eventId, array $params = []): void
    {
        if ($this->enabled() && filled($eventId) && request()->hasSession()) {
            request()->session()->push(self::SESSION_KEY, [$eventId, (object) $params]);
        }
    }
}
