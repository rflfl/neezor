<?php

namespace Tests\Browser;

use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Domain\Scheduling\Models\Appointment;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AppointmentCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;
    private Professional $professional;
    private Client $client;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Salon Test']);
        TenantContext::setCurrent($this->tenant->id);

        $this->professional = Professional::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Silva',
            'email' => 'maria@test.com',
            'commission_rate' => 40.0,
        ]);

        $this->client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Cliente',
            'phone' => '11999999999',
            'email' => 'joao@test.com',
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte Feminino',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);
    }

    public function test_can_create_appointment(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->click('[data-testid="new-appointment-button"]')
                ->waitFor('[data-testid="appointment-modal"]', 5)
                ->select('[data-testid="client-select"]', (string) $this->client->id)
                ->select('[data-testid="professional-select"]', (string) $this->professional->id)
                ->select('[data-testid="service-select"]', (string) $this->service->id)
                ->type('[data-testid="date-input"]', now()->addDay()->toDateString())
                ->type('[data-testid="time-input"]', '10:00')
                ->press('[data-testid="submit-button"]')
                ->assertSee('created');
        });
    }

    public function test_can_edit_appointment(): void
    {
        $appointment = $this->createTestAppointment();
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($appointment, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->click('[data-testid="appointment-card-' . $appointment->id . '"]')
                ->waitFor('[data-testid="appointment-modal"]', 5)
                ->type('[data-testid="price-input"]', '75.00')
                ->press('[data-testid="submit-button"]')
                ->assertSee('updated');
        });
    }

    public function test_can_cancel_appointment(): void
    {
        $appointment = $this->createTestAppointment();
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($appointment, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->click('[data-testid="appointment-card-' . $appointment->id . '"]')
                ->waitFor('[data-testid="appointment-modal"]', 5)
                ->click('[data-testid="cancel-button"]')
                ->waitFor('[data-testid="confirm-modal"]', 5)
                ->press('[data-testid="confirm-yes-button"]')
                ->assertSee('Cancelado');
        });
    }

    public function test_can_complete_appointment(): void
    {
        $appointment = $this->createTestAppointment();
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($appointment, $user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->click('[data-testid="appointment-card-' . $appointment->id . '"]')
                ->waitFor('[data-testid="appointment-modal"]', 5)
                ->click('[data-testid="complete-button"]')
                ->waitFor('[data-testid="confirm-modal"]', 5)
                ->press('[data-testid="confirm-yes-button"]')
                ->assertSee('Concluído');
        });
    }

    public function test_professional_filter_works(): void
    {
        $this->createTestAppointment();
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->select('[data-testid="professional-filter"]', (string) $this->professional->id)
                ->assertSee($this->professional->name);
        });
    }

    public function test_mobile_responsive_layout(): void
    {
        $user = $this->createUserForTenant();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->resize(375, 667)
                ->visit(route('dashboard.calendar.index'))
                ->assertSee('Agenda')
                ->assertVisible('@mobile-menu-button')
                ->assertPresent('[data-testid="calendar-grid"]');
        });
    }

    private function createTestAppointment()
    {
        return Appointment::create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(11, 0),
            'status' => 'scheduled',
            'price' => $this->service->price,
        ]);
    }

    private function createUserForTenant()
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test_' . time() . '@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);
    }
}