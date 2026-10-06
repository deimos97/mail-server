<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\NameRule;
use App\Services\NameAvailability;
use App\Services\NameCheck;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class NameAvailabilityTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class]);   // tramos 1–2 y 3–4
        $this->domain = Domain::firstOrFail();
    }

    private function check(string $name): NameCheck
    {
        return app(NameAvailability::class)->check($name, $this->domain);
    }

    public function test_a_free_long_name_is_available_without_surcharge(): void
    {
        $check = $this->check('javiervarela');

        $this->assertSame(NameCheck::AVAILABLE, $check->status);
        $this->assertNull($check->surchargeCents);
        $this->assertFalse($check->requiresPaidPlan());
    }

    public function test_input_is_normalized_and_a_pasted_address_loses_its_domain(): void
    {
        $check = $this->check('  Javier.Varela@unagrandeylibre.es ');

        $this->assertSame('javier.varela', $check->localPart);
        $this->assertTrue($check->isAvailable());
    }

    public function test_short_names_are_available_with_surcharge_and_paid_plan_only(): void
    {
        $this->assertSame(300, $this->check('jv')->surchargeCents);
        $this->assertSame(100, $this->check('jvr')->surchargeCents);
        $this->assertTrue($this->check('jvr')->requiresPaidPlan());
        $this->assertNull($this->check('jvrxy')->surchargeCents);
    }

    public function test_format_rules_come_from_the_database(): void
    {
        $this->assertSame(NameCheck::INVALID, $this->check('.pepe')->status);
        $this->assertSame(NameCheck::INVALID, $this->check('pepe-')->status);
        $this->assertSame(NameCheck::INVALID, $this->check('pe..pe')->status);
        $this->assertSame(NameCheck::INVALID, $this->check('pe pe')->status);
        $this->assertSame(NameCheck::INVALID, $this->check(str_repeat('a', 33))->status);
        $this->assertTrue($this->check('pe.pe_p-e')->isAvailable());

        NameRule::firstOrFail()->update(['allowed_symbols' => '.', 'max_length' => 40]);

        $this->assertSame(NameCheck::INVALID, $this->check('pe_pe')->status);
        $this->assertTrue($this->check(str_repeat('a', 40))->isAvailable());
    }

    public function test_reserved_names_are_unavailable_without_saying_why(): void
    {
        $check = $this->check('Postmaster');

        $this->assertSame(NameCheck::UNAVAILABLE, $check->status);
        $this->assertSame('Este nombre no está disponible.', $check->message);
    }

    public function test_existing_mailboxes_and_aliases_are_taken(): void
    {
        $this->addMailbox('pepe@unagrandeylibre.es');
        $this->addAlias('ventas2@unagrandeylibre.es');

        $this->assertSame(NameCheck::TAKEN, $this->check('pepe')->status);
        $this->assertSame(NameCheck::TAKEN, $this->check('ventas2')->status);
    }

    public function test_taken_names_get_free_available_suggestions(): void
    {
        $this->addMailbox('pepe@unagrandeylibre.es');
        $this->addMailbox('pepe.es@unagrandeylibre.es');

        $suggestions = $this->check('pepe')->suggestions;

        $this->assertCount(3, $suggestions);
        $this->assertNotContains('pepe.es', $suggestions);
        foreach ($suggestions as $suggestion) {
            $this->assertTrue($this->check($suggestion)->isAvailable());
        }
    }

    public function test_accents_and_enye_are_invalid_but_suggested_without_them(): void
    {
        $check = $this->check('peña');

        $this->assertSame(NameCheck::INVALID, $check->status);
        $this->assertSame('pena', $check->suggestions[0]);
        $this->assertSame('jose', $this->check('josé')->suggestions[0]);
    }

    public function test_endpoint_returns_json_for_signup_domains_only(): void
    {
        $this->getJson('/api/availability?local=javiervarela')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson(['status' => 'available', 'available' => true, 'email' => 'javiervarela@unagrandeylibre.es',
                'requires_paid_plan' => false]);

        $this->getJson('/api/availability?local=jv')
            ->assertJson(['available' => true, 'surcharge_cents' => 300, 'requires_paid_plan' => true]);

        $this->getJson('/api/availability?local=pepe&domain=gmail.com')->assertStatus(422);
        $this->getJson('/api/availability')->assertStatus(422);
    }

    public function test_endpoint_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/availability?local=nombre'.$i)->assertOk();
        }

        $this->getJson('/api/availability?local=otro')->assertStatus(429);
    }
}
