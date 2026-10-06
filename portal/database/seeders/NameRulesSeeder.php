<?php

namespace Database\Seeders;

use App\Models\NamePriceTier;
use App\Models\NameRule;
use Illuminate\Database\Seeder;

/**
 * Reglas de nombre y tramos de sobrecoste de partida (D-005). Idempotente: si ya existen, no
 * los toca. Los importes son provisionales; se ajustan desde el admin.
 */
class NameRulesSeeder extends Seeder
{
    public function run(): void
    {
        NameRule::firstOrCreate(['domain_id' => null], [
            'min_length' => 1,
            'max_length' => 32,
            'allowed_symbols' => '._-',
            'forbid_edge_symbols' => true,
            'forbid_consecutive_symbols' => true,
        ]);

        if (NamePriceTier::query()->doesntExist()) {
            NamePriceTier::create(['min_length' => 1, 'max_length' => 2, 'price_cents' => 300]);
            NamePriceTier::create(['min_length' => 3, 'max_length' => 4, 'price_cents' => 100]);
        }
    }
}
