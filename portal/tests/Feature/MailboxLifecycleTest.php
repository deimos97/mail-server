<?php

namespace Tests\Feature;

use App\Mail\LifecycleNotice;
use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\Plan;
use App\Models\User;
use App\Services\AppPasswords;
use App\Services\MailboxLifecycle;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class MailboxLifecycleTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();
        $this->user = User::factory()->create(['email' => 'ana@gmail.com']);
    }

    private function mailbox(string $plan, string $local = 'ana'): Mailbox
    {
        $plan = Plan::where('slug', $plan)->first();

        return Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => $plan->id, 'tier' => $plan->tier,
            'local_part' => $local, 'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'can_send' => true]);
    }

    private function runLifecycle(): void
    {
        app(MailboxLifecycle::class)->run();
    }

    private function sent(string $type): bool
    {
        return Mail::queued(LifecycleNotice::class, fn ($m) => $m->type === $type)->isNotEmpty();
    }

    public function test_unpaid_path_suspends_warns_deletes_and_quarantines(): void
    {
        $mailbox = $this->mailbox('basico');
        [$device] = app(AppPasswords::class)->create($mailbox, 'iPhone');
        MailboxState::for($mailbox->id)->fill(['unpaid_since' => now()])->save();

        $this->travel(9)->days();
        $this->runLifecycle();
        $this->assertSame('active', $mailbox->fresh()->status);

        $this->travel(1)->days();                 // día 10
        $this->runLifecycle();
        $this->assertSame('suspended', $mailbox->fresh()->status);
        $this->assertTrue($this->sent('suspended'));

        $this->travel(13)->days();                // día 23: aviso de borrado
        $this->runLifecycle();
        $this->assertTrue($this->sent('deletion_warning'));

        $this->travel(7)->days();                 // día 30: borrado
        $this->runLifecycle();
        $mailbox->refresh();
        $this->assertSame('deleted', $mailbox->status);
        $this->assertFalse($mailbox->active);
        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertTrue($mailbox->release_at->isSameDay(now()->addDays(60)));   // libre el día 90
        $this->assertTrue($this->sent('deleted'));
    }

    public function test_inactive_free_mailbox_is_warned_then_deleted(): void
    {
        $mailbox = $this->mailbox('gratis');
        DB::connection('mailserver')->table('last_logins')->insert(['username' => $mailbox->email, 'device' => 0, 'last_login' => now()->timestamp]);

        $this->travelTo(now()->addMonths(6)->addDay());
        $this->runLifecycle();
        $this->assertTrue($this->sent('inactive_warning'));
        $this->assertSame('active', $mailbox->fresh()->status);

        $this->travel(31)->days();
        $this->runLifecycle();
        $this->assertSame('deleted', $mailbox->fresh()->status);
        $this->assertTrue($this->sent('inactive_deleted'));
    }

    public function test_using_the_mailbox_after_the_warning_keeps_it(): void
    {
        $mailbox = $this->mailbox('gratis');
        DB::connection('mailserver')->table('last_logins')->insert(['username' => $mailbox->email, 'device' => 0, 'last_login' => now()->timestamp]);

        $this->travelTo(now()->addMonths(6)->addDay());
        $this->runLifecycle();
        $this->travel(2)->days();
        DB::connection('mailserver')->table('last_logins')->update(['last_login' => now()->timestamp]);   // vuelve a entrar
        $this->runLifecycle();

        $this->travel(40)->days();
        $this->runLifecycle();
        $this->assertSame('active', $mailbox->fresh()->status);
        $this->assertNull(MailboxState::find($mailbox->id)->inactivity_warned_at);
    }

    public function test_paid_and_system_mailboxes_are_not_deleted_for_inactivity(): void
    {
        $paid = $this->mailbox('basico');
        $this->travel(8)->months();
        $this->runLifecycle();
        $this->travel(2)->months();
        $this->runLifecycle();

        $this->assertSame('active', $paid->fresh()->status);
        $this->assertFalse($this->sent('inactive_warning'));
    }
}
