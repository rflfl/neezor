<?php

namespace App\Domain\Commission\Models;

use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalServiceCommission extends Model
{
    use HasFactory;

    protected $table = 'professional_service_commissions';

    protected $fillable = [
        'professional_id',
        'service_id',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:4',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Services\Models\Service::class);
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Commission\Models\ProfessionalServiceCommissionFactory();
    }
}