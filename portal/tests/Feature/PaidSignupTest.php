<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeCatalog;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\Support\FakeStripe;
use Tests\TestCase;

class PaidSignupTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private FakeStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class, DemoPlansSeeder::class]);
        Mail::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true]), 'api.pwnedpasswords.com/*' => Http::response('')]);
        $this->stripe = FakeStripe::install();
        NamePriceTier::create(['min_length' => 1, 'max_length' => 4, 'price_cents' => 300]);
        app(StripeCatalog::class)->syncAll();
    }

    protected function tearDown(): void
    {
        FakeStripe::uninstall();
        parent::tearDown();
    }

    /** Alta hasta el paso de plan y "Ir al pago". Devuelve la URL a la que manda. */
    private function goToPayment(string $name = 'lucia', string $plan = 'basico'): string
    {
        $this->get("/alta?nombre={$name}");
        $this->post('/alta/cuenta', ['email' => "{$name}@gmail.com", 'password' => 'una-clave-larga-123', 'terms' => '1', 'cf-turnstile-response' => 'ok']);
        $this->get('/alta/plan')->assertOk()->assertSee('Ir al pago', false);

        return $this->post('/alta/plan', ['plan' => $plan, 'immediate_start' => '1'])->assertRedirect()->headers->get('Location');
    }

    private function stripeSession(): array
    {
        return array_values($this->stripe->objects['checkout/sessions'])[0];
    }

    public function test_paying_activates_the_mailbox(): void
    {
        $url = $this->goToPayment();
        $this->assertStringStartsWith('https://checkout.stripe.test/', $url);

        $mailbox = Mailbox::where('email', 'lucia@unagrandeylibre.es')->firstOrFail();
        $this->assertSame('pending', $mailbox->status);
        $this->assertFalse($mailbox->active);                      // ni recibe ni entra hasta pagar

        $session = $this->stripeSession();
        $plan = Plan::where('slug', 'basico')->first();
        $this->assertSame($plan->stripe_price_id, $session['line_items'][0]['price']);
        $this->assertSame("mailbox:{$mailbox->id}", $session['subscription_data']['metadata']['type']);
        $this->assertSame($plan->offers()->first()->stripe_coupon_id, $session['discounts'][0]['coupon']);   // oferta de lanzamiento
        $this->assertNotEmpty($session['subscription_data']['default_tax_rates']);
        $this->assertSame('es', $session['locale']);

        $checkout = Checkout::firstOrFail();
        $this->assertNotNull($checkout->immediate_start_consent_at);

        // Vuelve antes de que Stripe confirme: espera
        $this->get("/alta/pago/{$checkout->id}?session_id={$session['id']}")->assertOk()->assertSee('Confirmando el pago');

        // Pagado
        $this->stripe->objects['checkout/sessions'][$session['id']]['status'] = 'complete';
        $this->stripe->objects['checkout/sessions'][$session['id']]['payment_status'] = 'paid';
        $this->get("/alta/pago/{$checkout->id}?session_id={$session['id']}")->assertRedirect('/alta/listo');

        $mailbox->refresh();
        $this->assertSame('active', $mailbox->status);
        $this->assertTrue($mailbox->active);
        $this->assertTrue($mailbox->can_send);                     // de pago: envía desde el principio (D-006)
        $this->assertSame('completed', $checkout->fresh()->status);
        $this->get('/alta/listo')->assertOk()->assertSee('lucia@unagrandeylibre.es');
    }

    public function test_cancelling_frees_the_mailbox_and_keeps_the_name_for_another_plan(): void
    {
        $this->goToPayment();
        $checkout = Checkout::firstOrFail();

        $this->get("/alta/pago/{$checkout->id}/cancelado")->assertRedirect('/alta/plan')->assertSessionHas('status');

        $this->assertSame(0, Mailbox::count());
        $this->assertSame('expired', $checkout->fresh()->status);
        $this->assertNotEmpty($this->stripe->sent('POST', 'checkout/sessions/'.$checkout->stripe_session_id.'/expire'));
        $this->get('/alta/plan')->assertOk()->assertSee('lucia@unagrandeylibre.es');   // sigue reservado para él
    }

    public function test_short_names_add_the_surcharge_line(): void
    {
        $this->goToPayment('ana');

        $tier = NamePriceTier::forLength(3);
        $prices = array_column($this->stripeSession()['line_items'], 'price');
        $this->assertContains($tier->stripe_price_id, $prices);
    }

    public function test_someone_else_cannot_use_my_checkout(): void
    {
        $this->goToPayment();
        $checkout = Checkout::firstOrFail();

        $this->actingAs(User::factory()->create())->get("/alta/pago/{$checkout->id}/cancelado")->assertNotFound();
        $this->assertSame('pending', Mailbox::firstOrFail()->status);
    }
}
