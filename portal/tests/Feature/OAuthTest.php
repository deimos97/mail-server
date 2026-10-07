<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\OauthToken;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private const REDIRECT = 'https://webmail.unagrandeylibre.es/index.php/login/oauth';

    private User $user;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        config(['oauth.client.secret' => 'secreto', 'oauth.introspection_ips' => ['127.0.0.1']]);

        $this->user = User::factory()->create();
        $this->mailbox = $this->mailboxFor($this->user, 'ana');
    }

    private function mailboxFor(User $user, string $local): Mailbox
    {
        return Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'local_part' => $local,
            'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'can_send' => true]);
    }

    private function authorizeUrl(array $overrides = []): string
    {
        return '/oauth/authorize?'.http_build_query($overrides + [
            'response_type' => 'code', 'client_id' => 'webmail', 'redirect_uri' => self::REDIRECT,
            'state' => 'xyz', 'scope' => 'email',
        ]);
    }

    /** Recorre authorize y devuelve el código. */
    private function code(array $overrides = []): string
    {
        $location = $this->actingAs($this->user)->get($this->authorizeUrl($overrides))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('xyz', $query['state']);

        return $query['code'];
    }

    private function exchange(string $code, array $overrides = [])
    {
        return $this->postJson('/api/oauth/token', $overrides + [
            'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => self::REDIRECT,
            'client_id' => 'webmail', 'client_secret' => 'secreto',
        ]);
    }

    public function test_full_flow_gives_roundcube_the_mailbox_address(): void
    {
        $tokens = $this->exchange($this->code())->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'token_type'])->json();

        $this->withToken($tokens['access_token'])->getJson('/api/oauth/userinfo')
            ->assertOk()->assertJson(['email' => 'ana@unagrandeylibre.es']);

        $this->postJson('/api/oauth/introspect', ['token' => $tokens['access_token']])
            ->assertOk()->assertExactJson(['active' => true, 'username' => 'ana@unagrandeylibre.es']);
    }

    public function test_authorize_requires_a_web_session(): void
    {
        $this->get($this->authorizeUrl())->assertRedirect('/entrar');
    }

    public function test_unknown_client_or_redirect_uri_never_redirects(): void
    {
        $this->actingAs($this->user);
        $this->get($this->authorizeUrl(['client_id' => 'otro']))->assertOk()->assertSee('Enlace no válido');
        $this->get($this->authorizeUrl(['redirect_uri' => 'https://atacante.example/robar']))->assertOk()->assertSee('Enlace no válido');
    }

    public function test_with_several_mailboxes_it_asks_unless_one_was_chosen(): void
    {
        $other = $this->mailboxFor($this->user, 'ana.trabajo');
        $this->actingAs($this->user);

        $this->get($this->authorizeUrl())->assertOk()->assertSee('¿Qué correo quieres abrir?')->assertSee('ana.trabajo@unagrandeylibre.es');

        // "Abrir mi correo" desde la cuenta ya elige
        $this->get("/cuenta/webmail/{$other->id}")->assertRedirect(config('oauth.webmail_start_url'));
        $location = $this->get($this->authorizeUrl())->assertRedirect()->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $access = $this->exchange($query['code'])->json('access_token');
        $this->withToken($access)->getJson('/api/oauth/userinfo')->assertJson(['email' => 'ana.trabajo@unagrandeylibre.es']);
    }

    public function test_cannot_get_a_token_for_someone_elses_mailbox(): void
    {
        $foreign = $this->mailboxFor(User::factory()->create(), 'pedro');
        $this->actingAs($this->user);

        $this->post('/oauth/authorize', ['mailbox' => $foreign->id, 'client_id' => 'webmail', 'redirect_uri' => self::REDIRECT])->assertNotFound();
        $this->get("/cuenta/webmail/{$foreign->id}")->assertNotFound();
    }

    public function test_client_must_authenticate(): void
    {
        $code = $this->code();
        $this->exchange($code, ['client_secret' => 'mal'])->assertStatus(401)->assertJson(['error' => 'invalid_client']);

        // client_secret_basic también vale
        $this->withHeader('Authorization', 'Basic '.base64_encode('webmail:secreto'))
            ->postJson('/api/oauth/token', ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => self::REDIRECT])
            ->assertOk();
    }

    public function test_code_is_single_use_and_reuse_revokes_its_tokens(): void
    {
        $code = $this->code();
        $access = $this->exchange($code)->assertOk()->json('access_token');

        $this->exchange($code)->assertStatus(400)->assertJson(['error' => 'invalid_grant']);
        $this->withToken($access)->getJson('/api/oauth/userinfo')->assertStatus(401);
    }

    public function test_code_expires_and_must_match_redirect_uri(): void
    {
        $code = $this->code();
        $this->exchange($code, ['redirect_uri' => 'https://webmail.unagrandeylibre.es/otra'])->assertJson(['error' => 'invalid_grant']);

        $late = $this->code();
        $this->travel(61)->seconds();
        $this->exchange($late)->assertJson(['error' => 'invalid_grant']);
    }

    public function test_pkce_is_enforced_when_used(): void
    {
        $verifier = str_repeat('v', 50);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $code = $this->code(['code_challenge' => $challenge, 'code_challenge_method' => 'S256']);
        $this->exchange($code, ['code_verifier' => 'otro'])->assertJson(['error' => 'invalid_grant']);

        $code = $this->code(['code_challenge' => $challenge, 'code_challenge_method' => 'S256']);
        $this->exchange($code, ['code_verifier' => $verifier])->assertOk();
    }

    public function test_refresh_rotates_tokens(): void
    {
        $first = $this->exchange($this->code())->json();
        $grant = ['grant_type' => 'refresh_token', 'client_id' => 'webmail', 'client_secret' => 'secreto'];

        $second = $this->postJson('/api/oauth/token', $grant + ['refresh_token' => $first['refresh_token']])->assertOk()->json();
        $this->withToken($second['access_token'])->getJson('/api/oauth/userinfo')->assertOk();

        // El refresh viejo ya no vale, y su access token tampoco
        $this->postJson('/api/oauth/token', $grant + ['refresh_token' => $first['refresh_token']])->assertJson(['error' => 'invalid_grant']);
        $this->withToken($first['access_token'])->getJson('/api/oauth/userinfo')->assertStatus(401);
    }

    public function test_access_tokens_expire(): void
    {
        $access = $this->exchange($this->code())->json('access_token');
        $this->travel(3601)->seconds();
        $this->postJson('/api/oauth/introspect', ['token' => $access])->assertExactJson(['active' => false]);
    }

    public function test_suspending_the_mailbox_cuts_access_immediately(): void
    {
        $tokens = $this->exchange($this->code())->json();
        $this->mailbox->update(['status' => 'suspended']);

        $this->postJson('/api/oauth/introspect', ['token' => $tokens['access_token']])->assertExactJson(['active' => false]);
        $this->postJson('/api/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token'],
            'client_id' => 'webmail', 'client_secret' => 'secreto'])->assertJson(['error' => 'invalid_grant']);
    }

    public function test_introspection_only_from_the_server(): void
    {
        $access = $this->exchange($this->code())->json('access_token');
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/oauth/introspect', ['token' => $access])->assertForbidden();
    }

    public function test_unsupported_response_type_goes_back_with_error(): void
    {
        $location = $this->actingAs($this->user)->get($this->authorizeUrl(['response_type' => 'token']))->headers->get('Location');
        $this->assertStringContainsString('error=unsupported_response_type', $location);
    }

    public function test_tokens_are_stored_only_as_hashes(): void
    {
        $tokens = $this->exchange($this->code())->json();
        $row = OauthToken::firstOrFail();

        $this->assertSame(hash('sha256', $tokens['access_token']), $row->access_hash);
        $this->assertDatabaseMissing('oauth_tokens', ['access_hash' => $tokens['access_token']]);
    }
}
