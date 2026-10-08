<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class RspamdMapTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        foreach ([['ana', 'basic', 'active'], ['pepe', 'pro', 'active'], ['luis', 'free', 'active'], ['eva', 'basic', 'deleted'], ['sys', 'system', 'active']] as [$l, $tier, $status]) {
            Mailbox::create(['domain_id' => 1, 'local_part' => $l, 'email' => "$l@unagrandeylibre.es", 'password' => 'x', 'tier' => $tier, 'status' => $status]);
        }
    }

    public function test_maps_list_active_mailboxes_per_paid_tier(): void
    {
        $this->get('/internal/rspamd/basic.map')->assertOk()->assertSeeText("ana@unagrandeylibre.es\n", false)->assertDontSee('eva@');
        $this->assertSame("ana@unagrandeylibre.es\npepe@unagrandeylibre.es\n", $this->get('/internal/rspamd/paid.map')->getContent());
        $this->get('/internal/rspamd/free.map')->assertNotFound();
    }

    public function test_only_from_the_server(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/internal/rspamd/paid.map')->assertForbidden();
    }
}
