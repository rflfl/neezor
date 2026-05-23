<?php

namespace Tests\Unit\Domain\Packages;

use App\Domain\Packages\Models\Package;
use App\Domain\Packages\Models\PackageSession;
use App\Domain\Packages\Services\PackageService;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PackageService $service;
    private Tenant $tenant;
    private Client $client;
    private Service $service1;
    private Service $service2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PackageService();
        $this->tenant = Tenant::factory()->create();
        TenantContext::setCurrent($this->tenant->id);
        $this->client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);
        $this->service1 = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);
        $this->service2 = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Depilação',
            'duration_minutes' => 45,
            'price' => 3000,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_create_package_succeeds(): void
    {
        $package = $this->service->createPackage([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals('Pacote Bronze', $package->name);
        $this->assertEquals(20000, $package->price);
        $this->assertEquals(90, $package->valid_until_days);
    }

    public function test_add_service_to_package(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);

        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $this->service1->id,
            'session_count' => 3,
        ]);
    }

    public function test_purchase_creates_sessions_for_each_service(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->addService($package, $this->service2->id, 2);

        $sessions = $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $this->assertCount(2, $sessions);
        $this->assertDatabaseHas('package_sessions', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => $package->id,
            'service_id' => $this->service1->id,
            'sessions_remaining' => 3,
        ]);
        $this->assertDatabaseHas('package_sessions', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => $package->id,
            'service_id' => $this->service2->id,
            'sessions_remaining' => 2,
        ]);
    }

public function test_purchase_sets_correct_expiration_date(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 30,
        ]);

        $this->service->addService($package, $this->service1->id, 3);

        $beforePurchase = Carbon::now();
        $sessions = $this->service->purchase($this->tenant->id, $this->client->id, $package);
        $afterPurchase = Carbon::now();

        $session = $sessions->first();
        $expectedExpiryMin = $beforePurchase->copy()->addDays(30)->startOfSecond();
        $expectedExpiryMax = $afterPurchase->copy()->addDays(30)->endOfSecond();

        $this->assertTrue(
            $session->expires_at->greaterThanOrEqualTo($expectedExpiryMin) && $session->expires_at->lessThanOrEqualTo($expectedExpiryMax),
            "Expiry {$session->expires_at} not in range [{$expectedExpiryMin} - {$expectedExpiryMax}]"
        );
    }

    public function test_find_usable_session_returns_valid_session(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $session = $this->service->findUsableSession($this->tenant->id, $this->client->id, $this->service1->id);

        $this->assertNotNull($session);
        $this->assertEquals(3, $session->sessions_remaining);
        $this->assertFalse($session->isExpired());
    }

    public function test_find_usable_session_returns_null_for_expired_session(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service1->id)
            ->update(['expires_at' => Carbon::now()->subDay()]);

        $session = $this->service->findUsableSession($this->tenant->id, $this->client->id, $this->service1->id);

        $this->assertNull($session);
    }

    public function test_find_usable_session_returns_null_when_no_sessions_remaining(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service1->id)
            ->update(['sessions_remaining' => 0]);

        $session = $this->service->findUsableSession($this->tenant->id, $this->client->id, $this->service1->id);

        $this->assertNull($session);
    }

    public function test_debit_session_decrements_remaining_count(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $sessions = $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $session = $sessions->first();
        $this->assertEquals(3, $session->sessions_remaining);

        $session->debitSession();
        $session->refresh();

        $this->assertEquals(2, $session->sessions_remaining);
        $this->assertNotNull($session->used_at);
    }

    public function test_debit_session_returns_false_when_expired(): void
    {
        $session = PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => null,
            'service_id' => $this->service1->id,
            'sessions_remaining' => 3,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $result = $session->debitSession();

        $this->assertFalse($result);
    }

    public function test_debit_session_returns_false_when_no_sessions_remaining(): void
    {
        $session = PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => null,
            'service_id' => $this->service1->id,
            'sessions_remaining' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $result = $session->debitSession();

        $this->assertFalse($result);
    }

    public function test_calculate_sessions_remaining_returns_correct_count(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $remaining = $this->service->calculateSessionsRemaining(
            $this->client->id,
            $package->id,
            $this->service1->id
        );

        $this->assertEquals(3, $remaining);
    }

    public function test_calculate_sessions_remaining_excludes_expired(): void
    {
        PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => null,
            'service_id' => $this->service1->id,
            'sessions_remaining' => 5,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $remaining = $this->service->calculateSessionsRemaining(
            $this->client->id,
            0,
            $this->service1->id
        );

        $this->assertEquals(0, $remaining);
    }

    public function test_get_client_active_sessions_returns_only_valid_sessions(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->addService($package, $this->service2->id, 2);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $sessions = $this->service->getClientActiveSessions($this->tenant->id, $this->client->id);

        $this->assertCount(2, $sessions);
    }

    public function test_is_expired_returns_true_for_past_date(): void
    {
        $session = new PackageSession();
        $session->expires_at = Carbon::now()->subDay();

        $this->assertTrue($session->isExpired());
    }

    public function test_is_expired_returns_false_for_future_date(): void
    {
        $session = new PackageSession();
        $session->expires_at = Carbon::now()->addDay();

        $this->assertFalse($session->isExpired());
    }

    public function test_has_remaining_sessions_returns_true_when_count_greater_than_zero(): void
    {
        $session = new PackageSession();
        $session->sessions_remaining = 3;

        $this->assertTrue($session->hasRemainingSessions());
    }

    public function test_has_remaining_sessions_returns_false_when_count_is_zero(): void
    {
        $session = new PackageSession();
        $session->sessions_remaining = 0;

        $this->assertFalse($session->hasRemainingSessions());
    }

    public function test_can_be_used_returns_true_for_valid_session(): void
    {
        $session = new PackageSession();
        $session->sessions_remaining = 3;
        $session->expires_at = Carbon::now()->addDay();

        $this->assertTrue($session->canBeUsed());
    }

    public function test_can_be_used_returns_false_for_expired_session(): void
    {
        $session = new PackageSession();
        $session->sessions_remaining = 3;
        $session->expires_at = Carbon::now()->subDay();

        $this->assertFalse($session->canBeUsed());
    }

    public function test_can_be_used_returns_false_when_no_sessions_remaining(): void
    {
        $session = new PackageSession();
        $session->sessions_remaining = 0;
        $session->expires_at = Carbon::now()->addDay();

        $this->assertFalse($session->canBeUsed());
    }

    public function test_delete_package_removes_package(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $result = $this->service->deletePackage($package);

        $this->assertTrue($result);
        $this->assertNull(Package::withoutGlobalScopes()->find($package->id));
    }

    public function test_update_package_updates_fields(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $updated = $this->service->updatePackage($package, [
            'name' => 'Pacote Prata',
            'price' => 30000,
        ]);

        $this->assertEquals('Pacote Prata', $updated->name);
        $this->assertEquals(30000, $updated->price);
    }

    public function test_remove_service_detaches_from_package(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->removeService($package, $this->service1->id);

        $this->assertDatabaseMissing('package_service', [
            'package_id' => $package->id,
            'service_id' => $this->service1->id,
        ]);
    }

    public function test_get_package_sessions_returns_all_sessions_for_package(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->service->addService($package, $this->service1->id, 3);
        $this->service->purchase($this->tenant->id, $this->client->id, $package);

        $sessions = $this->service->getPackageSessions($package->id);

        $this->assertCount(1, $sessions);
    }
}