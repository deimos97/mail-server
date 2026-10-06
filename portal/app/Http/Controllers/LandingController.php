<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Plan;
use App\Support\StructuredData;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
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

        return view('landing', [
            'domains' => Domain::signup()->pluck('name')->all(),
            'plans' => $plans,
            'structuredData' => StructuredData::landing($models),
        ]);
    }
}
