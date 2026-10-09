<?php

namespace Tests\Feature;

use App\Mail\SecurityNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        Mail::fake();
        $this->user = User::factory()->create(['email' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
    }

    private function otp(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    /** Activa la 2FA desde Mi cuenta → Seguridad. Devuelve [secreto, códigos de recuperación]. */
    private function enable(): array
    {
        $this->actingAs($this->user)->post('/cuenta/seguridad/dos-pasos', ['password' => 'una-clave-larga-123'])->assertRedirect('/cuenta/seguridad');
        $secret = session('two_factor.pending');
        $this->get('/cuenta/seguridad')->assertOk()->assertSee('data:image/svg+xml', false);

        $this->post('/cuenta/seguridad/dos-pasos/confirmar', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/cuenta/seguridad/dos-pasos/confirmar', ['code' => $this->otp($secret)])->assertRedirect('/cuenta/seguridad');
        $codes = session('two_factor.codes');
        $this->post('/salir');

        return [$secret, $codes];
    }

    public function test_enabling_two_factor_needs_the_password_and_a_valid_code(): void
    {
        $this->actingAs($this->user)->post('/cuenta/seguridad/dos-pasos', ['password' => 'mal'])->assertSessionHasErrors('password');

        [, $codes] = $this->enable();
        $this->assertTrue($this->user->fresh()->hasTwoFactor());
        $this->assertCount(8, $codes);
        Mail::assertQueued(SecurityNotice::class, fn ($m) => $m->type === 'two_factor_on');
    }

    public function test_login_asks_for_the_code_and_codes_cannot_be_reused(): void
    {
        [$secret] = $this->enable();
        Cache::flush();   // la librería TOTP usa la hora real: simulamos que el código de la activación ya pasó

        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123'])->assertRedirect('/entrar/verificacion');
        $this->assertGuest();

        $this->post('/entrar/verificacion', ['code' => '000000'])->assertSessionHasErrors('code');
        $code = $this->otp($secret);
        $this->post('/entrar/verificacion', ['code' => $code])->assertRedirect('/cuenta');
        $this->assertAuthenticatedAs($this->user);

        // El mismo código otra vez, no
        $this->post('/salir');
        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->post('/entrar/verificacion', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_codes_work_once(): void
    {
        [, $codes] = $this->enable();

        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->post('/entrar/verificacion', ['code' => strtoupper($codes[0])])->assertRedirect('/cuenta');
        $this->assertCount(7, $this->user->fresh()->two_factor_recovery_codes);

        $this->post('/salir');
        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->post('/entrar/verificacion', ['code' => $codes[0]])->assertSessionHasErrors('code');
    }

    public function test_the_challenge_expires(): void
    {
        $this->enable();
        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->travel(6)->minutes();
        $this->get('/entrar/verificacion')->assertRedirect('/entrar');
    }

    public function test_disabling_needs_the_password(): void
    {
        [$secret] = $this->enable();
        $this->actingAs($this->user)->post('/cuenta/seguridad/dos-pasos/desactivar', ['password' => 'mal'])->assertSessionHasErrors('password');
        $this->assertTrue($this->user->fresh()->hasTwoFactor());

        $this->post('/cuenta/seguridad/dos-pasos/desactivar', ['password' => 'una-clave-larga-123'])->assertRedirect('/cuenta/seguridad');
        $this->assertFalse($this->user->fresh()->hasTwoFactor());
        $this->assertNull($this->user->fresh()->two_factor_secret);
    }

    public function test_passkey_registration_options_and_bad_passkeys(): void
    {
        $this->actingAs($this->user);
        $options = $this->getJson('/cuenta/seguridad/passkeys/opciones')->assertOk()->json();
        $this->assertNotEmpty($options['challenge']);
        $this->assertSame('ana@gmail.com', $options['user']['name']);

        $this->postJson('/cuenta/seguridad/passkeys', ['name' => 'iPhone', 'passkey' => '{"id":"falso"}'])->assertStatus(422);
        $this->assertSame(0, $this->user->passkeys()->count());

        $this->get('/cuenta/seguridad')->assertOk()->assertSee('Passkeys');
        $this->post('/salir');
        $this->getJson('/entrar/passkey/opciones')->assertOk()->assertJsonStructure(['challenge']);
        $this->post('/entrar/passkey', ['start_authentication_response' => '{}'])->assertRedirect();
        $this->assertGuest();
    }
}
