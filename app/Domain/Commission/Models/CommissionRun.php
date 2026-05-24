<?php

namespace App\Domain\Commission\Models;

use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRun extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'professional_id',
        'period_start',
        'period_end',
        'total_gross',
        'total_commission',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_gross' => 'integer',
            'total_commission' => 'integer',
            'status' => CommissionRunStatus::class,
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Professional::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === CommissionRunStatus::PAID;
    }

    public function getTotalPaidAttribute(): int
    {
        return CommissionPayment::withoutGlobalScopes()
            ->where('commission_run_id', $this->id)
            ->sum('amount');
    }

    public function getPendingAmountAttribute(): int
    {
        return $this->total_commission - $this->total_paid;
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Commission\Models\CommissionRunFactory();
    }
}