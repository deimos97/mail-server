<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
    }

    public function test_landing_shows_visible_plans_with_their_offer(): void
    {
        Plan::whereIn('slug', ['pro', 'pro-anual'])->update(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('@unagrandeylibre.es')
            ->assertSee('Básico')
            ->assertSee('1,00 €')          // con la oferta
            ->assertSee('1,99 €')          // precio tachado
            ->assertSee('-50 % lanzamiento')
            ->assertDontSee('Espacio de sobra');   // Pro, desactivado
    }

    public function test_yearly_plans_behind_a_toggle(): void
    {
        $this->get('/')
            ->assertSee('Mensual')->assertSee('2 meses gratis')
            ->assertSee('19,90 €')->assertSee('Sale a 1,65 €/mes')           // Básico anual
            ->assertSee('x-show="yearly"', false)->assertSee('x-show="! yearly"', false);
    }

    public function test_landing_is_noindex_until_launch(): void
    {
        config(['app.indexable' => false]);
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        config(['app.indexable' => true]);
        $this->get('/')->assertDontSee('noindex', false);
    }

    public function test_placeholder_pages(): void
    {
        $this->get('/legal/privacidad')->assertOk()->assertSee('Política de privacidad');
        $this->get('/legal/no-existe')->assertNotFound();
    }
}
