<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Guías públicas para configurar el correo en cada app (también sirven para el SEO). */
class HelpController extends Controller
{
    public function index(): View
    {
        return view('help.setup', ['clients' => config('mail_clients.clients')]);
    }

    public function show(string $client): View
    {
        $clients = config('mail_clients.clients');
        abort_unless(isset($clients[$client]), 404);

        return view('help.setup-client', ['client' => $client, 'label' => $clients[$client]['label']]);
    }
}
