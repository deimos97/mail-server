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
            'features' => ['500 MB de espacio', 'Webmail y apps de correo', '20 envíos al día', 'Antispam'],
            'is_free' => true, 'price_cents' => 0,
            'quota_bytes' => 500 * 1024 ** 2, 'max_aliases' => 0, 'send_limit_per_hour' => 20, 'tier' => 'free',
            'sort_order' => 10,
        ]);

        $basico = Plan::updateOrCreate(['slug' => 'basico'], [
            'name' => 'Básico',
            'description' => 'Para el día a día.',
            'features' => ['10 GB de espacio', '3 alias', 'Nombres cortos disponibles', 'Soporte por email'],
            'price_cents' => 199,
            'quota_bytes' => 10 * $gb, 'max_aliases' => 3, 'send_limit_per_hour' => 100, 'tier' => 'basic',
            'is_highlighted' => true, 'sort_order' => 20,
        ]);
        $basico->offers()->updateOrCreate(['label' => '-50 % lanzamiento'], [
            'type' => 'percent', 'value' => 50, 'duration' => 'repeating', 'duration_months' => 3,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'description' => 'Espacio de sobra y todo incluido.',
            'features' => ['50 GB de espacio', '10 alias', 'Nombres cortos disponibles', 'Soporte prioritario'],
            'price_cents' => 499,
            'quota_bytes' => 50 * $gb, 'max_aliases' => 10, 'send_limit_per_hour' => 300, 'tier' => 'pro',
            'sort_order' => 30,
        ]);

        // Pago anual (D-029): mismo tier, dos meses gratis. En la web, un selector Mensual / Anual.
        Plan::updateOrCreate(['slug' => 'basico-anual'], [
            'name' => 'Básico anual',
            'description' => 'Para el día a día, con dos meses gratis.',
            'features' => ['10 GB de espacio', '3 alias', 'Nombres cortos disponibles', 'Soporte por email'],
            'price_cents' => 1990, 'interval' => 'year',
            'quota_bytes' => 10 * $gb, 'max_aliases' => 3, 'send_limit_per_hour' => 100, 'tier' => 'basic',
            'is_highlighted' => true, 'sort_order' => 21,
        ]);
        Plan::updateOrCreate(['slug' => 'pro-anual'], [
            'name' => 'Pro anual',
            'description' => 'Espacio de sobra, con dos meses gratis.',
            'features' => ['50 GB de espacio', '10 alias', 'Nombres cortos disponibles', 'Soporte prioritario'],
            'price_cents' => 4990, 'interval' => 'year',
            'quota_bytes' => 50 * $gb, 'max_aliases' => 10, 'send_limit_per_hour' => 300, 'tier' => 'pro',
            'sort_order' => 31,
        ]);
    }
}
