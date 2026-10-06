<?php

namespace App\Support;

use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Datos estructurados (schema.org, JSON-LD) de la landing: los leen Google y los asistentes de IA.
 * Los precios salen de la BD (precio efectivo, IVA incluido), así que nunca se quedan desfasados.
 */
class StructuredData
{
    /** @param  Collection<int, Plan>  $plans */
    public static function landing(Collection $plans): array
    {
        $home = route('home');
        $org = $home.'#organizacion';

        $products = $plans->map(fn (Plan $plan) => [
            '@type' => 'Product',
            'name' => 'Correo '.$plan->name.' @unagrandeylibre.es',
            'description' => $plan->description,
            'brand' => ['@id' => $org],
            'offers' => array_filter([
                '@type' => 'Offer',
                'price' => number_format($plan->effectivePriceCents() / 100, 2, '.', ''),
                'priceCurrency' => $plan->currency,
                'availability' => 'https://schema.org/InStock',
                'url' => $home.'#planes',
                'priceValidUntil' => $plan->currentOffer()?->ends_at?->toDateString(),
            ]),
        ])->values()->all();

        $faq = [
            '@type' => 'FAQPage',
            'mainEntity' => collect(config('landing.faq'))->map(fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ])->all(),
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $org,
                    'name' => 'Una Grande y Libre',
                    'url' => $home,
                    'logo' => asset('apple-touch-icon.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#web',
                    'url' => $home,
                    'name' => 'Una Grande y Libre',
                    'inLanguage' => 'es-ES',
                    'publisher' => ['@id' => $org],
                ],
                ...$products,
                $faq,
            ],
        ];
    }

    public static function toScript(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
