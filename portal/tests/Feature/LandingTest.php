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
        $this->resetLandingBaseline();
    }

    protected function tearDown(): void
    {
        $this->resetLandingBaseline();
        parent::tearDown();
    }

    /** La portada guarda su config en la primera visita y la restaura en cada una: sin esto, los cambios de config de un test no cuentan */
    private function resetLandingBaseline(): void
    {
        (new \ReflectionProperty(\App\Services\Experiments::class, 'baseline'))->setValue(null, null);
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

    public function test_without_free_plan_the_yearly_only_mini_is_always_shown(): void
    {
        Plan::where('slug', 'gratis')->update(['is_active' => false]);   // D-032

        $html = $this->get('/')->assertOk()
            ->assertDontSee('Empezar gratis')
            ->assertSee('Elegir Mini')->assertSee('4,99 €')->assertSee('Sale a 0,41 €/mes')
            ->getContent();
        // Solo los planes con gemelo mensual/anual van detrás del selector; el Mini, no
        $this->assertSame(2, substr_count($html, 'x-show="yearly"'));
        $this->assertSame(2, substr_count($html, 'x-show="! yearly"'));
    }

    public function test_preview_steps_and_switching_sections(): void
    {
        $this->get('/')
            ->assertSee('Así te verán')->assertSee('Listo en un minuto')->assertSee('tunombre@unagrandeylibre.es', false)
            ->assertSee('Pasarte es fácil')->assertSee('Desde Gmail')->assertSee('Desde Outlook o Hotmail');
    }

    public function test_the_counter_shows_only_from_the_minimum_and_without_internal_mailboxes(): void
    {
        config(['landing.counter.min' => 2]);
        $make = fn (string $local, string $tier) => \App\Models\Mailbox::create(['domain_id' => 1, 'tier' => $tier, 'quota_bytes' => 1,
            'local_part' => $local, 'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'status' => 'active']);
        $make('anabel', 'free');
        $make('javier', 'system');

        $this->get('/')->assertDontSee('Ya somos');

        $make('pepito', 'basic');
        \Illuminate\Support\Facades\Cache::forget('landing.mailbox_count');
        $this->get('/')->assertSee('Ya somos 2 con dirección @unagrandeylibre.es');
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
