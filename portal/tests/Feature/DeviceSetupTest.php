<?php

namespace Tests\Feature;

use App\Models\AppPassword;
use App\Models\Mailbox;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class DeviceSetupTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        $this->user = User::factory()->create();
        $this->mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'local_part' => 'ana',
            'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'can_send' => true]);
    }

    private function plainFrom(string $html): string
    {
        preg_match('#writeText\((?:&quot;|"|\')([a-z0-9]{16})(?:&quot;|"|\')\)#', $html, $m);
        $this->assertNotEmpty($m, 'la contraseña aparece en la respuesta');

        return $m[1];
    }

    public function test_android_setup_shows_password_once_steps_and_stores_only_a_hash(): void
    {
        $this->actingAs($this->user)->get("/cuenta/configurar/{$this->mailbox->id}")->assertOk()->assertSee('Android (Gmail)');

        $html = $this->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'android', 'name' => ''])
            ->assertOk()->assertSee('no volveremos a mostrarla')->assertSee('Personal (IMAP)')->assertSee('mail.unagrandeylibre.es')
            ->getContent();
        $plain = $this->plainFrom($html);

        $device = AppPassword::firstOrFail();
        $this->assertSame('Android', $device->name);                       // nombre por defecto según la app
        $this->assertSame(substr($plain, 0, 6), $device->selector);
        $this->assertTrue(Hash::check($plain, substr($device->password, strlen('{BLF-CRYPT}'))));
        $this->get('/cuenta')->assertSee('Android')->assertDontSee($plain);
    }

    public function test_iphone_gets_a_one_time_profile_with_the_password_inside(): void
    {
        $html = $this->actingAs($this->user)->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'iphone', 'name' => 'iPhone de Ana'])
            ->assertOk()->assertSee('Instalar el perfil')->assertDontSee('Copiar contraseña')->getContent();
        preg_match('#href="([^"]*/cuenta/perfil/[A-Za-z0-9]{40})"#', $html, $m);
        $url = $m[1];

        $profile = $this->get($url)->assertOk()
            ->assertHeader('Content-Type', 'application/x-apple-aspen-config')
            ->assertHeader('Content-Disposition', 'attachment; filename="correo-ana.mobileconfig"')
            ->getContent();
        $this->assertStringContainsString('<key>EmailAddress</key><string>ana@unagrandeylibre.es</string>', $profile);
        $this->assertStringContainsString('<key>IncomingMailServerHostName</key><string>mail.unagrandeylibre.es</string>', $profile);
        preg_match('#<key>IncomingPassword</key><string>([a-z0-9]{16})</string>#', $profile, $p);
        $device = AppPassword::firstOrFail();
        $this->assertTrue(Hash::check($p[1], substr($device->password, strlen('{BLF-CRYPT}'))));
        $this->assertSame(1, simplexml_load_string($profile) !== false ? 1 : 0);   // XML válido

        $this->get($url)->assertNotFound();                                   // una sola vez
    }

    public function test_manual_setup_offers_ai_help_without_the_password(): void
    {
        $response = $this->actingAs($this->user)->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'android'])
            ->assertOk()->assertSee('Que te ayude una IA');
        $html = $response->getContent();
        preg_match('#<p class="mt-3 select-all[^>]*>(.*?)</p>#s', $html, $m);
        $password = preg_replace('/\s+|<[^>]+>/', '', $m[1]);
        $this->assertSame(16, strlen($password));

        preg_match('#href="(https://chatgpt\.com/\?q=[^"]+)"#', $html, $link);
        $prompt = rawurldecode(html_entity_decode($link[1]));
        $this->assertStringContainsString('ana@unagrandeylibre.es', $prompt);
        $this->assertStringContainsString('mail.unagrandeylibre.es, puerto 993', $prompt);
        $this->assertStringContainsString('NO me la pidas', $prompt);
        $this->assertStringNotContainsString($password, $prompt);
        $this->assertStringContainsString('https://claude.ai/new?q=', $html);
        $this->assertStringContainsString('https://gemini.google.com/app"', $html);       // sin texto en el enlace: se pega

        // Con perfil automático (iPhone) no hace falta
        $this->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'iphone'])->assertDontSee('Que te ayude una IA');
    }

    public function test_profile_is_signed_by_mail_provision_or_served_unsigned_if_it_fails(): void
    {
        config(['mail_provision.command' => '/usr/bin/sudo -n /usr/local/sbin/mail-provision']);
        Process::fake([
            '*sign-profile*' => Process::sequence()->push(Process::result('FIRMADO'))->push(Process::result('', 'fallo', 2)),
        ]);

        foreach (['FIRMADO', '<?xml'] as $expected) {
            $html = $this->actingAs($this->user)->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'iphone'])->getContent();
            preg_match('#href="([^"]*/cuenta/perfil/[A-Za-z0-9]{40})"#', $html, $m);
            $this->assertStringStartsWith($expected, $this->get($m[1])->assertOk()->getContent());
        }

        Process::assertRan(fn ($process) => $process->command === ['/usr/bin/sudo', '-n', '/usr/local/sbin/mail-provision', 'sign-profile']
            && str_contains($process->input, 'com.apple.mail.managed'));
    }

    public function test_profile_is_only_for_its_owner_and_expires(): void
    {
        $html = $this->actingAs($this->user)->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'mac'])->getContent();
        preg_match('#href="([^"]*/cuenta/perfil/[A-Za-z0-9]{40})"#', $html, $m);

        $this->actingAs(User::factory()->create())->get($m[1])->assertNotFound();

        $html = $this->actingAs($this->user)->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'iphone'])->getContent();
        preg_match('#href="([^"]*/cuenta/perfil/[A-Za-z0-9]{40})"#', $html, $m);
        $this->travel(11)->minutes();
        $this->get($m[1])->assertNotFound();
    }

    public function test_cannot_set_up_someone_elses_mailbox(): void
    {
        $this->actingAs(User::factory()->create())->post("/cuenta/configurar/{$this->mailbox->id}", ['client' => 'android'])->assertNotFound();
        $this->assertSame(0, AppPassword::count());
    }

    public function test_autoconfig_for_thunderbird(): void
    {
        $this->get('/mail/config-v1.1.xml?emailaddress=ana%40unagrandeylibre.es')
            ->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<domain>unagrandeylibre.es</domain>', false)
            ->assertSee('<hostname>mail.unagrandeylibre.es</hostname>', false)
            ->assertSee('<port>993</port>', false)
            ->assertSee('<username>%EMAILADDRESS%</username>', false)
            ->assertCookieMissing(config('session.cookie'));                 // sin sesión
        $this->get('/.well-known/autoconfig/mail/config-v1.1.xml')->assertOk();
    }

    public function test_autodiscover_for_outlook(): void
    {
        $request = '<?xml version="1.0"?><Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006"><Request><EMailAddress>Ana@unagrandeylibre.es</EMailAddress><AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a</AcceptableResponseSchema></Request></Autodiscover>';

        $this->call('POST', '/autodiscover/autodiscover.xml', [], [], [], ['CONTENT_TYPE' => 'text/xml'], $request)
            ->assertOk()
            ->assertSee('<Type>IMAP</Type><Server>mail.unagrandeylibre.es</Server><Port>993</Port>', false)
            ->assertSee('<LoginName>ana@unagrandeylibre.es</LoginName>', false);
        $this->call('POST', '/Autodiscover/Autodiscover.xml', [], [], [], ['CONTENT_TYPE' => 'text/xml'], $request)->assertOk();
    }

    public function test_autodiscover_ignores_xxe_payloads(): void
    {
        $evil = '<?xml version="1.0"?><!DOCTYPE r [<!ENTITY x SYSTEM "file:///etc/passwd">]><Autodiscover><Request><EMailAddress>&x;</EMailAddress></Request></Autodiscover>';

        $this->call('POST', '/autodiscover/autodiscover.xml', [], [], [], ['CONTENT_TYPE' => 'text/xml'], $evil)
            ->assertOk()->assertDontSee('root:', false)->assertDontSee('<LoginName>', false);
    }

    public function test_public_help_pages(): void
    {
        $this->get('/ayuda/configurar')->assertOk()->assertSee('Android (Gmail)')->assertSee('Thunderbird');
        $this->get('/ayuda/configurar/android')->assertOk()->assertSee('Personal (IMAP)')->assertSee('mail.unagrandeylibre.es');
        $this->get('/ayuda/configurar/iphone')->assertOk()->assertSee('Configura un dispositivo');
        $this->get('/ayuda/configurar/inventada')->assertNotFound();
    }
}
