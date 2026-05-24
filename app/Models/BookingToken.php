<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingToken extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function generateForTenant(int $tenantId): self
    {
        return static::create([
            'tenant_id' => $tenantId,
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public static function findValidByToken(string $token): ?self
    {
        return static::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }
}