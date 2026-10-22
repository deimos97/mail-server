<?php

namespace App\Services;

use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\NamePriceTier;
use App\Models\PlanPrice;
use App\Models\QuotaUsage;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Datos de negocio para el resumen diario de Telegram. Lo llama `mail-daily-report` (root, en el servidor)
 * con `php artisan report:daily-stats --since=…`, y le añade lo del sistema (visitas, correo, disco, CPU…).
 *
 * El dinero es el precio de lista al mes (anuales entre 12, con el suplemento de nombre corto), IVA incluido:
 * los descuentos de las ofertas solo están en Stripe y no se restan.
 */
class DailyStats
{
    public function collect(CarbonInterface $since, CarbonInterface $until): array
    {
        $newMailboxes = Mailbox::with('plan')->whereNotNull('user_id')->whereIn('status', ['active', 'suspended'])
            ->whereBetween('created_at', [$since, $until])->get();
        $paidNew = $newMailboxes->filter(fn (Mailbox $m) => $m->plan && ! $m->plan->is_free);
        $active = $this->activeSubscriptions();

        return [
            'since' => $since->toIso8601String(),
            'until' => $until->toIso8601String(),
            'signups' => [
                'users' => User::whereBetween('created_at', [$since, $until])->count(),
                'mailboxes' => $newMailboxes->count(),
                'free' => $newMailboxes->count() - $paidNew->count(),
                'paid' => $paidNew->count(),
                'paid_monthly_cents' => $paidNew->sum(fn (Mailbox $m) => $active->get('mailbox:'.$m->id, 0)),
                'sources' => $this->signupSources($since, $until),
            ],
            'revenue' => [
                'paid_mailboxes' => $active->count(),
                'monthly_cents' => $active->sum(),
            ],
            'churn' => [
                'deleted_mailboxes' => Mailbox::whereNotNull('user_id')->whereBetween('deleted_at', [$since, $until])->count(),
                'cancelled_plans' => DB::table('subscriptions')->whereNotNull('ends_at')->whereBetween('updated_at', [$since, $until])->count(),
                'new_unpaid' => MailboxState::whereBetween('unpaid_since', [$since, $until])->count(),
                'unpaid_now' => MailboxState::whereNotNull('unpaid_since')->count(),
            ],
            'quota' => $this->quota(),
            'support' => [
                'new_tickets' => SupportTicket::whereBetween('created_at', [$since, $until])->count(),
                'customer_replies' => SupportMessage::where('author', 'customer')->where('source', 'email')->whereBetween('created_at', [$since, $until])->count(),
                'open' => SupportTicket::where('status', 'open')->count(),
                'waiting_over_24h' => SupportTicket::where('status', 'open')->with('latestMessage')->get()
                    ->filter(fn (SupportTicket $t) => $t->latestMessage?->fromCustomer() && $t->latestMessage->created_at->lt(now()->subDay()))->count(),
            ],
        ];
    }

    /** @return Collection<string, int> suscripciones activas: "mailbox:ID" → céntimos al mes */
    public function activeSubscriptions(): Collection
    {
        $planPrices = PlanPrice::all()->keyBy('stripe_price_id');
        $tiers = NamePriceTier::all();
        $tierMonthly = $tiers->filter(fn ($t) => $t->stripe_price_id)->mapWithKeys(fn ($t) => [$t->stripe_price_id => $t->price_cents])
            ->union($tiers->filter(fn ($t) => $t->stripe_price_year_id)->mapWithKeys(fn ($t) => [$t->stripe_price_year_id => $t->price_cents]));

        $subscriptions = DB::table('subscriptions')
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get(['id', 'type']);
        $items = DB::table('subscription_items')->whereIn('subscription_id', $subscriptions->pluck('id'))->get()->groupBy('subscription_id');

        return $subscriptions->mapWithKeys(function ($subscription) use ($items, $planPrices, $tierMonthly) {
            $cents = collect($items->get($subscription->id, []))->sum(function ($item) use ($planPrices, $tierMonthly) {
                $quantity = max(1, (int) $item->quantity);
                if ($price = $planPrices->get($item->stripe_price)) {
                    return intdiv($price->price_cents * ($price->interval === 'year' ? 1 : 12), 12) * $quantity;
                }

                return ($tierMonthly->get($item->stripe_price) ?? 0) * $quantity;
            });

            return [$subscription->type => $cents];
        });
    }

    /** Las 3 fuentes de las altas: utm_source, o el dominio de la web de origen, o "directo". */
    private function signupSources(CarbonInterface $since, CarbonInterface $until): array
    {
        return User::whereBetween('created_at', [$since, $until])->get(['attribution'])
            ->map(function (User $user) {
                $a = (array) $user->attribution;
                if (filled($a['utm_source'] ?? null)) {
                    return Str::lower($a['utm_source']);
                }
                $host = parse_url((string) ($a['referrer'] ?? ''), PHP_URL_HOST);

                return $host ? Str::after(Str::lower($host), 'www.') : 'directo';
            })
            ->countBy()->sortDesc()->take(3)->all();
    }

    /** Buzones activos de clientes por encima del 90 % y del 100 % de su cuota. */
    private function quota(): array
    {
        $mailboxes = Mailbox::whereNotNull('user_id')->where('status', 'active')->where('quota_bytes', '>', 0)->get(['email', 'quota_bytes']);
        $usage = QuotaUsage::whereIn('username', $mailboxes->pluck('email'))->pluck('bytes', 'username');
        $ratio = fn (Mailbox $m) => ($usage[$m->email] ?? 0) / $m->quota_bytes;

        return [
            'over_90' => $mailboxes->filter(fn ($m) => $ratio($m) >= 0.9)->count(),
            'over_100' => $mailboxes->filter(fn ($m) => $ratio($m) >= 1.0)->count(),
        ];
    }
}
