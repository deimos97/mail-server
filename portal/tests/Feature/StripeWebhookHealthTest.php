<?php

namespace Tests\Feature;

use Tests\Support\FakeStripe;
use Tests\TestCase;

class StripeWebhookHealthTest extends TestCase
{
    private FakeStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = FakeStripe::install();
    }

    protected function tearDown(): void
    {
        FakeStripe::uninstall();
        parent::tearDown();
    }

    private function endpoint(string $status = 'enabled'): void
    {
        $this->stripe->objects['webhook_endpoints']['we_1'] = ['id' => 'we_1', 'object' => 'webhook_endpoint', 'url' => route('cashier.webhook'), 'status' => $status];
    }

    public function test_healthy_when_endpoint_is_enabled_and_nothing_is_pending(): void
    {
        $this->endpoint();
        $this->artisan('stripe:webhook-health')->expectsOutput(null)->assertExitCode(0);
    }

    public function test_reports_a_missing_or_disabled_endpoint(): void
    {
        $this->artisan('stripe:webhook-health')->expectsOutputToContain('no existe el webhook')->assertExitCode(1);

        $this->endpoint('disabled');
        $this->artisan('stripe:webhook-health')->expectsOutputToContain('desactivado')->assertExitCode(1);
    }

    public function test_reports_undelivered_events(): void
    {
        $this->endpoint();
        $this->stripe->objects['events']['evt_1'] = ['id' => 'evt_1', 'object' => 'event', 'type' => 'invoice.payment_failed', 'pending_webhooks' => 1, 'created' => now()->timestamp];

        $this->artisan('stripe:webhook-health')->expectsOutputToContain('1 eventos sin entregar')->assertExitCode(1);
    }

    public function test_silent_without_stripe(): void
    {
        config(['cashier.secret' => null]);
        $this->artisan('stripe:webhook-health')->assertExitCode(0);
    }
}
