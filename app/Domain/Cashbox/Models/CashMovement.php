<?php

namespace App\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Scheduling\Models\Appointment;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'cashbox_day_id',
        'type',
        'amount',
        'payment_method',
        'appointment_id',
        'expense_category_id',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => \App\Domain\Cashbox\Enums\CashMovementType::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function cashboxDay(): BelongsTo
    {
        return $this->belongsTo(CashboxDay::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Cashbox\Models\CashMovementFactory();
    }
}