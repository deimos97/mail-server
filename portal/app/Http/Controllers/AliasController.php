<?php

namespace App\Http\Controllers;

use App\Models\Alias;
use App\Models\Mailbox;
use App\Services\Aliases;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Alias de los buzones del cliente, en Mi cuenta (ver App\Services\Aliases). */
class AliasController extends Controller
{
    public function __construct(private Aliases $aliases) {}

    public function store(Request $request, int $mailbox): RedirectResponse
    {
        $mailbox = Mailbox::where('user_id', $request->user()->id)->where('status', 'active')->with('plan', 'domain')->findOrFail($mailbox);
        $data = $request->validate(['local_part' => ['required', 'string', 'max:64']]);

        try {
            $alias = $this->aliases->create($mailbox, $data['local_part']);
        } catch (RuntimeException $e) {
            return back()->withErrors(["alias_{$mailbox->id}" => $e->getMessage()])->withInput();
        }

        return back()->with('status', "Listo: lo que llegue a {$alias->source} te llegará a {$mailbox->email}.");
    }

    public function destroy(Request $request, int $alias): RedirectResponse
    {
        $alias = Alias::whereNull('deleted_at')->whereIn('mailbox_id', Mailbox::where('user_id', $request->user()->id)->pluck('id'))->findOrFail($alias);
        $this->aliases->delete($alias);

        return back()->with('status', "Hemos borrado {$alias->source}: ya no recibe correo.");
    }
}
