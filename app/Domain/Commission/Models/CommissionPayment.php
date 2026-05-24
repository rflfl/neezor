<?php

namespace App\Domain\Commission\Models;

use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayment extends Model
{
    use HasFactory;
    use ScopeTenantAware;

    protected $fillable = [
        'commission_run_id',
        'amount',
        'paid_at',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function commissionRun(): BelongsTo
    {
        return $this->belongsTo(CommissionRun::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Commission\Models\CommissionPaymentFactory();
    }
}