<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordEmail;
use App\Models\AppPassword;
use App\Models\Mailbox;
use App\Models\User;
use App\Services\AppPasswords;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AuthAndAccountTest extends TestCase
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
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);

        $this->user = User::factory()->create(['email' => 'ana@gmail.com', 'password' => 'una-clave-larga-123']);
        $this->mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => 1,
            'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'can_send' => true]);
    }

    public function test_login_with_recovery_email_or_mailbox_address(): void
    {
        $this->post('/entrar', ['login' => 'ANA@gmail.com', 'password' => 'una-clave-larga-123'])->assertRedirect('/cuenta');
        $this->assertAuthenticatedAs($this->user);
        $this->post('/salir')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/entrar', ['login' => 'ana@unagrandeylibre.es', 'password' => 'una-clave-larga-123'])->assertRedirect('/cuenta');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_wrong_password_and_unknown_account_give_the_same_error(): void
    {
        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'mal'])->assertSessionHasErrors(['login' => 'El email o la contraseña no son correctos.']);
        $this->post('/entrar', ['login' => 'nadie@gmail.com', 'password' => 'mal'])->assertSessionHasErrors(['login' => 'El email o la contraseña no son correctos.']);
        $this->assertGuest();
    }

    public function test_login_is_throttled_per_account_and_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'mal']);
        }
        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_admins_cannot_use_the_customer_login(): void
    {
        $this->user->is_admin = true;
        $this->user->save();

        $this->post('/entrar', ['login' => 'ana@gmail.com', 'password' => 'una-clave-larga-123'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_password_reset_goes_to_recovery_email_even_if_asked_with_the_address(): void
    {
        $this->post('/recuperar', ['login' => 'ana@unagrandeylibre.es'])->assertSessionHas('status');
        $link = null;
        Mail::assertQueued(ResetPasswordEmail::class, function (ResetPasswordEmail $mail) use (&$link) {
            $link = $mail->link;

            return $mail->hasTo('ana@gmail.com');
        });

        // Misma respuesta si no existe, y no se envía nada
        $this->post('/recuperar', ['login' => 'nadie@gmail.com'])->assertSessionHas('status');
        Mail::assertQueuedCount(1);

        parse_str(parse_url($link, PHP_URL_QUERY), $query);
        $token = basename(parse_url($link, PHP_URL_PATH));
        $this->get($link)->assertOk()->assertSee('Elige una contraseña nueva');

        $this->post('/recuperar/nueva', ['token' => $token, 'email' => $query['email'], 'password' => 'otra-clave-larga-456'])
            ->assertRedirect('/entrar');
        $this->assertTrue(Hash::check('otra-clave-larga-456', $this->user->fresh()->password));

        // El enlace solo sirve una vez
        $this->post('/recuperar/nueva', ['token' => $token, 'email' => $query['email'], 'password' => 'tercera-clave-789'])
            ->assertSessionHasErrors('password');
    }

    public function test_account_page_lists_mailboxes(): void
    {
        $this->get('/cuenta')->assertRedirect('/entrar');

        $this->actingAs($this->user)->get('/cuenta')
            ->assertOk()->assertSee('ana@unagrandeylibre.es')->assertSee('Recibe y envía')->assertSee('Todavía no has conectado ninguno');
    }

    public function test_connecting_a_device_shows_the_password_once_and_stores_only_a_hash(): void
    {
        $response = $this->actingAs($this->user)->post('/cuenta/dispositivos', ['mailbox' => $this->mailbox->id, 'name' => 'iPhone de Ana'])
            ->assertOk()->assertSee('Contraseña para «iPhone de Ana»')->assertSee('no volveremos a mostrarla');

        preg_match('#writeText\(&quot;([a-z0-9]{16})&quot;\)|writeText\(\'([a-z0-9]{16})\'\)|writeText\("([a-z0-9]{16})"\)#', $response->getContent(), $m);
        $plain = array_values(array_filter(array_slice($m, 1)))[0] ?? null;
        $this->assertNotNull($plain, 'la contraseña aparece en la respuesta');

        $device = AppPassword::firstOrFail();
        $this->assertSame(substr($plain, 0, 6), $device->selector);
        $this->assertStringStartsWith('{BLF-CRYPT}$2y$', $device->password);
        $this->assertTrue(Hash::check($plain, substr($device->password, strlen('{BLF-CRYPT}'))));

        // Al volver a la cuenta ya no se ve
        $this->get('/cuenta')->assertSee('iPhone de Ana')->assertDontSee($plain);
    }

    public function test_cannot_add_devices_to_someone_elses_mailbox(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other)->post('/cuenta/dispositivos', ['mailbox' => $this->mailbox->id, 'name' => 'Intruso'])->assertNotFound();
        $this->assertSame(0, AppPassword::count());
    }

    public function test_revoking_a_device(): void
    {
        [$device] = app(AppPasswords::class)->create($this->mailbox, 'Móvil perdido');

        $this->actingAs($this->user)->post("/cuenta/dispositivos/{$device->id}/revocar")
            ->assertRedirect('/cuenta')->assertSessionHas('status');
        $this->assertNotNull($device->fresh()->revoked_at);
        $this->get('/cuenta')->assertSee('Hemos desconectado «Móvil perdido»');   // el aviso, una vez
        $this->get('/cuenta')->assertDontSee('Móvil perdido');                      // y ya no está en la lista

        // Ni dos veces ni el de otro
        $this->post("/cuenta/dispositivos/{$device->id}/revocar")->assertNotFound();
        [$other] = app(AppPasswords::class)->create($this->mailbox, 'Portátil');
        $this->actingAs(User::factory()->create())->post("/cuenta/dispositivos/{$other->id}/revocar")->assertNotFound();
        $this->assertNull($other->fresh()->revoked_at);
    }
}
