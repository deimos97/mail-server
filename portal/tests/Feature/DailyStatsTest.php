<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\Plan;
use App\Models\User;
use App\Services\Support;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class DailyStatsTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();
    }

    public function test_daily_stats_in_json(): void
    {
        $free = Plan::where('is_free', true)->firstOrFail();
        $paid = Plan::where('is_free', false)->firstOrFail();
        $user = User::factory()->create(['attribution' => ['utm_source' => 'Meta']]);
        Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'plan_id' => $free->id, 'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'status' => 'active']);
        $mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'plan_id' => $paid->id, 'local_part' => 'anapro', 'email' => 'anapro@unagrandeylibre.es', 'password' => 'x', 'status' => 'active']);
        DB::table('plan_prices')->insert(['plan_id' => $paid->id, 'stripe_price_id' => 'price_year', 'price_cents' => 12000, 'interval' => 'year', 'created_at' => now(), 'updated_at' => now()]);
        $subscription = DB::table('subscriptions')->insertGetId(['user_id' => $user->id, 'type' => 'mailbox:'.$mailbox->id, 'stripe_id' => 'sub_1', 'stripe_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('subscription_items')->insert(['subscription_id' => $subscription, 'stripe_id' => 'si_1', 'stripe_product' => 'prod_1', 'stripe_price' => 'price_year', 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        app(Support::class)->open(null, 'lucia@gmail.com', 'Duda', 'Hola');

        $this->assertSame(0, Artisan::call('report:daily-stats', ['--since' => now()->subHour()->toIso8601String()]));
        $stats = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $stats['signups']['users']);
        $this->assertSame(['mailboxes' => 2, 'free' => 1, 'paid' => 1, 'paid_monthly_cents' => 1000], array_intersect_key($stats['signups'], array_flip(['mailboxes', 'free', 'paid', 'paid_monthly_cents'])));
        $this->assertSame(['meta' => 1], $stats['signups']['sources']);
        $this->assertSame(['paid_mailboxes' => 1, 'monthly_cents' => 1000], $stats['revenue']);
        $this->assertSame(1, $stats['support']['new_tickets']);
        $this->assertSame(['over_90' => 0, 'over_100' => 0], $stats['quota']);
    }
}
