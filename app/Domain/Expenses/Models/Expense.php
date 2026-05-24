<?php

namespace App\Domain\Expenses\Models;

use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Database\Factories\Domain\Expenses\Models\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'amount',
        'expense_category_id',
        'is_recurring',
        'description',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_recurring' => 'boolean',
            'due_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    protected static function newFactory()
    {
        return new ExpenseFactory;
    }
}
