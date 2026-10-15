<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Models\User;
use App\Services\MailboxLifecycle;
use App\Services\NameAvailability;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AliasesTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([DemoPlansSeeder::class, NameRulesSeeder::class, \Database\Seeders\ReservedNamesSeeder::class]);
        $this->user = User::factory()->create(['email' => 'ana@gmail.com']);
    }

    private function mailbox(string $plan, string $local = 'anabel'): Mailbox
    {
        $plan = Plan::where('slug', $plan)->first();

        return Mailbox::create(['domain_id' => 1, 'user_id' => $this->user->id, 'plan_id' => $plan->id, 'tier' => $plan->tier,
            'quota_bytes' => $plan->quota_bytes, 'local_part' => $local, 'email' => "$local@unagrandeylibre.es", 'password' => 'x', 'status' => 'active']);
    }

    public function test_paid_mailboxes_add_aliases_up_to_their_plan(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->actingAs($this->user)->get('/cuenta')->assertSee('Alias')->assertSee('0 de 3')->assertSee('Nuevo alias');

        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'Compras.Ana'])->assertRedirect()->assertSessionHas('status');

        $alias = Alias::sole();
        $this->assertSame('compras.ana@unagrandeylibre.es', $alias->source);
        $this->assertSame('anabel@unagrandeylibre.es', $alias->destination);
        $this->assertSame($mailbox->id, $alias->mailbox_id);
        $this->assertTrue($alias->active);
        $this->get('/cuenta')->assertSee('compras.ana@unagrandeylibre.es')->assertSee('1 de 3');

        // Ya no está libre ni para otro buzón ni para otro alias
        $this->assertSame('taken', app(NameAvailability::class)->check('compras.ana', $mailbox->domain)->status);

        Plan::where('slug', 'basico')->update(['max_aliases' => 1]);
        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'facturas.ana'])
            ->assertSessionHasErrors(["alias_{$mailbox->id}" => 'Ya tienes los 1 alias de tu plan. Borra alguno o pasa a un plan con más.']);
    }

    public function test_the_same_rules_as_names_and_no_short_names(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->mailbox('basico', 'pepito');
        $this->actingAs($this->user);

        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'ana'])->assertSessionHasErrors("alias_{$mailbox->id}");   // corto
        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'pepito'])->assertSessionHasErrors("alias_{$mailbox->id}");   // cogido
        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'postmaster'])->assertSessionHasErrors("alias_{$mailbox->id}");   // reservado
        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => '.malo.'])->assertSessionHasErrors("alias_{$mailbox->id}");   // formato
        $this->assertSame(0, Alias::count());
    }

    public function test_free_mailboxes_get_the_upsell(): void
    {
        $mailbox = $this->mailbox('gratis');

        $this->actingAs($this->user)->get('/cuenta')->assertSee('Van con los planes de pago')->assertDontSee('Nuevo alias')
            ->assertSee('compras@unagrandeylibre.es')->assertSee('anabel+compras@unagrandeylibre.es')->assertDontSee('{{', false);   // «@{{» en Blade escribe las llaves tal cual
        $this->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'compras.ana'])
            ->assertSessionHasErrors(["alias_{$mailbox->id}" => 'Los alias van con los planes de pago.']);
    }

    public function test_deleting_keeps_the_name_in_quarantine(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->actingAs($this->user)->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'compras.ana']);
        $alias = Alias::sole();

        $this->post("/cuenta/alias/{$alias->id}/borrar")->assertRedirect()->assertSessionHas('status');

        $alias->refresh();
        $this->assertFalse($alias->active);
        $this->assertNotNull($alias->deleted_at);
        $this->assertTrue($alias->release_at->isAfter(now()->addDays(89)));
        $this->assertSame('taken', app(NameAvailability::class)->check('compras.ana', $mailbox->domain)->status);
        $this->get('/cuenta')->assertSee('0 de 3');
    }

    public function test_only_your_own(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->actingAs($this->user)->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'compras.ana']);

        $other = User::factory()->create();
        $this->actingAs($other)->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'robo.ana'])->assertNotFound();
        $this->post('/cuenta/alias/'.Alias::sole()->id.'/borrar')->assertNotFound();
        $this->assertTrue(Alias::sole()->active);
    }

    public function test_aliases_go_with_their_mailbox(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->actingAs($this->user)->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => 'compras.ana']);

        app(MailboxLifecycle::class)->delete($mailbox, now()->addDays(60));

        $alias = Alias::sole();
        $this->assertFalse($alias->active);
        $this->assertSame(now()->addDays(60)->toDateString(), $alias->release_at->toDateString());
    }

    public function test_internal_mailboxes_have_their_own_limit(): void
    {
        $mailbox = $this->mailbox('basico');
        $mailbox->update(['tier' => 'system', 'plan_id' => null]);

        $this->actingAs($this->user)->get('/cuenta')->assertSee('0 de 50');
    }

    private function addAliases(Mailbox $mailbox, array $names): void
    {
        foreach ($names as $i => $name) {
            $this->travel($i)->minutes();
            $this->actingAs($this->user)->post("/cuenta/buzones/{$mailbox->id}/alias", ['local_part' => $name])->assertSessionHasNoErrors();
        }
        $this->travelBack();
    }

    public function test_downgrading_removes_the_newest_aliases_that_do_not_fit(): void
    {
        $mailbox = $this->mailbox('pro');
        $this->addAliases($mailbox, ['uno.ana', 'dos.ana', 'tres.ana', 'cuatro.ana']);

        $stripe = \Tests\Support\FakeStripe::install();   // sin Stripe, los planes de pago salen como «Muy pronto»
        app(\App\Services\StripeCatalog::class)->syncAll();
        $this->actingAs($this->user)->get("/cuenta/plan/{$mailbox->id}")
            ->assertSee('Perderás tu alias más nuevo: este plan tiene 3.')->assertSee('Perderás tus 4 alias: este plan no tiene.');
        \Tests\Support\FakeStripe::uninstall();

        $gone = app(\App\Services\MailboxBilling::class)->applyPlan($mailbox, Plan::where('slug', 'basico')->first());

        $this->assertSame(['cuatro.ana@unagrandeylibre.es'], $gone);
        $this->assertSame(['dos.ana@unagrandeylibre.es', 'tres.ana@unagrandeylibre.es', 'uno.ana@unagrandeylibre.es'],
            Alias::where('active', true)->orderBy('source')->pluck('source')->all());
        $this->assertTrue(Alias::where('source', 'cuatro.ana@unagrandeylibre.es')->sole()->release_at->isAfter(now()->addDays(89)));   // cuarentena
    }

    public function test_ending_on_free_removes_all_aliases_and_says_so(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $mailbox = $this->mailbox('basico');
        $this->addAliases($mailbox, ['uno.ana', 'dos.ana']);

        app(\App\Services\MailboxBilling::class)->ended(['metadata' => ['type' => "mailbox:{$mailbox->id}"]]);

        $this->assertSame('free', $mailbox->fresh()->tier);
        $this->assertSame(0, Alias::where('active', true)->count());
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\LifecycleNotice::class, function ($mail) {
            $html = $mail->render();

            return $mail->type === 'downgraded' && str_contains($html, 'dos.ana@unagrandeylibre.es, uno.ana@unagrandeylibre.es')
                && str_contains($html, 'han dejado de funcionar');
        });
    }

    public function test_upgrading_keeps_every_alias(): void
    {
        $mailbox = $this->mailbox('basico');
        $this->addAliases($mailbox, ['uno.ana', 'dos.ana']);

        $this->assertSame([], app(\App\Services\MailboxBilling::class)->applyPlan($mailbox, Plan::where('slug', 'pro')->first()));
        $this->assertSame(2, Alias::where('active', true)->count());
    }
}
