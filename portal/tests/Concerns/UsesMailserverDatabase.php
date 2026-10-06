<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** Crea en memoria la BD `mailserver` (domains, mailboxes, aliases) con un dominio de alta. */
trait UsesMailserverDatabase
{
    protected function setUpMailserverDatabase(): void
    {
        Artisan::call('migrate', ['--database' => 'mailserver', '--path' => 'database/migrations-mailserver-local']);

        DB::connection('mailserver')->table('domains')->insert([
            'name' => 'unagrandeylibre.es', 'active' => true, 'public_signup' => true, 'sort_order' => 0,
        ]);
    }

    protected function addMailbox(string $email): void
    {
        DB::connection('mailserver')->table('mailboxes')->insert([
            'domain_id' => 1, 'local_part' => strstr($email, '@', true), 'email' => $email, 'password' => 'x',
        ]);
    }

    protected function addAlias(string $source): void
    {
        DB::connection('mailserver')->table('aliases')->insert([
            'domain_id' => 1, 'source' => $source, 'destination' => 'test@unagrandeylibre.es',
        ]);
    }
}
