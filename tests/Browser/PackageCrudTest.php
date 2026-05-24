<?php

namespace Tests\Browser;

use App\Domain\Packages\Models\Package;
use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PackageCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;
    private Service $service1;
    private Service $service2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Salon Test']);
        TenantContext::setCurrent($this->tenant->id);

        $this->service1 = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Feminino',
            'duration_minutes' => 60,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->service2 = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hidratação',
            'duration_minutes' => 45,
            'price' => 3000,
            'is_active' => true,
        ]);
    }

    public function test_can_view_packages_list(): void
    {
        $user = $this->createUserForTenant();
        Package::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 7000,
            'valid_until_days' => 30,
        ])->services()->attach([$this->service1->id => ['session_count' => 2]]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->assertSee('Pacotes')
                ->assertSee('Pacote Bronze')
                ->assertPresent('[data-testid="search-input"]');
        });
    }

    public function test_can_create_package(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->click('[data-testid="new-package-button"]')
                ->waitFor('[data-testid="package-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Pacote Prata')
                ->type('[data-testid="price-input"]', '120.00')
                ->click('[data-testid="add-service-button"]')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Pacote Prata', 5);
        });
    }

    public function test_can_add_multiple_services_to_package(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->click('[data-testid="new-package-button"]')
                ->waitFor('[data-testid="package-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Pacote Combo')
                ->type('[data-testid="price-input"]', '150.00')
                ->click('[data-testid="add-service-button"]')
                ->click('[data-testid="add-service-button"]')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Pacote Combo', 5);
        });
    }

    public function test_can_edit_package(): void
    {
        $package = Package::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Original',
            'price' => 10000,
            'valid_until_days' => 30,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($package, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->assertSee('Pacote Original')
                ->click('[data-testid="edit-button"]')
                ->waitFor('[data-testid="package-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Pacote Editado')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Pacote Editado', 5);
        });
    }

    public function test_can_delete_package(): void
    {
        $package = Package::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote para Deletar',
            'price' => 5000,
            'valid_until_days' => 30,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($package, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->assertSee('Pacote para Deletar')
                ->click('@delete-button')
                ->whenAvailable('.swal2-confirm', function ($button) {
                    $button->click();
                })
                ->waitForText('Pacote para Deletar', 5, true);
        });
    }

    public function test_can_view_package_sessions(): void
    {
        $package = Package::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Teste',
            'price' => 8000,
            'valid_until_days' => 60,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($package, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->assertSee('Pacote Teste')
                ->clickLink('Gerenciar sessões')
                ->assertSee('Gerenciar sessões do pacote')
                ->assertPresent('[data-testid="active-sessions-table"]');
        });
    }

    public function test_package_price_display(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.packages.index'))
                ->click('[data-testid="new-package-button"]')
                ->waitFor('[data-testid="package-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Pacote Preço')
                ->type('[data-testid="price-input"]', '99.99')
                ->press('[data-testid="submit-button"]')
                ->waitForText('R$ 99,99', 5);
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