<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Plan;
use App\Support\Money;
use Illuminate\Http\Response;

/** sitemap.xml y llms.txt, generados desde la BD para que nunca se queden desfasados. */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        // Las páginas legales entrarán cuando tengan contenido (Fase 5)
        $urls = [route('home')];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .collect($urls)->map(fn (string $url) => '  <url><loc>'.e($url).'</loc></url>')->implode("\n")
            ."\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Resumen para asistentes de IA (https://llmstxt.org). */
    public function llms(): Response
    {
        $domain = Domain::signup()->value('name') ?? 'unagrandeylibre.es';
        $home = route('home');
        $api = url('/api/availability');
        $plansApi = url('/api/plans');
        $signup = url('/alta');
        $status = route('status');

        $plans = Plan::visible()->ordered()->with('offers')->get()->map(function (Plan $plan) {
            $per = $plan->interval === 'year' ? 'año' : 'mes';
            $price = $plan->is_free ? 'gratis' : Money::format($plan->effectivePriceCents())."/$per (IVA incluido)";
            if ($offer = $plan->currentOffer()) {
                $price .= ", oferta \"{$offer->label}\"; precio normal ".Money::format($plan->price_cents)."/$per";
            }
            $features = $plan->features ? ' — '.implode(', ', $plan->features) : '';

            return "- **{$plan->name}**: {$price}{$features}";
        })->implode("\n");

        $text = <<<MD
        # Una Grande y Libre

        > Servicio de correo electrónico español con direcciones @{$domain}: privado, sin publicidad y con los servidores en la Unión Europea.

        ## Conseguir una cuenta

        - Web: {$home}
        - Se elige el nombre (tunombre@{$domain}) y un plan. Los nombres de 1 a 4 caracteres tienen un suplemento mensual y solo se pueden coger con planes de pago.
        - Comprobar si un nombre está libre: `GET {$api}?local=tunombre` (JSON; limitado por IP).
        - Planes y precios en JSON: `GET {$plansApi}`
        - Empezar el alta con el nombre elegido: {$signup}?nombre=tunombre (el usuario completa el resto).
        - Agentes en el navegador: la web expone herramientas WebMCP (`comprobar_disponibilidad`, `listar_planes`, `empezar_alta`).
        - Estado del servicio: {$status}

        ## Planes

        {$plans}

        ## Configurar una app de correo

        - IMAP: mail.{$domain}, puerto 993, SSL/TLS
        - SMTP: mail.{$domain}, puerto 465 (SSL/TLS) o 587 (STARTTLS)
        - Usuario: la dirección completa
        - Webmail: https://webmail.{$domain}
        MD;

        return response($text."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
