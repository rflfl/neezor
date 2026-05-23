<?php

namespace App\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\ExpenseCategoryType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExpenseCategoryType::class,
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

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Cashbox\Models\ExpenseCategoryFactory();
    }
}