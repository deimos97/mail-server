<?php

namespace App\Console\Commands;

use App\Services\StripeCatalog;
use Illuminate\Console\Command;

class StripeSync extends Command
{
    protected $signature = 'stripe:sync';

    protected $description = 'Crea o actualiza en Stripe los planes de pago, los tramos de nombre corto, las ofertas y el IVA';

    public function handle(StripeCatalog $catalog): int
    {
        if (! $catalog->enabled()) {
            $this->error('Falta STRIPE_SECRET.');

            return self::FAILURE;
        }
        $catalog->syncAll();
        $this->info('Catálogo sincronizado con Stripe.');

        return self::SUCCESS;
    }
}
