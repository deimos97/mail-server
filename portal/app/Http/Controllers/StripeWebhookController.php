<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Services\MailboxBilling;
use App\Services\PaidSignup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhooks de Stripe (POST /stripe/webhook, firmados con STRIPE_WEBHOOK_SECRET). Cashier mantiene sus tablas
 * de suscripciones; aquí se añade lo nuestro: activar el buzón al pagar, liberarlo si no se paga, y
 * plan/impagos/fin de la suscripción (MailboxBilling). Cada evento se procesa una sola vez (stripe_events).
 */
class StripeWebhookController extends WebhookController
{
    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $id = $payload['id'] ?? null;

        if ($id && DB::table('stripe_events')->where('id', $id)->exists()) {
            return $this->successMethod();
        }

        $response = parent::handleWebhook($request);

        if ($id && $response->getStatusCode() < 300) {
            DB::table('stripe_events')->insertOrIgnore(['id' => $id, 'type' => $payload['type'] ?? '', 'created_at' => now()]);
        }

        return $response;
    }

    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];
        if (in_array($session['payment_status'] ?? null, ['paid', 'no_payment_required'], true) && ($checkout = $this->checkout($session))) {
            app(PaidSignup::class)->activate($checkout);
        }

        return $this->successMethod();
    }

    protected function handleCheckoutSessionAsyncPaymentSucceeded(array $payload): Response
    {
        if ($checkout = $this->checkout($payload['data']['object'])) {
            app(PaidSignup::class)->activate($checkout);
        }

        return $this->successMethod();
    }

    protected function handleCheckoutSessionExpired(array $payload): Response
    {
        if ($checkout = $this->checkout($payload['data']['object'])) {
            app(PaidSignup::class)->release($checkout);
        }

        return $this->successMethod();
    }

    protected function handleCheckoutSessionAsyncPaymentFailed(array $payload): Response
    {
        return $this->handleCheckoutSessionExpired($payload);
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        app(MailboxBilling::class)->sync($payload['data']['object']);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        app(MailboxBilling::class)->sync($payload['data']['object']);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        // Si el buzón nunca llegó a activarse (pago inicial fallido), no hay nada que bajar a gratis
        $mailbox = app(MailboxBilling::class)->mailboxFromMetadata($payload['data']['object']);
        if ($mailbox && $mailbox->status !== 'pending') {
            app(MailboxBilling::class)->ended($payload['data']['object']);
        }

        return $response;
    }

    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $invoice = $payload['data']['object'];
        // Solo las renovaciones: el primer pago lo gestiona el Checkout
        if (($invoice['billing_reason'] ?? null) !== 'subscription_create') {
            $metadata = $invoice['parent']['subscription_details']['metadata'] ?? $invoice['subscription_details']['metadata'] ?? [];
            app(MailboxBilling::class)->paymentFailed(app(MailboxBilling::class)->mailboxFromMetadata(['metadata' => $metadata]));
        }

        return $this->successMethod();
    }

    private function checkout(array $session): ?Checkout
    {
        $id = $session['metadata']['checkout_id'] ?? null;

        return $id ? Checkout::find((int) $id) : Checkout::where('stripe_session_id', $session['id'] ?? '')->first();
    }
}
