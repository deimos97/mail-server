<?php

namespace Tests\Feature;

use App\Mail\ConfirmNewRecoveryEmail;
use App\Mail\RecoveryEmailChanged;
use App\Models\Mailbox;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class RecoveryEmailChangeTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();

        $this->user = User::factory()->unverified()->create(['email' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => 1,
            'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'can_send' => false]);
    }

    private function requestCode(string $email = 'ana.nueva@outlook.com'): string
    {
        $this->actingAs($this->user)->post('/cuenta/email', ['email' => $email, 'password' => 'una-clave-larga-123'])
            ->assertRedirect('/cuenta/email');

        $code = null;
        Mail::assertQueued(ConfirmNewRecoveryEmail::class, function ($mail) use ($email, &$code) {
            $code = $mail->code;

            return $mail->hasTo($email);
        });

        return $code;
    }

    public function test_change_needs_the_code_and_warns_the_old_email(): void
    {
        $code = $this->requestCode();
        $this->assertSame('ana@gmail.com', $this->user->fresh()->email);          // aún no cambia
        $this->get('/cuenta/email')->assertSee('ana.nueva@outlook.com');

        $this->post('/cuenta/email/confirmar', ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/cuenta/email/confirmar', ['code' => $code])->assertRedirect('/cuenta');

        $user = $this->user->fresh();
        $this->assertSame('ana.nueva@outlook.com', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Mailbox::firstOrFail()->can_send);                       // ya tiene un email verificado
        Mail::assertQueued(RecoveryEmailChanged::class, fn ($mail) => $mail->hasTo('ana@gmail.com') && $mail->masked === 'an•••••••@outlook.com');

        // Entra con el nuevo
        $this->post('/salir');
        $this->post('/entrar', ['login' => 'ana.nueva@outlook.com', 'password' => 'una-clave-larga-123'])->assertRedirect('/cuenta');
    }

    public function test_wrong_password_or_invalid_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'otra@gmail.com']);
        $this->actingAs($this->user);

        $this->post('/cuenta/email', ['email' => 'x@outlook.com', 'password' => 'mal'])->assertSessionHasErrors('password');
        $this->post('/cuenta/email', ['email' => 'otra@gmail.com', 'password' => 'una-clave-larga-123'])->assertSessionHasErrors('email');
        $this->post('/cuenta/email', ['email' => 'yo@unagrandeylibre.es', 'password' => 'una-clave-larga-123'])->assertSessionHasErrors('email');
        Mail::assertNotQueued(ConfirmNewRecoveryEmail::class);
    }

    public function test_code_stops_working_after_five_failures_and_can_be_cancelled(): void
    {
        $code = $this->requestCode();
        $wrong = $code === '000000' ? '111111' : '000000';
        foreach (range(1, 5) as $_) {
            $this->post('/cuenta/email/confirmar', ['code' => $wrong]);
        }
        $this->post('/cuenta/email/confirmar', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertSame('ana@gmail.com', $this->user->fresh()->email);

        $this->post('/cuenta/email/cancelar')->assertRedirect('/cuenta');
        $this->get('/cuenta/email')->assertSee('Nuevo email de recuperación');
    }
}
