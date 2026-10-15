<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Mailbox;
use App\Models\Plan;
use App\Services\Experiments;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(Request $request, Experiments $experiments): View
    {
        // Pruebas A/B: textos de la variante de este visitante (y ?variante=clave:b para probarlas)
        $experiments->forceFromQuery($request->query('variante'));
        $experiments->applyLandingOverrides();

        $models = Plan::visible()->ordered()->with('offers')->get();

        $plans = $models->map(fn (Plan $plan) => [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'description' => $plan->description,
            'features' => $plan->features ?? [],
            'is_free' => $plan->is_free,
            'is_highlighted' => $plan->is_highlighted,
            'interval' => $plan->interval,
            'price_cents' => $plan->price_cents,
            'effective_price_cents' => $plan->effectivePriceCents(),
            'offer' => $plan->currentOffer(),
        ]);

        // "Ya somos N": buzones activos de clientes (sin los internos), solo a partir de un mínimo
        $count = Cache::remember('landing.mailbox_count', 600,
            fn () => Mailbox::where('status', 'active')->where('tier', '!=', 'system')->count());

        return view('landing', [
            'domains' => Domain::signup()->pluck('name')->all(),
            'mailboxCount' => $count >= config('landing.counter.min') ? $count : null,
            'plans' => $plans,
            'structuredData' => StructuredData::landing($models),
        ]);
    }
}
