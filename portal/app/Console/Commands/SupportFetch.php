<?php

namespace App\Console\Commands;

use App\Services\SupportInbox;
use Illuminate\Console\Command;

/** Lee soporte@ y engancha las respuestas de los clientes a sus tickets. Corre cada minuto. */
class SupportFetch extends Command
{
    protected $signature = 'support:fetch';

    protected $description = 'Añade a los tickets las respuestas de los clientes que llegan por email a soporte@';

    public function handle(SupportInbox $inbox): int
    {
        if (! $inbox->configured()) {
            $this->line('Sin SUPPORT_IMAP_USERNAME/PASSWORD: no se lee ningún buzón.');

            return self::SUCCESS;
        }

        $this->info($inbox->fetch().' respuestas añadidas.');

        return self::SUCCESS;
    }
}
