<?php

namespace Tests\Unit\Domain\Booking;

use App\Models\BookingToken;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTokenTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create([
            'name' => 'Salon Test',
            'slug' => 'salon-test',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);
    }

    public function test_generate_for_tenant_creates_token(): void
    {
        $token = BookingToken::generateForTenant($this->tenant->id);

        $this->assertNotNull($token->token);
        $this->assertEquals($this->tenant->id, $token->tenant_id);
        $this->assertFalse($token->isExpired());
        $this->assertEquals(30, round(Carbon::now()->diffInDays($token->expires_at)));
    }

    public function test_is_expired_returns_true_for_past_date(): void
    {
        $token = BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'test-token',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($token->isExpired());
    }

    public function test_is_expired_returns_false_for_future_date(): void
    {
        $token = BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'test-token',
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse($token->isExpired());
    }

    public function test_find_valid_by_token_returns_valid_token(): void
    {
        $token = BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'valid-token',
            'expires_at' => Carbon::now()->addDays(10),
        ]);

        $found = BookingToken::findValidByToken('valid-token');

        $this->assertNotNull($found);
        $this->assertEquals($token->id, $found->id);
    }

    public function test_find_valid_by_token_returns_null_for_expired(): void
    {
        BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'expired-token',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $found = BookingToken::findValidByToken('expired-token');

        $this->assertNull($found);
    }

    public function test_find_valid_by_token_returns_null_for_nonexistent(): void
    {
        $found = BookingToken::findValidByToken('nonexistent-token');
        $this->assertNull($found);
    }

    public function test_token_is_uuid_format(): void
    {
        $token = BookingToken::generateForTenant($this->tenant->id);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $token->token
        );
    }
}