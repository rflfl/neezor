<?php

namespace App\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashboxDay extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'date',
        'opening_balance',
        'closing_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'opening_balance' => 'integer',
            'closing_balance' => 'integer',
            'status' => CashboxStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CashMovement::class)->where('type', 'entry');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashMovement::class)->where('type', 'expense');
    }

    public function getTotalEntriesAttribute(): int
    {
        return $this->entries()->sum('amount');
    }

    public function getTotalExpensesAttribute(): int
    {
        return $this->expenses()->sum('amount');
    }

    public function getExpectedClosingBalanceAttribute(): int
    {
        return $this->opening_balance + $this->total_entries - $this->total_expenses;
    }

    public function hasDiscrepancy(): bool
    {
        if ($this->closing_balance === null) {
            return false;
        }

        return $this->closing_balance !== $this->expected_closing_balance;
    }

    public function getDiscrepancyAmountAttribute(): int
    {
        if ($this->closing_balance === null) {
            return 0;
        }

        return $this->closing_balance - $this->expected_closing_balance;
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Cashbox\Models\CashboxDayFactory();
    }
}