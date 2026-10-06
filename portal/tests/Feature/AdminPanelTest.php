<?php

namespace Tests\Feature;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $withMfa = true): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;   // no es asignable en masa
        if ($withMfa) {
            $user->app_authentication_secret = AppAuthentication::make()->generateSecret();
        }
        $user->save();

        return $user;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_customers_cannot_enter_the_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_is_admin_cannot_be_mass_assigned(): void
    {
        $user = User::create(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret-123', 'is_admin' => true]);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_admins_without_two_factor_must_set_it_up(): void
    {
        $this->actingAs($this->admin(withMfa: false))
            ->get('/admin/plans')
            ->assertRedirect('/admin/multi-factor-authentication/set-up');
    }

    public function test_admin_pages_render(): void
    {
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class, DemoPlansSeeder::class]);
        $plan = Plan::where('slug', 'basico')->firstOrFail();

        $this->actingAs($this->admin());

        foreach (['/admin', '/admin/plans', '/admin/plans/create', "/admin/plans/{$plan->id}/edit",
            '/admin/reserved-names', '/admin/name-price-tiers', '/admin/name-rules'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/admin/plans')->assertSee('Básico')->assertSee('-50 % lanzamiento');
    }

    public function test_plan_form_stores_cents_and_bytes(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreatePlan::class)
            ->fillForm([
                'name' => 'Familia', 'slug' => 'familia', 'is_free' => false,
                'price_cents' => '2.50', 'interval' => 'month',
                'quota_bytes' => '5', 'max_aliases' => 3, 'send_limit_per_hour' => 80, 'tier' => 'family',
                'is_active' => true, 'sort_order' => 40,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $plan = Plan::where('slug', 'familia')->firstOrFail();
        $this->assertSame(250, $plan->price_cents);
        $this->assertSame(5 * 1024 ** 3, $plan->quota_bytes);

        // Al editar se ve en euros y GB, y guardar sin tocar nada no altera los valores
        Livewire::test(EditPlan::class, ['record' => $plan->getRouteKey()])
            ->assertSchemaStateSet(['price_cents' => '2.50', 'quota_bytes' => 5])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(250, $plan->fresh()->price_cents);
    }

    public function test_free_plans_are_saved_at_zero(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreatePlan::class)
            ->fillForm(['name' => 'Libre', 'slug' => 'libre', 'is_free' => true, 'quota_bytes' => '1',
                'max_aliases' => 0, 'send_limit_per_hour' => 20, 'tier' => 'free'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, Plan::where('slug', 'libre')->value('price_cents'));
    }
}
