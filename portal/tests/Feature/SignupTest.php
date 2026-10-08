<?php

namespace Tests\Feature;

use App\Mail\VerifyRecoveryEmail;
use App\Models\Mailbox;
use App\Models\MailboxReservation;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private bool $turnstilePasses = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class, DemoPlansSeeder::class]);
        Mail::fake();
        Http::fake([
            'challenges.cloudflare.com/*' => fn () => Http::response(['success' => $this->turnstilePasses]),
            'api.pwnedpasswords.com/*' => Http::response(''),
        ]);
    }

    private function account(array $overrides = []): array
    {
        return $overrides + ['email' => 'javier@gmail.com', 'password' => 'una-clave-larga-123', 'terms' => '1', 'cf-turnstile-response' => 'ok'];
    }

    public function test_full_free_signup(): void
    {
        $this->get('/alta?nombre=JavierVarela&plan=gratis&utm_source=meta')->assertRedirect('/alta/cuenta');
        $this->assertDatabaseHas('mailbox_reservations', ['local_part' => 'javiervarela']);

        $this->post('/alta/cuenta', $this->account())->assertRedirect('/alta/plan');
        $user = User::where('email', 'javier@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('meta', $user->attribution['utm_source']);
        $this->assertNotNull($user->terms_accepted_at);

        $code = null;
        Mail::assertQueued(VerifyRecoveryEmail::class, function (VerifyRecoveryEmail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('javier@gmail.com');
        });

        $this->get('/alta/plan')->assertOk()->assertSee('Gratis')->assertSee('Muy pronto');
        $this->post('/alta/plan', ['plan' => 'gratis'])->assertRedirect('/alta/verificar');

        $mailbox = Mailbox::where('email', 'javiervarela@unagrandeylibre.es')->firstOrFail();
        $this->assertSame($user->id, (int) $mailbox->user_id);
        $this->assertSame('free', $mailbox->tier);
        $this->assertSame(1024 ** 3, $mailbox->quota_bytes);
        $this->assertFalse($mailbox->can_send);                       // no envía hasta verificar
        $this->assertStringStartsWith('{BLF-CRYPT}$2y$', $mailbox->password);
        $this->assertDatabaseMissing('mailbox_reservations', ['local_part' => 'javiervarela']);

        $this->post('/alta/verificar', ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/alta/verificar', ['code' => $code])->assertRedirect('/alta/listo');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue($mailbox->fresh()->can_send);
        $this->get('/alta/listo')->assertOk()->assertSee('javiervarela@unagrandeylibre.es')->assertSee('Ya puedes recibir y enviar');
    }

    public function test_reserved_name_is_taken_for_other_sessions_only(): void
    {
        $this->get('/alta?nombre=pepito')->assertRedirect('/alta/cuenta');

        // Otra sesión (la API no tiene sesión): para ella está cogido
        $this->getJson('/api/availability?local=pepito')->assertJson(['status' => 'taken']);
        // La misma sesión lo sigue viendo suyo
        $this->get('/alta/cuenta')->assertOk()->assertSee('pepito@unagrandeylibre.es');
    }

    public function test_reservation_expires(): void
    {
        $this->get('/alta?nombre=pepito')->assertRedirect('/alta/cuenta');
        $this->travel(MailboxReservation::MINUTES + 1)->minutes();

        $this->get('/alta/cuenta')->assertRedirect('/alta');
        $this->getJson('/api/availability?local=pepito')->assertJson(['status' => 'available']);
    }

    public function test_account_validation(): void
    {
        $this->get('/alta?nombre=pepito');

        $this->post('/alta/cuenta', $this->account(['email' => 'x@mailinator.com']))->assertSessionHasErrors('email');
        $this->post('/alta/cuenta', $this->account(['email' => 'x@unagrandeylibre.es']))->assertSessionHasErrors('email');
        $this->post('/alta/cuenta', $this->account(['password' => 'corta']))->assertSessionHasErrors('password');
        $this->post('/alta/cuenta', $this->account(['terms' => null]))->assertSessionHasErrors('terms');

        User::factory()->create(['email' => 'javier@gmail.com']);
        $this->post('/alta/cuenta', $this->account())->assertSessionHasErrors(['email' => 'Ya existe una cuenta con este email.']);
        $this->assertGuest();
    }

    public function test_turnstile_failure_blocks_signup(): void
    {
        $this->turnstilePasses = false;
        $this->get('/alta?nombre=pepito');

        $this->post('/alta/cuenta', $this->account())->assertSessionHasErrors('turnstile');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_signups_are_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/alta/cuenta', [])->assertStatus(302);
        }
        $this->post('/alta/cuenta', [])->assertStatus(429);
    }

    public function test_short_names_cannot_take_the_free_plan(): void
    {
        $this->get('/alta?nombre=jv');
        $this->post('/alta/cuenta', $this->account());

        $this->get('/alta/plan')->assertSee('Los nombres cortos necesitan un plan de pago');
        $this->post('/alta/plan', ['plan' => 'gratis'])->assertSessionHasErrors('plan');
        $this->assertSame(0, Mailbox::count());
    }

    public function test_paid_plans_need_stripe_and_consent(): void
    {
        $this->get('/alta?nombre=pepito');
        $this->post('/alta/cuenta', $this->account());

        $this->get('/alta/plan')->assertSee('Muy pronto');   // sin Stripe configurado
        $this->post('/alta/plan', ['plan' => 'basico'])->assertSessionHasErrors('immediate_start');
        $this->post('/alta/plan', ['plan' => 'basico', 'immediate_start' => '1'])
            ->assertSessionHasErrors(['plan' => 'Ese plan no está disponible ahora mismo.']);
    }

    public function test_signed_link_verifies_from_another_device(): void
    {
        $user = User::factory()->unverified()->create();
        $mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'local_part' => 'otro',
            'email' => 'otro@unagrandeylibre.es', 'password' => 'x', 'can_send' => false]);

        $url = URL::temporarySignedRoute('signup.verify.link', now()->addHour(), ['user' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertOk()->assertSee('Email confirmado');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue($mailbox->fresh()->can_send);

        $this->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
    }

    public function test_plan_step_requires_an_account(): void
    {
        $this->get('/alta/plan')->assertRedirect('/alta');
    }

    public function test_only_one_free_mailbox_per_user(): void
    {
        $this->get('/alta?nombre=primero&plan=gratis');
        $this->post('/alta/cuenta', $this->account());
        $this->post('/alta/plan', ['plan' => 'gratis'])->assertRedirect('/alta/verificar');

        // "Añadir otro buzón" desde Mi cuenta: el gratis sale bloqueado y explicado
        $this->get('/cuenta')->assertSee('Añadir otro buzón');
        $this->get('/alta?nuevo=1')->assertOk();
        $this->post('/alta/nombre', ['nombre' => 'segundo', 'dominio' => 'unagrandeylibre.es'])->assertRedirect('/alta/plan');
        $this->get('/alta/plan')->assertOk()
            ->assertSee('Tu cuenta ya tiene su buzón gratis')
            ->assertSee('Ya tienes tu buzón gratis')
            ->assertDontSee('Crear mi correo');

        // Y el servidor lo impide aunque se fuerce
        $this->post('/alta/plan', ['plan' => 'gratis'])
            ->assertSessionHasErrors(['plan' => 'Tu cuenta ya tiene su buzón gratis. Cada buzón extra va con un plan de pago.']);
        $this->assertSame(1, Mailbox::count());
    }
}
