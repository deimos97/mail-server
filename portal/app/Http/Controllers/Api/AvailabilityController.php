<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\NameAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/availability?local=pepe[&domain=unagrandeylibre.es]
 *
 * Solo se puede preguntar por dominios que se ofrecen en el alta (public_signup).
 */
class AvailabilityController extends Controller
{
    public function __invoke(Request $request, NameAvailability $availability): JsonResponse
    {
        $data = $request->validate([
            'local' => ['required', 'string', 'max:128'],
            'domain' => ['nullable', 'string', 'max:255'],
        ]);

        $domain = Domain::signup()
            ->when($data['domain'] ?? null, fn ($q, $name) => $q->where('name', mb_strtolower($name)))
            ->first();

        if (! $domain) {
            return response()->json(['message' => 'Dominio no disponible.'], 422);
        }

        return response()
            ->json($availability->check($data['local'], $domain)->toArray())
            ->header('Cache-Control', 'no-store');
    }
}
