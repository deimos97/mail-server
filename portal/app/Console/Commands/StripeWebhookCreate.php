<?php

namespace App\Console\Commands;

use App\Services\StripeCatalog;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

/**
 * Crea en la cuenta de Stripe de STRIPE_SECRET el webhook de la web, con los eventos de config/cashier.php.
 * Escribe en la salida estándar **solo** el secreto de firma (whsec_…), para que `server/bin/stripe-go-live`
 * lo guarde en el .env. Si ya existe un endpoint con esa URL, lo borra y lo vuelve a crear: Stripe solo
 * enseña el secreto al crearlo.
 */
class StripeWebhookCreate extends Command
{
    protected $signature = 'stripe:webhook-create';

    protected $description = 'Crea el webhook de la web en Stripe y escribe su secreto (para stripe-go-live)';

    public function handle(StripeCatalog $catalog): int
    {
        if (! $catalog->enabled()) {
            $this->error('Falta STRIPE_SECRET.');

            return self::FAILURE;
        }

        $stripe = Cashier::stripe();
        $url = route('cashier.webhook');
        foreach ($stripe->webhookEndpoints->all(['limit' => 100])->autoPagingIterator() as $endpoint) {
            if ($endpoint->url === $url) {
                $stripe->webhookEndpoints->delete($endpoint->id);
            }
        }

        $endpoint = $stripe->webhookEndpoints->create([
            'url' => $url,
            'enabled_events' => config('cashier.webhook.events'),
            'api_version' => Cashier::STRIPE_VERSION,
            'description' => 'Web unagrandeylibre.es (Cashier)',
        ]);

        if (! str_starts_with((string) ($endpoint->secret ?? ''), 'whsec_')) {
            $this->error('Stripe no ha devuelto el secreto del webhook.');

            return self::FAILURE;
        }
        $this->line($endpoint->secret);

        return self::SUCCESS;
    }
}
