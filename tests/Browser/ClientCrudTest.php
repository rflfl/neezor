<?php

namespace Tests\Browser;

use App\Domain\Customers\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Salon Test']);
        TenantContext::setCurrent($this->tenant->id);
    }

    public function test_can_view_clients_list(): void
    {
        $user = $this->createUserForTenant();
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Cliente',
            'phone' => '11999999999',
            'email' => 'joao@test.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->assertSee('Clientes')
                ->assertSee('João Cliente')
                ->assertPresent('[data-testid="clients-table"]');
        });
    }

    public function test_can_create_client(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->click('[data-testid="new-client-button"]')
                ->waitFor('[data-testid="client-modal"]', 5)
                ->type('[data-testid="name-input"]', 'Maria Silva')
                ->type('[data-testid="phone-input"]', '21988887777')
                ->type('[data-testid="email-input"]', 'maria@test.com')
                ->press('[data-testid="submit-button"]')
                ->waitForText('Maria Silva', 5);
        });
    }

    public function test_can_edit_client(): void
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Cliente',
            'phone' => '11999999999',
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($client, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->assertSee('João Cliente')
                ->click('[data-testid="edit-button"]')
                ->waitFor('[data-testid="client-modal"]', 5)
                ->type('[data-testid="name-input"]', 'João Atualizado')
                ->press('[data-testid="submit-button"]')
                ->waitForText('João Atualizado', 5);
        });
    }

    public function test_can_delete_client(): void
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cliente para Deletar',
            'phone' => '11999999999',
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($client, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->assertSee('Cliente para Deletar')
                ->click('@delete-button')
                ->whenAvailable('.swal2-confirm', function ($button) {
                    $button->click();
                })
                ->waitForText('Cliente para Deletar', 5, true);
        });
    }

    public function test_can_view_client_detail(): void
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Cliente',
            'phone' => '11999999999',
            'email' => 'joao@test.com',
            'notes' => 'Cliente VIP',
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($client, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.show', $client->id))
                ->assertSee('João Cliente')
                ->assertSee('Cliente VIP')
                ->assertPresent('[data-testid="appointments-table"]');
        });
    }

    public function test_can_toggle_inactive_clients(): void
    {
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cliente Ativo',
            'is_active' => true,
        ]);
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cliente Inativo',
            'is_active' => false,
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->assertSee('Cliente Ativo')
                ->assertDontSee('Cliente Inativo')
                ->check('input[type="checkbox"]')
                ->assertSee('Cliente Inativo');
        });
    }

    public function test_can_search_clients(): void
    {
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Silva',
            'phone' => '11999999999',
        ]);
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Santos',
            'phone' => '21988887777',
        ]);
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.clients.index'))
                ->type('[data-testid="search-input"]', 'João')
                ->assertSee('João Silva')
                ->assertDontSee('Maria Santos');
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