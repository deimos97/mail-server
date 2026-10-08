<?php

namespace Tests\Feature;

use App\Mail\AccountDeleted;
use App\Models\Domain;
use App\Models\Mailbox;
use App\Models\User;
use App\Services\AppPasswords;
use App\Services\NameAvailability;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();
        config(['mail_provision.command' => '/usr/bin/sudo -n /usr/local/sbin/mail-provision']);
        Process::fake();

        $this->user = User::factory()->create(['email' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => 1,
            'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'can_send' => true]);
    }

    public function test_deleting_the_account_erases_mail_and_keeps_the_name_in_quarantine(): void
    {
        [$device] = app(AppPasswords::class)->create($this->mailbox, 'iPhone');

        $this->actingAs($this->user)->get('/cuenta/borrar')->assertOk()->assertSee('ana@unagrandeylibre.es');
        $this->post('/cuenta/borrar', ['password' => 'una-clave-larga-123', 'confirm' => '1'])
            ->assertRedirect('/entrar')->assertSessionHas('status');

        $this->assertGuest();
        $this->assertModelMissing($this->user);
        $mailbox = $this->mailbox->fresh();
        $this->assertSame('deleted', $mailbox->status);
        $this->assertFalse($mailbox->active);
        $this->assertFalse($mailbox->can_send);
        $this->assertNotNull($mailbox->deleted_at);
        $this->assertNotNull($device->fresh()->revoked_at);
        Process::assertRan(fn ($p) => $p->command === ['/usr/bin/sudo', '-n', '/usr/local/sbin/mail-provision', 'delete-content', 'ana@unagrandeylibre.es']);
        Mail::assertQueued(AccountDeleted::class, fn ($mail) => $mail->hasTo('ana@gmail.com'));

        // El nombre sigue ocupado (cuarentena)
        $this->assertFalse(app(NameAvailability::class)->check('ana', Domain::firstOrFail())->isAvailable());
    }

    public function test_needs_password_and_explicit_confirmation(): void
    {
        $this->actingAs($this->user);
        $this->post('/cuenta/borrar', ['password' => 'mal', 'confirm' => '1'])->assertSessionHasErrors('password');
        $this->post('/cuenta/borrar', ['password' => 'una-clave-larga-123'])->assertSessionHasErrors('confirm');

        $this->assertModelExists($this->user);
        $this->assertSame('active', $this->mailbox->fresh()->status);
        Process::assertNothingRan();
    }
}
