<?php

namespace Tests\Feature;

use App\Mail\LifecycleNotice;
use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\Plan;
use App\Services\StripeCatalog;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\Support\FakeStripe;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
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
        app(StripeCatalog::class)->syncAll();
    }

    protected function tearDown(): void
    {
        FakeStripe::uninstall();
        parent::tearDown();
    }

    /** Alta de pago hasta Stripe Checkout; el buzón queda pendiente. */
    private function pendingPaidMailbox(string $name = 'lucia'): Mailbox
    {
        $this->get("/alta?nombre={$name}");
        $this->post('/alta/cuenta', ['email' => "{$name}@gmail.com", 'password' => 'una-clave-larga-123', 'terms' => '1', 'cf-turnstile-response' => 'ok']);
        $this->post('/alta/plan', ['plan' => 'basico', 'immediate_start' => '1']);

        return Mailbox::where('email', "{$name}@unagrandeylibre.es")->firstOrFail();
    }

    private function event(string $type, array $object, ?string $id = null): TestResponse
    {
        return $this->postJson('/stripe/webhook', ['id' => $id ?? 'evt_'.Str::random(10), 'type' => $type, 'data' => ['object' => $object]]);
    }

    private function subscription(Mailbox $mailbox, string $status, ?Plan $plan = null, string $id = 'sub_1'): array
    {
        $plan ??= Plan::where('slug', 'basico')->first();

        return ['id' => $id, 'object' => 'subscription', 'customer' => $mailbox->user->stripe_id, 'status' => $status,
            'metadata' => ['type' => "mailbox:{$mailbox->id}", 'mailbox_id' => (string) $mailbox->id],
            'cancel_at_period_end' => false, 'current_period_end' => now()->addMonth()->timestamp,
            'items' => ['data' => [['id' => 'si_1', 'quantity' => 1, 'price' => ['id' => $plan->stripe_price_id, 'product' => $plan->stripe_product_id]]]]];
    }

    private function activePaidMailbox(): Mailbox
    {
        $mailbox = $this->pendingPaidMailbox();
        $checkout = Checkout::firstOrFail();
        $this->event('checkout.session.completed', ['id' => $checkout->stripe_session_id, 'payment_status' => 'paid',
            'metadata' => ['checkout_id' => (string) $checkout->id]])->assertOk();
        $this->event('customer.subscription.created', $this->subscription($mailbox, 'active'))->assertOk();

        return $mailbox->fresh();
    }

    public function test_checkout_completed_activates_the_mailbox_once(): void
    {
        $mailbox = $this->pendingPaidMailbox();
        $checkout = Checkout::firstOrFail();
        $session = ['id' => $checkout->stripe_session_id, 'payment_status' => 'paid', 'metadata' => ['checkout_id' => (string) $checkout->id]];

        $this->event('checkout.session.completed', $session, 'evt_1')->assertOk();
        $this->event('checkout.session.completed', $session, 'evt_1')->assertOk();   // Stripe lo repite

        $this->assertSame('active', $mailbox->fresh()->status);
        $this->assertSame('completed', $checkout->fresh()->status);
        $this->assertSame(1, DB::table('stripe_events')->count());
    }

    public function test_expired_checkout_frees_the_name(): void
    {
        $this->pendingPaidMailbox();
        $checkout = Checkout::firstOrFail();

        $this->event('checkout.session.expired', ['id' => $checkout->stripe_session_id, 'metadata' => ['checkout_id' => (string) $checkout->id]])->assertOk();

        $this->assertSame(0, Mailbox::count());
        $this->assertSame('expired', $checkout->fresh()->status);
    }

    public function test_changing_plan_in_stripe_updates_quota_and_tier(): void
    {
        $mailbox = $this->activePaidMailbox();
        $pro = Plan::where('slug', 'pro')->first();

        $this->event('customer.subscription.updated', $this->subscription($mailbox, 'active', $pro))->assertOk();

        $mailbox->refresh();
        $this->assertSame($pro->id, (int) $mailbox->plan_id);
        $this->assertSame($pro->quota_bytes, $mailbox->quota_bytes);
        $this->assertSame('pro', $mailbox->tier);
    }

    public function test_failed_renewal_starts_the_unpaid_path_and_paying_clears_it(): void
    {
        $mailbox = $this->activePaidMailbox();

        $this->event('customer.subscription.updated', $this->subscription($mailbox, 'past_due'))->assertOk();
        $this->assertNotNull(MailboxState::find($mailbox->id)->unpaid_since);
        Mail::assertQueued(LifecycleNotice::class, fn ($m) => $m->type === 'payment_failed' && $m->hasTo('lucia@gmail.com'));

        // Suspendido por el ciclo de vida, y luego paga
        $mailbox->update(['status' => 'suspended']);
        MailboxState::find($mailbox->id)->update(['suspended_at' => now()]);
        $this->event('customer.subscription.updated', $this->subscription($mailbox, 'active'))->assertOk();

        $this->assertSame('active', $mailbox->fresh()->status);
        $this->assertNull(MailboxState::find($mailbox->id)->unpaid_since);
        Mail::assertQueued(LifecycleNotice::class, fn ($m) => $m->type === 'reactivated');
    }

    public function test_cancelled_subscription_falls_back_to_free_when_possible(): void
    {
        $mailbox = $this->activePaidMailbox();

        $this->event('customer.subscription.deleted', $this->subscription($mailbox, 'canceled'))->assertOk();

        $mailbox->refresh();
        $this->assertSame('free', $mailbox->tier);
        $this->assertSame('active', $mailbox->status);
        Mail::assertQueued(LifecycleNotice::class, fn ($m) => $m->type === 'downgraded');
    }

    public function test_cancelled_subscription_without_room_for_free_goes_unpaid(): void
    {
        $mailbox = $this->activePaidMailbox();
        // Ya tiene su buzón gratis (D-014)
        Mailbox::create(['domain_id' => 1, 'user_id' => $mailbox->user_id, 'plan_id' => Plan::where('is_free', true)->value('id'),
            'local_part' => 'lucia.gratis', 'email' => 'lucia.gratis@unagrandeylibre.es', 'password' => 'x']);

        $this->event('customer.subscription.deleted', $this->subscription($mailbox, 'canceled'))->assertOk();

        $this->assertSame('basic', $mailbox->fresh()->tier);
        $this->assertNotNull(MailboxState::find($mailbox->id)->unpaid_since);
        Mail::assertQueued(LifecycleNotice::class, fn ($m) => $m->type === 'ended');
    }

    public function test_webhook_signature_is_checked_when_configured(): void
    {
        config(['cashier.webhook.secret' => 'whsec_prueba']);
        $this->postJson('/stripe/webhook', ['id' => 'evt_x', 'type' => 'checkout.session.completed', 'data' => ['object' => []]])
            ->assertForbidden();
    }

    public function test_account_shows_paid_plan_and_opens_the_billing_portal(): void
    {
        $mailbox = $this->activePaidMailbox();
        $this->actingAs($mailbox->user);

        $this->get('/cuenta')->assertOk()->assertSee('Gestionar pago y facturas');
        $this->post('/cuenta/facturacion')->assertRedirect();
        $this->assertStringStartsWith('https://billing.stripe.test/', $this->post('/cuenta/facturacion')->headers->get('Location'));

        $config = array_values($this->stripe->objects['billing_portal/configurations'])[0];
        $this->assertSame('at_period_end', $config['features']['subscription_cancel']['mode']);
        $this->assertFalse($config['features']['subscription_update']['enabled']);   // los cambios de plan, solo en Mi cuenta

        // Impago: aviso con botón para pagar
        $this->event('customer.subscription.updated', $this->subscription($mailbox, 'past_due'))->assertOk();
        $this->get('/cuenta')->assertSee('No hemos podido cobrar el plan')->assertSee('Actualizar la tarjeta y pagar');
    }
}
