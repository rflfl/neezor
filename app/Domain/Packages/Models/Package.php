<?php

namespace App\Domain\Packages\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\ScopeTenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\Domain\Packages\Models\PackageFactory;

class Package extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use ScopeTenantAware;

    protected $fillable = [
        'tenant_id',
        'name',
        'price',
        'valid_until_days',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'valid_until_days' => 'integer',
        ];
    }

    protected static function newFactory()
    {
        return new PackageFactory();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Services\Models\Service::class,
            'package_service'
        )->withPivot('session_count')->withTimestamps();
    }

    public function packageServices(): HasMany
    {
        return $this->hasMany(PackageService::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PackageSession::class);
    }
}