<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use ScopeTenantAware;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const VALID_TRANSITIONS = [
        self::STATUS_SCHEDULED => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW],
        self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_NO_SHOW],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_NO_SHOW => [],
    ];

    protected $fillable = [
        'tenant_id',
        'professional_id',
        'client_id',
        'service_id',
        'package_id',
        'start_at',
        'end_at',
        'status',
        'price',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'price' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Packages\Models\Package::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::VALID_TRANSITIONS[$this->status] ?? []);
    }
}
