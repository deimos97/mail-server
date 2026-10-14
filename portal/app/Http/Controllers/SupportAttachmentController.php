<?php

namespace App\Http\Controllers;

use App\Models\SupportAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Imagen de un ticket. Solo dentro del admin (ruta del panel: login, admin y 2FA de Filament). */
class SupportAttachmentController extends Controller
{
    public function __invoke(SupportAttachment $attachment): StreamedResponse
    {
        // inline solo para imágenes; nosniff para que nadie cuele HTML con extensión de imagen
        return Storage::disk('local')->response($attachment->path, $attachment->name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ], str_starts_with($attachment->mime, 'image/') ? 'inline' : 'attachment');
    }
}
