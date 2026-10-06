<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** SOLO LOCAL: el dominio de partida en database/mailserver.sqlite, como en el servidor. */
class LocalMailserverSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mailserver')->table('domains')->updateOrInsert(
            ['name' => 'unagrandeylibre.es'],
            ['active' => true, 'public_signup' => true, 'sort_order' => 0],
        );
    }
}
