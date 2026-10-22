<?php

namespace Tests\Feature;

use App\Filament\Pages\Stats;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Models\User;
use App\Services\AdminStats;
use Database\Seeders\DemoPlansSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        AdminStats::flush();
    }

    private function mailbox(User $user, string $local, Plan $plan, int $quota = 1000): Mailbox
    {
        return Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'plan_id' => $plan->id, 'local_part' => $local,
            'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'status' => 'active', 'quota_bytes' => $quota]);
    }

    private function seedStats(): void
    {
        $free = Plan::where('is_free', true)->firstOrFail();
        $paid = Plan::where('slug', 'basico')->firstOrFail();
        $user = User::factory()->create();
        User::factory()->create();   // se registró y no llegó a tener buzón
        $this->mailbox($user, 'ana', $free);
        $this->mailbox($user, 'anapro', $paid);
        DB::connection('mailserver')->table('quota_usage')->insert([
            ['username' => 'ana@unagrandeylibre.es', 'bytes' => 950, 'messages' => 3],
            ['username' => 'anapro@unagrandeylibre.es', 'bytes' => 100, 'messages' => 1],
        ]);
        DB::connection('mailserver')->table('last_logins')->insert(['username' => 'ana@unagrandeylibre.es', 'device' => 0, 'last_login' => now()->subDay()->timestamp]);

        $today = now(AdminStats::TZ)->toDateString();
        $yesterday = now(AdminStats::TZ)->subDay()->toDateString();
        DB::table('stats_days')->insert([
            ['day' => $yesterday, 'visitors' => 30, 'pageviews' => 50, 'sources' => json_encode(['x' => 20, 'google.com' => 2]), 'sent' => 4, 'received' => 9,
                'rejected' => 7, 'bounced' => 1, 'disk_used_bytes' => null, 'disk_total_bytes' => null, 'vmail_bytes' => null, 'updated_at' => now()->subDay()],
            ['day' => $today, 'visitors' => 20, 'pageviews' => 25, 'sources' => json_encode(['x' => 5]), 'sent' => 2, 'received' => 3,
                'rejected' => 0, 'bounced' => 0, 'disk_used_bytes' => 40 * 1024 ** 3, 'disk_total_bytes' => 80 * 1024 ** 3, 'vmail_bytes' => 1024 ** 2, 'updated_at' => now()],
            // Fuera del periodo de 7 días
            ['day' => now(AdminStats::TZ)->subDays(20)->toDateString(), 'visitors' => 1000, 'pageviews' => 1000, 'sources' => null, 'sent' => 0, 'received' => 0,
                'rejected' => 0, 'bounced' => 0, 'disk_used_bytes' => null, 'disk_total_bytes' => null, 'vmail_bytes' => null, 'updated_at' => now()->subDays(20)],
        ]);
        DB::table('stats_mailbox_days')->insert([
            ['day' => $yesterday, 'email' => 'anapro@unagrandeylibre.es', 'sent' => 3, 'received' => 2],
            ['day' => $today, 'email' => 'ana@unagrandeylibre.es', 'sent' => 0, 'received' => 5],
            ['day' => $today, 'email' => 'noreply@unagrandeylibre.es', 'sent' => 40, 'received' => 0],   // no es de un cliente
        ]);
    }

    public function test_stats_for_the_period(): void
    {
        $this->seedStats();
        $stats = AdminStats::for(7);

        $visits = $stats->visits();
        $this->assertSame(50, $visits['visitors']);
        $this->assertSame(75, $visits['pageviews']);
        $this->assertSame(['x' => 25, 'google.com' => 2], $visits['sources']);
        $this->assertSame([0, 0, 0, 0, 0, 30, 20], $visits['visitors_per_day']);

        $accounts = $stats->accounts();
        $this->assertSame(1, $accounts['free']);
        $this->assertSame(1, $accounts['paid']);
        $this->assertSame(2, $accounts['new_users']);
        $this->assertEqualsWithDelta(4.0, $accounts['conversion'], 0.001);
        $this->assertSame(1, $accounts['users_without_mailbox']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 1], $accounts['new_paid_per_day']);

        $this->assertSame(['disk_used' => 40 * 1024 ** 3, 'disk_total' => 80 * 1024 ** 3, 'vmail' => 1024 ** 2, 'mailboxes_used' => 1050, 'mailboxes_quota' => 2000], $stats->storage());

        $usage = $stats->usage();
        $this->assertEqualsWithDelta(52.5, $usage['avg_quota_pct'], 0.001);
        $this->assertSame(1, $usage['over_90']);
        $this->assertSame(6, $usage['sent']);
        $this->assertSame(12, $usage['received']);
        $this->assertSame(3, $usage['customer_sent']);
        $this->assertSame(1, $usage['active_30d']);
        $this->assertSame(1, $usage['never_used']);
        $this->assertSame(['anapro@unagrandeylibre.es' => 3], $usage['top_senders']);
        $this->assertSame(['ana@unagrandeylibre.es' => 5, 'anapro@unagrandeylibre.es' => 2], $usage['top_receivers']);
    }

    public function test_admins_see_the_stats_page(): void
    {
        $this->seedStats();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->app_authentication_secret = AppAuthentication::make()->generateSecret();
        $admin->save();

        $this->actingAs($admin)->get(Stats::getUrl())->assertOk()->assertSee('Estadísticas');
        $this->actingAs(User::factory()->create())->get('/admin/estadisticas')->assertForbidden();
    }

    public function test_every_widget_renders_with_and_without_data(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin);

        foreach ([false, true] as $withData) {
            if ($withData) {
                $this->seedStats();
                AdminStats::flush();
            }
            foreach ((new Stats)->getWidgets() as $widget) {
                Livewire::test($widget, ['pageFilters' => ['days' => 7]])->assertOk();
            }
        }
        Livewire::test(\App\Filament\Widgets\Stats\TrafficOverview::class, ['pageFilters' => ['days' => 7]])
            ->assertSee('Buzones de pago')->assertSee('Conversión 4,00 %');
        Livewire::test(\App\Filament\Widgets\Stats\Rankings::class, ['pageFilters' => ['days' => 7]])
            ->assertSee('google.com')->assertSee('anapro@unagrandeylibre.es');
    }

    public function test_the_page_works_before_mail_stats_has_ever_run(): void
    {
        $this->assertNull(AdminStats::for(30)->updatedAt());
        $this->assertSame(0, AdminStats::for(30)->visits()['visitors']);
        $this->assertNull(AdminStats::for(30)->storage()['disk_total']);
    }
}
