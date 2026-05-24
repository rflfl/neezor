<?php

namespace App\Domain\Customers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\ClientFactory;

class Client extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'phone' => 'string',
            'email' => 'string',
        ];
    }

    protected static function newFactory()
    {
        return new \Database\Factories\Domain\Customers\Models\ClientFactory();
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Domain\Scheduling\Models\Appointment::class);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereDoesntHave('appointments', function (Builder $q) {
            $q->where('start_at', '>=', Carbon::now()->subDays(60));
        });
    }

    public function scopeActiveRecently(Builder $query): Builder
    {
        return $query->whereHas('appointments', function (Builder $q) {
            $q->where('start_at', '>=', Carbon::now()->subDays(60));
        });
    }
}