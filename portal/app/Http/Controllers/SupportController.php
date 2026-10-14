<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\Support;
use App\Services\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/** Ayuda y contacto (Fase 7): el formulario que abre un ticket. Ver App\Services\Support. */
class SupportController extends Controller
{
    public function __construct(private Support $support) {}

    public function create(Request $request): View
    {
        return view('support.create', [
            'user' => $request->user(),
            'guestsAllowed' => $this->support->guestsAllowed(),
        ]);
    }

    public function store(Request $request, Turnstile $turnstile): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user && ! $this->support->guestsAllowed(), 403);

        $limits = config('support.attachments');
        $data = $request->validate([
            'email' => $user ? ['prohibited'] : ['required', 'string', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:'.$limits['max_files'], function ($attribute, $value, $fail) use ($limits) {
                $total = collect($value)->sum(fn ($file) => $file instanceof UploadedFile ? $file->getSize() : 0);
                if ($total > $limits['max_total_kb'] * 1024) {
                    $fail('Las imágenes ocupan más de '.($limits['max_total_kb'] / 1024).' MB en total.');
                }
            }],
            'images.*' => ['file', 'mimes:'.implode(',', $limits['mimes']), 'max:'.$limits['max_total_kb']],
        ], [
            'email.required' => 'Escribe tu email para que podamos responderte.',
            'email.email' => 'Ese email no parece válido.',
            'subject.required' => 'Ponle un asunto.',
            'body.required' => 'Cuéntanos qué pasa.',
            'body.max' => 'El mensaje es demasiado largo (máximo 5.000 caracteres).',
            'images.max' => 'Puedes adjuntar como mucho '.$limits['max_files'].' imágenes.',
            'images.*.mimes' => 'Solo se pueden adjuntar imágenes (JPG, PNG, WebP, GIF o HEIC).',
            'images.*.max' => 'Cada imagen puede ocupar como mucho '.($limits['max_total_kb'] / 1024).' MB.',
            'images.*.file' => 'No hemos podido subir una de las imágenes.',
        ]);

        if (! $turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['turnstile' => 'No hemos podido comprobar que no eres un robot. Inténtalo de nuevo.']);
        }

        $ticket = $this->support->open(
            $user,
            $user ? $user->email : $data['email'],
            $data['subject'],
            $data['body'],
            $request->file('images', []),
            $request->ip(),
        );

        $request->session()->put('support.sent', $ticket->id);

        return redirect()->route('support.sent');
    }

    /** Confirmación: solo para quien acaba de enviarlo (el id va en la sesión, no en la URL). */
    public function sent(Request $request): View|RedirectResponse
    {
        $ticket = SupportTicket::find($request->session()->get('support.sent'));

        return $ticket ? view('support.sent', ['ticket' => $ticket]) : redirect()->route('support');
    }
}
