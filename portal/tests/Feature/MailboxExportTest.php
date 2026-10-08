<?php

namespace Tests\Feature;

use App\Mail\MailboxExportReady;
use App\Models\Mailbox;
use App\Models\MailboxExport;
use App\Models\User;
use App\Services\MailboxExports;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class MailboxExportTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    private Mailbox $mailbox;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
        Mail::fake();
        $this->dir = sys_get_temp_dir().'/ugl-exports-'.uniqid();
        config(['mail_export.dir' => $this->dir]);

        $this->user = User::factory()->create(['email' => 'ana@gmail.com']);
        $this->mailbox = Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => 1,
            'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x', 'can_send' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    /** Lo que haría root (mail-provision process-exports). */
    private function rootPrepares(MailboxExport $export, bool $ok = true): void
    {
        $req = "{$this->dir}/requests/{$export->id}.req";
        $this->assertSame("ana@unagrandeylibre.es\n", file_get_contents($req));
        unlink($req);
        File::ensureDirectoryExists("{$this->dir}/files");
        file_put_contents("{$this->dir}/files/{$export->id}.".($ok ? 'zip' : 'failed'), $ok ? 'PK-zip' : '');
    }

    public function test_full_export_flow(): void
    {
        $this->actingAs($this->user)->post("/cuenta/copia/{$this->mailbox->id}")->assertRedirect('/cuenta');
        $export = MailboxExport::firstOrFail();
        $this->get('/cuenta')->assertSee('La estamos preparando');

        // Otra petición mientras tanto: no
        $this->post("/cuenta/copia/{$this->mailbox->id}")->assertSessionHas('status', 'Ya tienes una copia de este buzón en marcha o lista para descargar.');
        $this->assertSame(1, MailboxExport::count());

        $this->rootPrepares($export);
        app(MailboxExports::class)->collect();

        $export->refresh();
        $this->assertSame('ready', $export->status);
        Mail::assertQueued(MailboxExportReady::class, fn ($mail) => $mail->hasTo('ana@gmail.com'));
        $this->get('/cuenta')->assertSee('Descargar la copia (.zip)');

        $response = $this->get("/cuenta/copia/{$export->id}/descargar")->assertOk();
        $this->assertStringContainsString('correo-ana-', $response->headers->get('Content-Disposition'));

        // Nadie más la descarga
        $this->actingAs(User::factory()->create())->get("/cuenta/copia/{$export->id}/descargar")->assertNotFound();

        // Caduca: se borra el fichero
        $this->travel(49)->hours();
        app(MailboxExports::class)->collect();
        $this->assertSame('expired', $export->fresh()->status);
        $this->assertFileDoesNotExist("{$this->dir}/files/{$export->id}.zip");
        $this->actingAs($this->user)->get("/cuenta/copia/{$export->id}/descargar")->assertNotFound();
    }

    public function test_failed_export_can_be_retried(): void
    {
        $this->actingAs($this->user)->post("/cuenta/copia/{$this->mailbox->id}");
        $this->rootPrepares(MailboxExport::firstOrFail(), ok: false);
        app(MailboxExports::class)->collect();

        $this->get('/cuenta')->assertSee('no se pudo preparar');
        $this->post("/cuenta/copia/{$this->mailbox->id}")->assertRedirect('/cuenta');
        $this->assertSame(2, MailboxExport::count());
    }

    public function test_cannot_export_someone_elses_mailbox(): void
    {
        $this->actingAs(User::factory()->create())->post("/cuenta/copia/{$this->mailbox->id}")->assertNotFound();
        $this->assertSame(0, MailboxExport::count());
    }
}
