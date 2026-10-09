<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_all_legal_pages_render_with_the_company_data(): void
    {
        foreach (array_keys(config('landing.legal')) as $page) {
            $this->get("/legal/{$page}")->assertOk()->assertSee(config('landing.legal')[$page]);
        }

        $this->get('/legal/aviso-legal')->assertSee('Tible Technologies, S.L.')->assertSee('B87456554')->assertSee('M-615786');
        $this->get('/legal/privacidad')->assertSee('Agencia Española de Protección de Datos')->assertSee('un mes');
        $this->get('/legal/condiciones')->assertSee('14 días naturales')->assertSee('juzgados de tu domicilio');
        $this->get('/legal/cookies')->assertSee('ugl_consent');
    }

    public function test_draft_notice_can_be_turned_off(): void
    {
        $this->get('/legal/privacidad')->assertSee('Borrador pendiente de revisión');
        config(['legal.draft' => false]);
        $this->get('/legal/privacidad')->assertDontSee('Borrador pendiente de revisión');
    }

    public function test_unknown_page_is_404(): void
    {
        $this->get('/legal/otra-cosa')->assertNotFound();
    }
}
