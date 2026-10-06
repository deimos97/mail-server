<?php

namespace Tests\Feature;

use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(DemoPlansSeeder::class);
    }

    public function test_landing_has_share_preview_and_icons(): void
    {
        $this->get('/')
            ->assertSee('<meta property="og:image" content="'.asset('img/og.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);
    }

    public function test_structured_data_lists_visible_plans_with_current_price(): void
    {
        $html = $this->get('/')->getContent();
        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $match);
        $graph = collect(json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['@graph']);

        $this->assertNotNull($graph->firstWhere('@type', 'Organization'));
        $this->assertNotNull($graph->firstWhere('@type', 'FAQPage'));

        $basico = $graph->firstWhere('name', 'Correo Básico @unagrandeylibre.es');
        $this->assertSame('1.00', $basico['offers']['price']);            // con la oferta de lanzamiento
        $this->assertSame('EUR', $basico['offers']['priceCurrency']);
        $this->assertArrayHasKey('priceValidUntil', $basico['offers']);
    }

    public function test_sitemap_and_llms_txt(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('home').'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('# Una Grande y Libre')
            ->assertSee('**Básico**: 1,00 €/mes')
            ->assertSee('IMAP: mail.unagrandeylibre.es, puerto 993')
            ->assertSee(url('/api/availability'));
    }
}
