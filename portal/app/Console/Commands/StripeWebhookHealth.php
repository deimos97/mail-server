<?php

namespace App\Console\Commands;

use App\Services\StripeCatalog;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Throwable;

/**
 * Salud de los webhooks de Stripe, para mail-monitor (que avisa por Telegram). Sin salida y código 0 si todo
 * va bien; si no, una línea con el problema y código 1.
 * - El endpoint `/stripe/webhook` existe en Stripe y está activo (Stripe lo desactiva si falla muchos días).
 * - No hay eventos de las últimas 24 h que Stripe no haya conseguido entregar (ni los reintentos).
 */
class StripeWebhookHealth extends Command
{
    protected $signature = 'stripe:webhook-health {--hours=24 : Ventana de eventos a revisar}';

    protected $description = 'Comprueba que el webhook de Stripe está activo y que no hay eventos sin entregar (para mail-monitor)';

    public function handle(StripeCatalog $catalog): int
    {
        if (! $catalog->enabled()) {
            return self::SUCCESS;   // sin Stripe configurado no hay nada que vigilar
        }

        try {
            $stripe = Cashier::stripe();
            $url = route('cashier.webhook');

            $endpoint = null;
            foreach ($stripe->webhookEndpoints->all(['limit' => 100])->autoPagingIterator() as $e) {
                if ($e->url === $url) {
                    $endpoint = $e;
                    break;
                }
            }
            if (! $endpoint) {
                return $this->problem("no existe el webhook {$url} en Stripe");
            }
            if ($endpoint->status !== 'enabled') {
                return $this->problem("el webhook está desactivado en Stripe ({$endpoint->status})");
            }

            $failed = 0;
            $types = [];
            $events = $stripe->events->all([
                'delivery_success' => false,
                'created' => ['gte' => now()->subHours((int) $this->option('hours'))->timestamp],
                'limit' => 100,
            ]);
            foreach ($events->autoPagingIterator() as $event) {
                if (($event->pending_webhooks ?? 0) > 0) {
                    $failed++;
                    $types[$event->type] = true;
                }
            }
            if ($failed > 0) {
                return $this->problem("{$failed} eventos sin entregar al webhook en ".$this->option('hours').' h ('.implode(', ', array_keys($types)).')');
            }
        } catch (Throwable $e) {
            return $this->problem('no se pudo consultar Stripe: '.mb_substr($e->getMessage(), 0, 150));
        }

        return self::SUCCESS;
    }

    private function problem(string $text): int
    {
        $this->line($text);

        return self::FAILURE;
    }
}
