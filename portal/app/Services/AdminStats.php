<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\LastLogin;
use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\QuotaUsage;
use App\Models\StatsDay;
use App\Models\StatsMailboxDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Datos del panel de estadísticas del admin para los últimos N días (hora de Madrid, hoy incluido).
 *
 * Visitas, correo y disco salen de `stats_days` y `stats_mailbox_days` (los escribe `mail-stats` cada hora
 * desde los logs del servidor); cuentas, dinero y cuotas, de la BD al momento. Solo cuentan los buzones de
 * clientes (con usuario y que no son del servicio). El dinero es el de DailyStats: precio de lista al mes.
 */
class AdminStats
{
    public const TZ = 'Europe/Madrid';

    /** @var array<int, self> uno por periodo y petición: lo comparten todos los widgets de la página */
    private static array $instances = [];

    private array $memo = [];

    private function __construct(public readonly int $days) {}

    public static function for(int $days): self
    {
        return self::$instances[$days] ??= new self($days);
    }

    public static function flush(): void
    {
        self::$instances = [];
    }

    /** @return list<string> los días del periodo, Y-m-d */
    public function dayKeys(): array
    {
        $today = CarbonImmutable::now(self::TZ)->startOfDay();

        return collect(range($this->days - 1, 0))->map(fn (int $ago) => $today->subDays($ago)->toDateString())->all();
    }

    public function since(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->dayKeys()[0], self::TZ)->utc();
    }

    public function visits(): array
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $days = $this->trafficDays();

            return [
                'visitors' => $days->sum('visitors'),
                'pageviews' => $days->sum('pageviews'),
                'visitors_per_day' => $this->perDay($days, 'visitors'),
                'pageviews_per_day' => $this->perDay($days, 'pageviews'),
                'sources' => $days->flatMap(fn (StatsDay $d) => collect($d->sources ?? [])->map(fn ($n, $source) => compact('source', 'n'))->values())
                    ->groupBy('source')->map->sum('n')->sortDesc()->take(8)->all(),
            ];
        })();
    }

    public function accounts(): array
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $live = $this->customerMailboxes()->whereIn('status', ['active', 'suspended']);
            $new = $this->customerMailboxes()->where('status', '!=', 'pending')->filter(fn (Mailbox $m) => $m->created_at?->gte($this->since()));
            $isFree = fn (Mailbox $m) => ! $m->plan || $m->plan->is_free;
            $newPerDay = fn (bool $free) => collect($this->dayKeys())->mapWithKeys(fn ($day) => [$day => 0])
                ->merge($new->filter(fn ($m) => $isFree($m) === $free)->countBy(fn (Mailbox $m) => $m->created_at->tz(self::TZ)->toDateString()))
                ->only($this->dayKeys())->values()->all();
            $newUsers = User::where('is_admin', false)->where('created_at', '>=', $this->since())->count();
            $visitors = $this->visits()['visitors'];
            $withMailbox = $this->customerMailboxes()->where('status', '!=', 'deleted')->pluck('user_id')->unique();
            $checkouts = Checkout::where('created_at', '>=', $this->since())->get(['user_id', 'status']);
            $subscriptions = app(DailyStats::class)->activeSubscriptions();

            return [
                'free' => $live->filter($isFree)->count(),
                'paid' => $live->reject($isFree)->count(),
                'suspended' => $live->where('status', 'suspended')->count(),
                'by_plan' => $live->countBy(fn (Mailbox $m) => $m->plan?->name ?? 'Sin plan')->sortDesc()->all(),
                'new_free' => $new->filter($isFree)->count(),
                'new_paid' => $new->reject($isFree)->count(),
                'new_free_per_day' => $newPerDay(true),
                'new_paid_per_day' => $newPerDay(false),
                'new_users' => $newUsers,
                'conversion' => $visitors ? $newUsers / $visitors * 100 : null,
                'users_without_mailbox' => User::where('is_admin', false)->whereNotIn('id', $withMailbox)->count(),
                'checkouts_completed' => $checkouts->where('status', 'completed')->count(),
                // Quien abrió el pago, no lo terminó y no ha pagado después
                'checkouts_abandoned' => $checkouts->where('status', 'expired')->pluck('user_id')->unique()
                    ->diff($checkouts->where('status', 'completed')->pluck('user_id'))->count(),
                'monthly_cents' => $subscriptions->sum(),
                'subscriptions' => $subscriptions->count(),
                'deleted' => $this->customerMailboxes()->filter(fn (Mailbox $m) => $m->deleted_at?->gte($this->since()))->count(),
                'unpaid_now' => MailboxState::whereNotNull('unpaid_since')->count(),
            ];
        })();
    }

    public function storage(): array
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $live = $this->customerMailboxes()->where('status', 'active');
            $usage = $this->usageBytes();
            $disk = StatsDay::whereNotNull('disk_total_bytes')->orderByDesc('day')->first();

            return [
                'disk_used' => $disk?->disk_used_bytes,
                'disk_total' => $disk?->disk_total_bytes,
                'vmail' => $disk?->vmail_bytes,
                'mailboxes_used' => $live->sum(fn (Mailbox $m) => $usage[$m->email] ?? 0),
                'mailboxes_quota' => $live->sum('quota_bytes'),
            ];
        })();
    }

    public function usage(): array
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $live = $this->customerMailboxes()->where('status', 'active');
            $usage = $this->usageBytes();
            $ratios = $live->where('quota_bytes', '>', 0)->map(fn (Mailbox $m) => ($usage[$m->email] ?? 0) / $m->quota_bytes * 100);
            $days = $this->trafficDays();
            $emails = $live->pluck('email');
            $perMailbox = StatsMailboxDay::whereIn('email', $emails)->where('day', '>=', $this->dayKeys()[0])
                ->selectRaw('email, SUM(sent) as sent, SUM(received) as received')->groupBy('email')->get();
            $logins = LastLogin::whereIn('username', $emails)->selectRaw('username, MAX(last_login) as last')->groupBy('username')->pluck('last', 'username');
            $top = fn (string $column) => $perMailbox->where($column, '>', 0)->sortByDesc($column)->take(5)->pluck($column, 'email')->map(fn ($n) => (int) $n)->all();

            return [
                'avg_quota_pct' => $ratios->avg(),
                'over_90' => $ratios->filter(fn ($r) => $r >= 90)->count(),
                'sent' => $days->sum('sent'),
                'received' => $days->sum('received'),
                'rejected' => $days->sum('rejected'),
                'bounced' => $days->sum('bounced'),
                'sent_per_day' => $this->perDay($days, 'sent'),
                'received_per_day' => $this->perDay($days, 'received'),
                'mailboxes' => $live->count(),
                'customer_sent' => (int) $perMailbox->sum('sent'),
                'customer_received' => (int) $perMailbox->sum('received'),
                'mailboxes_sending' => $perMailbox->where('sent', '>', 0)->count(),
                'active_30d' => $logins->filter(fn ($t) => $t >= now()->subDays(30)->timestamp)->count(),
                'never_used' => $emails->diff($logins->keys())->count(),
                'top_senders' => $top('sent'),
                'top_receivers' => $top('received'),
            ];
        })();
    }

    /** La última vez que `mail-stats` escribió (null si aún no ha pasado nunca). */
    public function updatedAt(): ?CarbonImmutable
    {
        $at = StatsDay::max('updated_at');

        return $at ? CarbonImmutable::parse($at, 'UTC')->tz(self::TZ) : null;
    }

    /** @return Collection<int, Mailbox> */
    private function customerMailboxes(): Collection
    {
        return $this->memo[__FUNCTION__] ??= Mailbox::with('plan')->whereNotNull('user_id')
            ->where(fn ($q) => $q->whereNull('tier')->orWhere('tier', '!=', 'system'))->get();
    }

    /** @return Collection<string, int> */
    private function usageBytes(): Collection
    {
        return $this->memo[__FUNCTION__] ??= QuotaUsage::whereIn('username', $this->customerMailboxes()->pluck('email'))->pluck('bytes', 'username');
    }

    /** @return Collection<int, StatsDay> */
    private function trafficDays(): Collection
    {
        return $this->memo[__FUNCTION__] ??= StatsDay::where('day', '>=', $this->dayKeys()[0])->get();
    }

    /** @return list<int> un valor por día del periodo (0 si ese día no hay fila) */
    private function perDay(Collection $days, string $column): array
    {
        $byDay = $days->mapWithKeys(fn (StatsDay $d) => [$d->day->toDateString() => $d->{$column}]);

        return collect($this->dayKeys())->map(fn ($day) => (int) ($byDay[$day] ?? 0))->all();
    }
}
