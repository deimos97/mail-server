<?php

namespace Tests\Feature;

use App\Mail\QuotaNotice;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Models\User;
use App\Services\QuotaWarnings;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class QuotaWarningsTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();
        $plan = Plan::where('slug', 'gratis')->first();
        $this->mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => User::factory()->create(['email' => 'ana@gmail.com'])->id,
            'plan_id' => $plan->id, 'tier' => 'free', 'quota_bytes' => 1000, 'local_part' => 'anabel',
            'email' => 'anabel@unagrandeylibre.es', 'password' => 'x', 'status' => 'active']);
    }

    private function use(int $bytes): void
    {
        DB::connection('mailserver')->table('quota_usage')->updateOrInsert(['username' => $this->mailbox->email], ['bytes' => $bytes, 'messages' => 1]);
    }

    public function test_warns_once_at_80_and_once_at_100_and_again_after_freeing_space(): void
    {
        $warnings = app(QuotaWarnings::class);

        $this->use(500);
        $this->assertSame(0, $warnings->run());

        $this->use(850);
        $this->assertSame(1, $warnings->run());
        $this->assertSame(0, $warnings->run());                                   // una sola vez por nivel
        Mail::assertQueued(QuotaNotice::class, fn ($mail) => $mail->hasTo('ana@gmail.com') && $mail->level === 80
            && $mail->free && $mail->upsellName === 'Básico');

        $this->use(1000);
        $this->assertSame(1, $warnings->run());
        Mail::assertQueued(QuotaNotice::class, fn ($mail) => $mail->level === 100 && $mail->envelope()->subject === 'Tu buzón está lleno (anabel@unagrandeylibre.es)');

        $this->use(600);                                                           // libera espacio: vuelve a avisar si se llena
        $warnings->run();
        $this->use(900);
        $this->assertSame(1, $warnings->run());
    }

    public function test_internal_mailboxes_are_not_warned(): void
    {
        $this->mailbox->update(['tier' => 'system', 'plan_id' => null]);
        $this->use(1000);

        $this->assertSame(0, app(QuotaWarnings::class)->run());
    }

    public function test_the_email_renders(): void
    {
        $this->use(1000);
        $html = (new QuotaNotice($this->mailbox->fresh(), 100))->render();

        $this->assertStringContainsString('los correos nuevos se rechazan', $html);
        $this->assertStringContainsString('Ver planes', $html);
        $this->assertStringContainsString(route('account.plan', $this->mailbox->id), $html);
    }
}
