<?php

namespace App\Domain\Packages\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Database\Factories\Domain\Packages\Models\PackageSessionFactory;

class PackageSession extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'package_id',
        'service_id',
        'appointment_id',
        'sessions_remaining',
        'used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sessions_remaining' => 'integer',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return new PackageSessionFactory();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Customers\Models\Client::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Services\Models\Service::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Scheduling\Models\Appointment::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && Carbon::now()->greaterThan($this->expires_at);
    }

    public function hasRemainingSessions(): bool
    {
        return $this->sessions_remaining > 0;
    }

    public function canBeUsed(): bool
    {
        return !$this->isExpired() && $this->hasRemainingSessions();
    }

    public function debitSession(): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        $this->decrement('sessions_remaining');
        $this->used_at = Carbon::now();
        $this->save();

        return true;
    }
}