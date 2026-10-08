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

        return view('account.plan', [
            'mailbox' => $mailbox,
            'options' => $this->plans->options($mailbox),
            'subscription' => $subscription,
            'pendingFree' => $subscription?->onGracePeriod(),
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
        return Mailbox::where('user_id', $request->user()->id)->whereIn('status', ['active', 'suspended'])
            ->with('plan', 'user', 'usage')->findOrFail($id);
    }
}
