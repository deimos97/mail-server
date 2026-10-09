<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class ExperimentsTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class, DemoPlansSeeder::class]);
    }

    private function heroExperiment(int $controlWeight = 0, int $bWeight = 100, bool $active = true): Experiment
    {
        return Experiment::create(['key' => 'hero', 'name' => 'Título del hero', 'is_active' => $active, 'variants' => [
            ['key' => 'control', 'weight' => $controlWeight, 'overrides' => []],
            ['key' => 'b', 'weight' => $bWeight, 'overrides' => ['hero.title' => 'Tu correo, hecho en España']],
        ]]);
    }

    public function test_without_experiments_the_landing_is_unchanged(): void
    {
        $this->heroExperiment(active: false);
        $this->get('/')->assertOk()->assertSee(config('landing.hero.title'))->assertDontSee('Tu correo, hecho en España');
    }

    public function test_variant_texts_are_rendered_server_side_and_sent_to_posthog(): void
    {
        config(['services.posthog.key' => 'phc_prueba']);
        $this->heroExperiment();

        $this->get('/')->assertOk()
            ->assertSee('Tu correo, hecho en España')
            ->assertSee('experiments: {"hero":"b"}', false);
    }

    public function test_assignment_is_stable_and_can_be_forced_for_qa(): void
    {
        $experiment = $this->heroExperiment();
        $this->get('/')->assertSee('Tu correo, hecho en España');

        // Cambian los pesos: quien ya la vio se queda con su variante
        $experiment->update(['variants' => [['key' => 'control', 'weight' => 100], ['key' => 'b', 'weight' => 0, 'overrides' => ['hero.title' => 'Tu correo, hecho en España']]]]);
        $this->get('/')->assertSee('Tu correo, hecho en España');

        $this->get('/?variante=hero:control')->assertDontSee('Tu correo, hecho en España');
        $this->get('/?variante=hero:inexistente')->assertDontSee('Tu correo, hecho en España');
    }

    public function test_split_is_roughly_by_weight(): void
    {
        $this->heroExperiment(50, 50);
        $service = app(\App\Services\Experiments::class);
        $b = 0;
        foreach (range(1, 400) as $i) {
            session()->flush();
            session()->put('experiments.visitor', "visitante-$i");
            $b += $service->variant('hero') === 'b' ? 1 : 0;
        }
        $this->assertGreaterThan(150, $b);
        $this->assertLessThan(250, $b);
    }

    public function test_signup_keeps_the_variant_for_server_events(): void
    {
        Mail::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true]), 'api.pwnedpasswords.com/*' => Http::response(''), 'eu.i.posthog.com/*' => Http::response([])]);
        config(['services.posthog.key' => 'phc_prueba']);
        $this->heroExperiment();

        $this->get('/');
        $this->get('/alta?nombre=lucia');
        $this->post('/alta/cuenta', ['email' => 'lucia@gmail.com', 'password' => 'una-clave-larga-123', 'terms' => '1', 'cf-turnstile-response' => 'ok']);
        $this->assertSame(['hero' => 'b'], User::firstOrFail()->attribution['experiments']);

        $this->post('/alta/plan', ['plan' => 'gratis']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'posthog') && ($request['properties']['$feature/hero'] ?? null) === 'b');
    }
}
