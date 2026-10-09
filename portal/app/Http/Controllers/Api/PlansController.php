<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NamePriceTier;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

/** GET /api/plans: planes visibles ahora, con precio (IVA incluido). Para WebMCP y otros clientes. */
class PlansController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $plans = Plan::visible()->ordered()->with('offers')->get()->map(fn (Plan $plan) => [
            'id' => $plan->slug,
            'nombre' => $plan->name,
            'descripcion' => $plan->description,
            'caracteristicas' => $plan->features ?? [],
            'gratis' => $plan->is_free,
            'precio_eur' => round($plan->price_cents / 100, 2),
            'precio_ahora_eur' => round($plan->effectivePriceCents() / 100, 2),
            'oferta' => $plan->currentOffer()?->label,
            'periodo' => $plan->interval === 'year' ? 'año' : 'mes',
            'espacio_gb' => round($plan->quota_bytes / 1024 ** 3, 1),
            'envios_por_hora' => $plan->send_limit_per_hour,
        ]);

        return response()->json([
            'iva_incluido' => true,
            'planes' => $plans,
            'nombres_cortos' => NamePriceTier::where('is_active', true)->orderBy('min_length')->get()->map(fn ($t) => [
                'longitud' => "{$t->min_length}-{$t->max_length}", 'suplemento_mensual_eur' => round($t->price_cents / 100, 2), 'solo_planes_de_pago' => true,
            ]),
            'alta' => url('/alta'),
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
