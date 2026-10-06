<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de partida. En producción solo lo que es seguro (idempotente y sin datos de ejemplo):
     *   php artisan db:seed --force
     */
    public function run(): void
    {
        $this->call([ReservedNamesSeeder::class, NameRulesSeeder::class]);

        if (! app()->isProduction()) {
            $this->call([LocalMailserverSeeder::class, DemoPlansSeeder::class]);
        }
    }
}
