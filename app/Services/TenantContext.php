<?php

namespace App\Services;

class TenantContext
{
    private static ?int $currentTenantId = null;

    public static function setCurrent(?int $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }

    public static function current(): ?int
    {
        return self::$currentTenantId;
    }

    public static function clear(): void
    {
        self::$currentTenantId = null;
    }
}
