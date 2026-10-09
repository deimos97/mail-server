<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StatusPageTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = sys_get_temp_dir().'/ugl-status-'.uniqid().'.json';
        config(['status.file' => $this->file]);
    }

    protected function tearDown(): void
    {
        File::delete($this->file);
        parent::tearDown();
    }

    private function writeStatus(array $problems, ?int $at = null): void
    {
        file_put_contents($this->file, json_encode(['checked_at' => $at ?? now()->timestamp, 'problems' => $problems]));
    }

    public function test_all_good(): void
    {
        $this->writeStatus([]);
        $this->get('/estado')->assertOk()->assertSee('Todo funciona con normalidad')->assertDontSee('Con incidencias');
    }

    public function test_a_problem_marks_only_its_components(): void
    {
        $this->writeStatus(['cert-993']);
        $response = $this->get('/estado')->assertOk()->assertSee('Hay una incidencia en curso');
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('#Apps de correo \(IMAP\)</span>\s*<span[^>]*>.*Con incidencias#s', $html);
        $this->assertMatchesRegularExpression('#Recibir correo</span>\s*<span[^>]*>.*?Operativo#s', $html);
        $this->assertStringNotContainsString('cert-993', $html);   // sin detalles internos
    }

    public function test_blacklists_affect_sending(): void
    {
        $this->writeStatus(['bl-zen.spamhaus.org']);
        $this->assertMatchesRegularExpression('#Enviar correo</span>\s*<span[^>]*>.*?Con incidencias#s', $this->get('/estado')->getContent());
    }

    public function test_stale_or_missing_data_is_not_shown_as_ok(): void
    {
        $this->get('/estado')->assertSee('Sin datos recientes');
        $this->writeStatus([], now()->subHour()->timestamp);
        $this->get('/estado')->assertSee('Sin datos recientes')->assertDontSee('Todo funciona con normalidad');
    }
}
