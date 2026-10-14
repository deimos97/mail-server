<?php

namespace Tests\Feature;

use App\Mail\Support\StaffNotice;
use App\Mail\Support\UnmatchedNotice;
use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support;
use App\Services\SupportInbox;
use App\Support\EmailReplyText;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SupportInboxTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private SupportTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        Mail::fake();
        Storage::fake('local');
        $this->ticket = app(Support::class)->open(null, 'lucia@gmail.com', 'No me llegan', 'Hola');
    }

    private function email(array $overrides = []): array
    {
        return $overrides + [
            'message_id' => '<abc@mail.gmail.com>',
            'from' => 'Lucia@Gmail.com',
            'subject' => 'Re: [#0001] No me llegan',
            'text' => "Ya funciona, gracias.\n\nEl mar, 14 oct 2026 a las 13:00, Javier · Una Grande y Libre (<soporte@unagrandeylibre.es>)\nescribió:\n> Prueba ahora.",
            'html' => null,
            'auto_submitted' => null,
            'images' => [],
        ];
    }

    public function test_a_reply_is_added_to_its_ticket_and_reopens_it(): void
    {
        app(Support::class)->close($this->ticket);

        $message = app(SupportInbox::class)->handle($this->email());

        $this->assertNotNull($message);
        $this->assertSame('Ya funciona, gracias.', $message->body);
        $this->assertSame('email', $message->source);
        $this->assertSame('open', $this->ticket->fresh()->status);
        Mail::assertQueued(StaffNotice::class, fn ($mail) => ! $mail->isNew);
    }

    public function test_the_same_email_is_not_added_twice(): void
    {
        $inbox = app(SupportInbox::class);

        $this->assertNotNull($inbox->handle($this->email()));
        $this->assertNull($inbox->handle($this->email()));
        $this->assertSame(2, $this->ticket->messages()->count());
    }

    public function test_emails_that_are_not_ticket_replies_stay_in_the_mailbox(): void
    {
        $inbox = app(SupportInbox::class);

        $this->assertNull($inbox->handle($this->email(['subject' => 'Hola, una pregunta'])));
        $this->assertNull($inbox->handle($this->email(['subject' => 'Re: [#0099] No existe'])));
        $this->assertNull($inbox->handle($this->email(['from' => 'otro@gmail.com'])));
        $this->assertNull($inbox->handle($this->email(['auto_submitted' => 'auto-replied'])));
        $this->assertSame(1, $this->ticket->messages()->count());
    }

    public function test_customers_with_an_account_can_reply_from_their_mailbox(): void
    {
        $this->seed(DemoPlansSeeder::class);
        $user = User::factory()->create(['email' => 'ana@gmail.com']);
        Mailbox::create(['domain_id' => 1, 'user_id' => $user->id, 'plan_id' => 1, 'local_part' => 'ana', 'email' => 'ana@unagrandeylibre.es', 'password' => 'x']);
        $ticket = app(Support::class)->open($user, 'ana@gmail.com', 'Duda', 'Hola');

        $message = app(SupportInbox::class)->handle($this->email(['from' => 'ana@unagrandeylibre.es', 'subject' => "Re: {$ticket->emailSubject()}", 'message_id' => '<x@y>']));

        $this->assertSame($ticket->id, $message->support_ticket_id);
    }

    public function test_only_images_within_the_limits_are_kept(): void
    {
        $png = ['content' => str_repeat('x', 1000), 'name' => 'captura.png', 'mime' => 'image/png'];
        $message = app(SupportInbox::class)->handle($this->email(['images' => [
            $png, ['content' => 'x', 'name' => 'factura.pdf', 'mime' => 'application/pdf'], $png, $png, $png,
        ]]));

        $this->assertCount(3, $message->attachments);
    }

    public function test_html_only_replies(): void
    {
        $message = app(SupportInbox::class)->handle($this->email(['text' => null, 'html' => '<div>Perfecto,<br>gracias</div><blockquote>On Tue, Javier wrote:</blockquote>']));

        $this->assertSame("Perfecto,\ngracias", $message->body);
    }

    public function test_quoted_text_is_removed(): void
    {
        $this->assertSame('Gracias', EmailReplyText::clean("Gracias\n\nOn Tue, Oct 14, 2026 at 1:00 PM Javier <soporte@x.es> wrote:\n> hola"));
        $this->assertSame('Vale', EmailReplyText::clean("Vale\r\n\r\n-----Mensaje original-----\r\nDe: Javier"));
        $this->assertSame('Ok', EmailReplyText::clean("Ok\n\n________________________________\nDe: Javier <soporte@x.es>\nEnviado: martes"));
        $this->assertSame('Ok', EmailReplyText::clean("Ok\n\nDe: Javier <soporte@x.es>\nEnviado: martes, 14 de octubre"));
        $this->assertSame("Hola\nadiós", EmailReplyText::clean("Hola\nadiós\n-- \nLucía, Madrid"));
        $this->assertSame('> solo cita', EmailReplyText::clean('> solo cita'));   // si no queda nada, el texto entero
    }

    public function test_unmatched_emails_are_forwarded_once_with_reply_to_the_sender(): void
    {
        app(SupportInbox::class)->forwardUnmatched($this->email(['subject' => 'Pregunta suelta', 'from' => 'pepe@gmail.com']));

        Mail::assertQueued(UnmatchedNotice::class, fn ($mail) => $mail->hasTo(config('support.notify'))
            && $mail->envelope()->replyTo[0]->address === 'pepe@gmail.com'
            && $mail->envelope()->subject === 'En soporte@: Pregunta suelta');
    }

    public function test_without_credentials_nothing_is_read(): void
    {
        config(['support.imap.username' => null]);

        $this->assertSame(0, app(SupportInbox::class)->fetch());
        $this->artisan('support:fetch')->assertSuccessful();
    }
}
