<?php

namespace Tests\Browser;

use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ServiceCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Salon Test']);
        TenantContext::setCurrent($this->tenant->id);
    }

    public function test_can_view_services_list(): void
    {
        $user = $this->createUserForTenant();
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Feminino',
            'duration_minutes' => 60,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->assertSee('Serviços')
                ->assertSee('Corte Feminino')
                ->assertPresent('[data-testid="services-table"]');
        });
    }

    public function test_can_create_service(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->click('[data-testid="new-service-button"]')
                ->waitFor('[data-testid="service-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Escova Progressiva')
                ->select('[data-testid="duration-input"]', '90')
                ->type('[data-testid="price-input"]', '150.00')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Escova Progressiva', 5);
        });
    }

    public function test_can_edit_service(): void
    {
        $service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Básico',
            'duration_minutes' => 30,
            'price' => 3000,
            'is_active' => true,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($service, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->assertSee('Corte Básico')
                ->click('[data-testid="edit-button"]')
                ->waitFor('[data-testid="service-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Corte Atualizado')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Corte Atualizado', 5);
        });
    }

    public function test_can_delete_service(): void
    {
        $service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Serviço para Deletar',
            'duration_minutes' => 30,
            'price' => 3000,
            'is_active' => true,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($service, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->assertSee('Serviço para Deletar')
                ->click('@delete-button')
                ->whenAvailable('.swal2-confirm', function ($button) {
                    $button->click();
                })
                ->waitForText('Serviço para Deletar', 5, true);
        });
    }

    public function test_can_toggle_inactive_services(): void
    {
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Serviço Ativo',
            'duration_minutes' => 30,
            'price' => 3000,
            'is_active' => true,
        ]);
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Serviço Inativo',
            'duration_minutes' => 30,
            'price' => 3000,
            'is_active' => false,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->assertSee('Serviço Ativo')
                ->assertDontSee('Serviço Inativo')
                ->check('input[type="checkbox"]')
                ->assertSee('Serviço Inativo');
        });
    }

    public function test_can_search_services(): void
    {
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Feminino',
            'duration_minutes' => 60,
            'price' => 5000,
            'is_active' => true,
        ]);
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Manicure',
            'duration_minutes' => 30,
            'price' => 2500,
            'is_active' => true,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->type('[data-testid="search-input"]', 'Corte')
                ->assertSee('Corte Feminino')
                ->assertDontSee('Manicure');
        });
    }

    public function test_service_price_display(): void
    {
        Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Masculino',
            'duration_minutes' => 30,
            'price' => 3500,
            'is_active' => true,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.services.index'))
                ->assertSee('R$ 35,00');
        });
    }

    private function createUserForTenant(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test_' . time() . '@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);
    }
}