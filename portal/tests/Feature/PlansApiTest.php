<?php

namespace Tests\Feature;

use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlansApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_visible_plans_with_prices(): void
    {
        $this->seed(DemoPlansSeeder::class);

        $this->getJson('/api/plans')->assertOk()
            ->assertJsonPath('iva_incluido', true)
            ->assertJsonPath('planes.0.id', 'gratis')
            ->assertJsonPath('planes.1.precio_eur', 1.99)
            ->assertJsonPath('planes.1.precio_ahora_eur', 1)    // oferta -50 %
            ->assertJsonPath('planes.1.oferta', '-50 % lanzamiento');
    }
}
