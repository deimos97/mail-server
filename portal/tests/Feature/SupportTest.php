<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Mail\Support\StaffNotice;
use App\Mail\Support\TicketReceived;
use App\Mail\Support\TicketReply;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    private bool $turnstilePasses = true;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        Http::fake(['challenges.cloudflare.com/*' => fn () => Http::response(['success' => $this->turnstilePasses])]);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + ['email' => 'lucia@gmail.com', 'subject' => 'No me llegan correos', 'body' => "Desde ayer\nno recibo nada.", 'cf-turnstile-response' => 'ok'];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->app_authentication_secret = AppAuthentication::make()->generateSecret();
        $user->save();

        return $user;
    }

    public function test_a_guest_opens_a_ticket_with_images(): void
    {
        $this->get('/soporte')->assertOk()->assertSee('Tu email')->assertSee('transfer.tibletech.com');

        $this->post('/soporte', $this->form(['images' => [
            UploadedFile::fake()->image('captura.png', 400, 300),
            UploadedFile::fake()->image('otra.jpg'),
        ]]))->assertRedirect('/soporte/enviado');

        $ticket = SupportTicket::sole();
        $this->assertSame('lucia@gmail.com', $ticket->email);
        $this->assertNull($ticket->user_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('[#0001] No me llegan correos', $ticket->emailSubject());

        $message = $ticket->messages()->sole();
        $this->assertSame("Desde ayer\nno recibo nada.", $message->body);
        $this->assertCount(2, $message->attachments);
        Storage::disk('local')->assertExists($message->attachments[0]->path);

        Mail::assertQueued(TicketReceived::class, fn ($mail) => $mail->hasTo('lucia@gmail.com') && $mail->envelope()->subject === '[#0001] No me llegan correos');
        Mail::assertQueued(StaffNotice::class, fn ($mail) => $mail->hasTo(config('support.notify')) && $mail->isNew);

        $this->get('/soporte/enviado')->assertOk()->assertSee('#0001');
    }

    public function test_customer_emails_go_through_the_support_mailer_and_staff_notices_do_not(): void
    {
        // En producción, soporte@ solo puede enviar autenticado como soporte@ (reject_sender_login_mismatch)
        config(['support.mailer' => 'support']);
        $ticket = app(Support::class)->open(null, 'lucia@gmail.com', 'Duda', 'Hola');
        app(Support::class)->reply($ticket, $this->admin(), 'Respuesta');

        Mail::assertQueued(TicketReceived::class, fn ($mail) => $mail->mailer === 'support');
        Mail::assertQueued(TicketReply::class, fn ($mail) => $mail->mailer === 'support');
        Mail::assertQueued(StaffNotice::class, fn ($mail) => $mail->mailer !== 'support');
    }

    public function test_the_sent_page_needs_the_session(): void
    {
        $this->get('/soporte/enviado')->assertRedirect('/soporte');
    }

    public function test_logged_in_users_get_the_reply_at_their_recovery_email(): void
    {
        $user = User::factory()->create(['email' => 'ana@gmail.com']);

        $this->actingAs($user)->get('/soporte')->assertOk()->assertSee('ana@gmail.com')->assertDontSee('Tu email');
        $this->actingAs($user)->post('/soporte', $this->form(['email' => null]))->assertRedirect('/soporte/enviado');

        $ticket = SupportTicket::sole();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('ana@gmail.com', $ticket->email);
    }

    public function test_guests_can_be_turned_off_from_the_admin(): void
    {
        app(Support::class)->setGuestsAllowed(false);

        $this->get('/soporte')->assertOk()->assertSee('Para escribirnos, entra en tu cuenta')->assertDontSee('Tu email');
        $this->post('/soporte', $this->form())->assertForbidden();

        $this->actingAs(User::factory()->create())->post('/soporte', $this->form(['email' => null]))->assertRedirect('/soporte/enviado');
    }

    public function test_only_images_and_within_the_limits(): void
    {
        $this->post('/soporte', $this->form(['images' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]]))
            ->assertSessionHasErrors('images.0');
        $this->post('/soporte', $this->form(['images' => array_map(fn ($i) => UploadedFile::fake()->image("c{$i}.png"), range(1, 4))]))
            ->assertSessionHasErrors('images');
        $this->post('/soporte', $this->form(['images' => [
            UploadedFile::fake()->image('a.png')->size(5000),
            UploadedFile::fake()->image('b.png')->size(4000),
        ]]))->assertSessionHasErrors('images');

        $this->assertSame(0, SupportTicket::count());
    }

    public function test_turnstile_is_required(): void
    {
        $this->turnstilePasses = false;

        $this->post('/soporte', $this->form())->assertSessionHasErrors('turnstile');
        $this->assertSame(0, SupportTicket::count());
    }

    public function test_replying_from_the_admin_emails_the_customer_and_can_close(): void
    {
        $admin = $this->admin();
        $ticket = app(Support::class)->open(null, 'lucia@gmail.com', 'Duda', 'Hola');

        Livewire::actingAs($admin)->test(ViewSupportTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertSee('Hola')
            ->callAction('reply', ['body' => 'Ya está arreglado.', 'close' => true])
            ->assertHasNoActionErrors();

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);
        $this->assertSame('staff', $ticket->messages->last()->author);
        Mail::assertQueued(TicketReply::class, fn ($mail) => $mail->hasTo('lucia@gmail.com') && $mail->envelope()->subject === '[#0001] Duda');

        Livewire::actingAs($admin)->test(ViewSupportTicket::class, ['record' => $ticket->getRouteKey()])->callAction('reopen');
        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_admin_list_and_guest_toggle(): void
    {
        $admin = $this->admin();
        app(Support::class)->open(null, 'lucia@gmail.com', 'Duda abierta', 'Hola');

        Livewire::actingAs($admin)->test(ListSupportTickets::class)
            ->assertSee('Duda abierta')
            ->callAction('guests');

        $this->assertFalse(app(Support::class)->guestsAllowed());
    }

    public function test_attachments_are_only_for_admins(): void
    {
        $ticket = app(Support::class)->open(null, 'lucia@gmail.com', 'Duda', 'Hola', [UploadedFile::fake()->image('c.png')]);
        $attachment = $ticket->messages()->sole()->attachments()->sole();
        $url = "/admin/soporte/adjuntos/{$attachment->id}";

        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->admin())->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_help_buttons(): void
    {
        $this->get('/')->assertSee('¿Necesitas ayuda?')->assertSee('Ayuda y contacto');
        $this->get('/legal/privacidad')->assertSee(route('support'));
    }
}
