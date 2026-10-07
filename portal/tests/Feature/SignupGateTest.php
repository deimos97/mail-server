<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SignupGateTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        config(['signup.open' => false, 'signup.preview_token' => 'token-de-prueba']);
    }

    public function test_signup_is_closed_by_default(): void
    {
        $this->get('/alta?nombre=pepito')->assertOk()->assertSee('El alta abre muy pronto');
        $this->post('/alta/cuenta', [])->assertOk()->assertSee('El alta abre muy pronto');
        $this->assertDatabaseCount('mailbox_reservations', 0);
    }

    public function test_wrong_preview_token_does_not_open_it(): void
    {
        $this->get('/alta?acceso=otro')->assertSee('El alta abre muy pronto');
    }

    public function test_preview_token_opens_it_for_the_session(): void
    {
        $this->get('/alta?acceso=token-de-prueba&nombre=pepito')->assertRedirect('/alta?nombre=pepito');
        $this->get('/alta?nombre=pepito')->assertRedirect('/alta/cuenta');
        $this->get('/alta/cuenta')->assertOk()->assertSee('Crea tu cuenta');
    }

    public function test_email_link_works_while_closed(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('signup.verify.link', now()->addHour(), ['user' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertOk()->assertSee('Email confirmado');
    }
}
