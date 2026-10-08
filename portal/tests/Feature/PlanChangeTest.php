<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeCatalog;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\Support\FakeStripe;
use Tests\TestCase;

class PlanChangeTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private FakeStripe $stripe;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        $this->stripe = FakeStripe::install();
        app(StripeCatalog::class)->syncAll();
        $this->user = User::factory()->create(['email' => 'ana@gmail.com']);
    }

    protected function tearDown(): void
    {
        FakeStripe::uninstall();
        parent::tearDown();
    }

    private function plan(string $slug): Plan
    {
        return Plan::where('slug', $slug)->first();
    }

    private function mailbox(string $plan, string $local = 'anabel'): Mailbox
    {
        $plan = $this->plan($plan);

        return Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => $plan->id, 'tier' => $plan->tier,
            'quota_bytes' => $plan->quota_bytes, 'local_part' => $local, 'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'can_send' => true]);
    }

    /** Buzón de pago con su suscripción en Stripe y en Cashier. */
    private function paidMailbox(string $plan = 'basico'): Mailbox
    {
        $mailbox = $this->mailbox($plan);
        $this->user->createOrGetStripeCustomer();
        $price = $this->plan($plan)->stripe_price_id;
        $sub = $this->stripe->subscription('sub_1', $this->user->stripe_id, [$price], ['type' => "mailbox:{$mailbox->id}", 'mailbox_id' => $mailbox->id]);
        $subscription = $this->user->subscriptions()->create(['type' => "mailbox:{$mailbox->id}", 'stripe_id' => 'sub_1',
            'stripe_status' => 'active', 'stripe_price' => $price, 'quantity' => 1]);
        $item = $sub['items']['data'][0];
        $subscription->items()->create(['stripe_id' => $item['id'], 'stripe_product' => $item['price']['product'], 'stripe_price' => $price, 'quantity' => 1]);

        return $mailbox;
    }

    public function test_every_mailbox_has_a_change_plan_button(): void
    {
        $free = $this->mailbox('gratis', 'anagratis');
        $this->actingAs($this->user)->get('/cuenta')->assertSee('Cambiar de plan')->assertDontSee('Gestionar pago y facturas');
        $this->get("/cuenta/plan/{$free->id}")->assertOk()->assertSee('Tu plan actual')->assertSee('Básico');
    }

    public function test_free_to_paid_goes_through_checkout(): void
    {
        $mailbox = $this->mailbox('gratis');
        $this->actingAs($this->user);
        $basico = $this->plan('basico');

        $this->post("/cuenta/plan/{$mailbox->id}", ['plan' => $basico->id])->assertSessionHasErrors('plan');   // falta la casilla
        $url = $this->post("/cuenta/plan/{$mailbox->id}", ['plan' => $basico->id, 'immediate_start' => '1'])->headers->get('Location');
        $this->assertStringStartsWith('https://checkout.stripe.test/', $url);

        $checkout = Checkout::firstOrFail();
        $this->assertSame('change', $checkout->kind);
        $this->assertSame('gratis', $mailbox->fresh()->plan->slug);              // aún no

        $session = $checkout->stripe_session_id;
        $this->stripe->objects['checkout/sessions'][$session]['status'] = 'complete';
        $this->stripe->objects['checkout/sessions'][$session]['payment_status'] = 'paid';
        $this->get("/cuenta/plan/pago/{$checkout->id}?session_id={$session}")->assertRedirect('/cuenta');

        $mailbox->refresh();
        $this->assertSame('basico', $mailbox->plan->slug);
        $this->assertSame('basic', $mailbox->tier);
        $this->assertSame($basico->quota_bytes, $mailbox->quota_bytes);
        $this->assertSame('active', $mailbox->status);
    }

    public function test_paid_upgrade_swaps_the_price_and_invoices_now(): void
    {
        $mailbox = $this->paidMailbox('basico');
        $pro = $this->plan('pro');

        $this->actingAs($this->user)->get('/cuenta')->assertSee('Gestionar pago y facturas');
        $this->post("/cuenta/plan/{$mailbox->id}", ['plan' => $pro->id])->assertRedirect('/cuenta')->assertSessionHas('status');

        $this->assertSame('pro', $mailbox->fresh()->tier);
        $update = collect($this->stripe->sent('POST', 'subscriptions/sub_1'))->last();
        $this->assertSame('always_invoice', $update['proration_behavior']);
        $prices = array_map(fn ($item) => $item['price']['id'], $this->stripe->objects['subscriptions']['sub_1']['items']['data']);
        $this->assertSame([$pro->stripe_price_id], $prices);
    }

    public function test_paid_downgrade_prorates_as_credit(): void
    {
        $mailbox = $this->paidMailbox('pro');

        $this->actingAs($this->user)->post("/cuenta/plan/{$mailbox->id}", ['plan' => $this->plan('basico')->id])->assertRedirect('/cuenta');

        $this->assertSame('basic', $mailbox->fresh()->tier);
        $this->assertSame('create_prorations', collect($this->stripe->sent('POST', 'subscriptions/sub_1'))->last()['proration_behavior']);
    }

    public function test_paid_to_free_cancels_at_period_end_and_can_be_undone(): void
    {
        $mailbox = $this->paidMailbox('basico');
        $this->actingAs($this->user);

        $this->post("/cuenta/plan/{$mailbox->id}", ['plan' => $this->plan('gratis')->id])->assertRedirect('/cuenta');
        $this->assertTrue($this->stripe->objects['subscriptions']['sub_1']['cancel_at_period_end']);
        $this->assertSame('basic', $mailbox->fresh()->tier);                      // hasta el final del periodo
        $this->get("/cuenta/plan/{$mailbox->id}")->assertSee('Deshacer y seguir con mi plan');
        $this->get('/cuenta')->assertSee('Cancelado: termina el');

        $this->post("/cuenta/plan/{$mailbox->id}/deshacer")->assertRedirect('/cuenta');
        $this->assertFalse($this->stripe->objects['subscriptions']['sub_1']['cancel_at_period_end']);
    }

    public function test_free_is_not_offered_when_not_allowed(): void
    {
        $this->mailbox('gratis', 'anagratis');                                     // ya tiene su gratis
        $mailbox = $this->paidMailbox('basico');

        $this->actingAs($this->user)->get("/cuenta/plan/{$mailbox->id}")->assertSee('Ya tienes un buzón gratis');
        $this->post("/cuenta/plan/{$mailbox->id}", ['plan' => $this->plan('gratis')->id])
            ->assertSessionHasErrors(['plan' => 'Ya tienes un buzón gratis: cada cuenta incluye uno.']);
    }

    public function test_cannot_change_someone_elses_plan(): void
    {
        $mailbox = $this->mailbox('gratis');
        $this->actingAs(User::factory()->create())->get("/cuenta/plan/{$mailbox->id}")->assertNotFound();
    }
}
