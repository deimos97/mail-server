<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Services\AppPasswords;
use App\Services\MailProvision;
use App\Support\MailClients\AppleProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Configura un dispositivo": elige la app, se crea su contraseña y se muestran sus pasos.
 * En iPhone/iPad/Mac, además, un perfil que la lleva dentro. Como la contraseña no se guarda en
 * claro, el perfil se guarda cifrado 10 minutos y se borra al descargarlo (una sola vez).
 * Al descargarlo se firma con el certificado de la web (`mail-provision sign-profile`); si la firma
 * falla, sale sin firmar (iOS lo instala igual, como "No verificado").
 */
class DeviceSetupController extends Controller
{
    private const PROFILE_TTL = 600;

    public function choose(Request $request, int $mailbox): View
    {
        return view('setup.choose', ['mailbox' => $this->mailbox($request, $mailbox), 'clients' => config('mail_clients.clients')]);
    }

    public function store(Request $request, int $mailbox, AppPasswords $appPasswords): View
    {
        $clients = config('mail_clients.clients');
        $data = $request->validate([
            'client' => ['required', 'string', 'in:'.implode(',', array_keys($clients))],
            'name' => ['nullable', 'string', 'max:64'],
        ]);
        $mailbox = $this->mailbox($request, $mailbox);
        $client = $data['client'];
        $name = trim((string) ($data['name'] ?? '')) ?: $clients[$client]['device'];

        try {
            [$device, $password] = $appPasswords->create($mailbox, $name);
        } catch (RuntimeException $e) {
            return view('setup.choose', ['mailbox' => $mailbox, 'clients' => $clients, 'error' => $e->getMessage()]);
        }

        $profileUrl = null;
        if ($clients[$client]['profile']) {
            $token = Str::random(40);
            Cache::put("apple-profile:{$token}", Crypt::encryptString(json_encode([
                'user_id' => $request->user()->id,
                'filename' => 'correo-'.$mailbox->local_part.'.mobileconfig',
                'xml' => AppleProfile::make($mailbox, $device, $password),
            ])), self::PROFILE_TTL);
            $profileUrl = route('setup.profile', $token);
        }

        return view('setup.result', [
            'mailbox' => $mailbox, 'client' => $client, 'label' => $clients[$client]['label'],
            'device' => $device, 'password' => $password, 'profileUrl' => $profileUrl,
        ]);
    }

    /** Descarga del perfil de Apple: una sola vez, solo su dueño, como mucho 10 minutos después. */
    public function profile(Request $request, string $token, MailProvision $provision): Response
    {
        $stored = Cache::pull("apple-profile:{$token}");
        abort_unless($stored, 404);
        $profile = json_decode(Crypt::decryptString($stored), true);
        abort_unless($profile['user_id'] === $request->user()->id, 404);

        $body = $profile['xml'];
        if ($provision->enabled()) {
            try {
                $body = $provision->signProfile($body);
            } catch (RuntimeException $e) {
                Log::warning($e->getMessage());
            }
        }

        return response($body, 200, [
            'Content-Type' => AppleProfile::CONTENT_TYPE,
            'Content-Disposition' => 'attachment; filename="'.$profile['filename'].'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function mailbox(Request $request, int $id): Mailbox
    {
        return Mailbox::with('domain')->where('user_id', $request->user()->id)->where('status', 'active')->findOrFail($id);
    }
}
