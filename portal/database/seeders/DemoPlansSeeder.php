<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Planes de ejemplo para desarrollo local. Los planes reales se crean desde el admin. */
class DemoPlansSeeder extends Seeder
{
    public function run(): void
    {
        $gb = 1024 ** 3;

        Plan::updateOrCreate(['slug' => 'gratis'], [
            'name' => 'Gratis',
            'description' => 'Tu dirección para siempre, sin pagar nada.',
            'features' => ['1 GB de espacio', 'Webmail y apps de correo', 'Antispam'],
            'is_free' => true, 'price_cents' => 0,
            'quota_bytes' => 1 * $gb, 'max_aliases' => 0, 'send_limit_per_hour' => 20, 'tier' => 'free',
            'sort_order' => 10,
        ]);

        $basico = Plan::updateOrCreate(['slug' => 'basico'], [
            'name' => 'Básico',
            'description' => 'Para el día a día.',
            'features' => ['10 GB de espacio', '5 alias', 'Nombres cortos disponibles', 'Soporte por email'],
            'price_cents' => 199,
            'quota_bytes' => 10 * $gb, 'max_aliases' => 5, 'send_limit_per_hour' => 100, 'tier' => 'basic',
            'is_highlighted' => true, 'sort_order' => 20,
        ]);
        $basico->offers()->updateOrCreate(['label' => '-50 % lanzamiento'], [
            'type' => 'percent', 'value' => 50, 'duration' => 'repeating', 'duration_months' => 3,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'description' => 'Espacio de sobra y todo incluido.',
            'features' => ['50 GB de espacio', '25 alias', 'Nombres cortos disponibles', 'Soporte prioritario'],
            'price_cents' => 499,
            'quota_bytes' => 50 * $gb, 'max_aliases' => 25, 'send_limit_per_hour' => 300, 'tier' => 'pro',
            'sort_order' => 30,
        ]);
    }
}
