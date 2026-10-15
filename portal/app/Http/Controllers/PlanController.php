<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Services\PaidSignup;
use App\Services\PlanChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/** "Cambiar de plan" de un buzón (ver App\Services\PlanChange). */
class PlanController extends Controller
{
    public function __construct(private PlanChange $plans) {}

    public function edit(Request $request, int $mailbox): View
    {
        $mailbox = $this->mailbox($request, $mailbox);
        $subscription = $this->plans->subscription($mailbox);
        $fitsFree = app(\App\Services\MailboxBilling::class)->fitsFree($mailbox);

        return view('account.plan', [
            'mailbox' => $mailbox,
            'options' => $this->plans->options($mailbox),
            'subscription' => $subscription,
            'pendingFree' => $subscription?->onGracePeriod(),
            'fitsFree' => $fitsFree,
            // Si no puede pasar a gratis, cancelar es perderlo: la página enseña las fechas antes y después
            'loss' => $subscription && ! $fitsFree ? $this->plans->lossDates($subscription) : null,
        ]);
    }

    public function update(Request $request, int $mailbox): RedirectResponse
    {
        $mailbox = $this->mailbox($request, $mailbox);
        $data = $request->validate(['plan' => ['required', 'integer']]);
        $plan = Plan::findOrFail($data['plan']);

        try {
            $result = $this->plans->change($request->user(), $mailbox, $plan, $request->boolean('immediate_start'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()])->withInput();
        }

        return isset($result['redirect'])
            ? redirect()->away($result['redirect'])
            : redirect()->route('account')->with('status', $result['status']);
    }

    public function resume(Request $request, int $mailbox): RedirectResponse
    {
        try {
            $this->plans->resume($this->mailbox($request, $mailbox));
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return redirect()->route('account')->with('status', 'Hecho: sigues con tu plan actual, que se renovará con normalidad.');
    }

    public function cancel(Request $request, int $mailbox): RedirectResponse
    {
        $mailbox = $this->mailbox($request, $mailbox);
        try {
            $subscription = $this->plans->cancel($mailbox);
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }
        $end = $subscription->ends_at?->timezone('Europe/Madrid')->format('d/m/Y');

        return redirect()->route('account')->with('status', "Plan cancelado: {$mailbox->email} funciona hasta el {$end}. Te hemos enviado un email con las fechas; puedes deshacerlo hasta entonces en «Cambiar de plan».");
    }

    /** Vuelta de Stripe Checkout al pasar de gratis a pago. */
    public function paymentReturn(Request $request, PaidSignup $paid, int $checkout): View|RedirectResponse
    {
        $checkout = Checkout::where('user_id', $request->user()->id)->where('kind', 'change')->findOrFail($checkout);

        try {
            $ready = $paid->confirm($checkout);
        } catch (\Throwable $e) {
            report($e);
            $ready = false;
        }

        return $ready
            ? redirect()->route('account')->with('status', "Listo: tu plan ahora es {$checkout->plan->name}.")
            : view('signup.payment-pending', ['checkout' => $checkout]);
    }

    private function mailbox(Request $request, int $id): Mailbox
    {
        return Mailbox::where('user_id', $request->user()->id)->whereIn('status', ['active', 'suspended'])->where('tier', '!=', 'system')
            ->with('plan', 'user', 'usage')->findOrFail($id);
    }
}
