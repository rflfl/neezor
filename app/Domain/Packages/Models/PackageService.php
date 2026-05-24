<?php

namespace App\Domain\Packages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageService extends Model
{
    protected $table = 'package_service';

    protected $fillable = [
        'package_id',
        'service_id',
        'session_count',
    ];

    protected function casts(): array
    {
        return [
            'session_count' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Services\Models\Service::class);
    }
}