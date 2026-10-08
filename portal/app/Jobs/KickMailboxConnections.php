<?php

namespace App\Jobs;

use App\Services\MailProvision;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Cierra las conexiones abiertas de un buzón: las apps reconectan solas, pero ya sin la contraseña revocada. */
class KickMailboxConnections implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public string $email) {}

    public function handle(MailProvision $provision): void
    {
        if ($provision->enabled()) {
            $provision->kick($this->email);
        }
    }
}
