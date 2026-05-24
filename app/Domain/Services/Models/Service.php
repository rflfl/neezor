<?php

namespace App\Domain\Services\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Database\Factories\ServiceFactory;

class Service extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'name',
        'duration_minutes',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Services\Models\ServiceFactory();
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}