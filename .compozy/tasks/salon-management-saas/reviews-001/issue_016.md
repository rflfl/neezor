---
status: pending
file: app/Domain/Packages/Services/PackageService.php
line: 50
severity: high
author: claude-code
provider_ref:
---

# Issue 016: withoutGlobalScopes() Removes ALL Global Scopes

## Review Comment

All domain services use `Model::withoutGlobalScopes()` which removes ALL global scopes (including `TenantScope`), not just the one intended. This pattern appears across multiple files: `PackageService.php` lines 50, 68, 109; `CashboxService.php` lines 18, 27, 49; `CommissionRun.php` line 55. While the services manually re-add `where('tenant_id', $tenantId)`, this is error-prone — a developer who forgets the filter creates a multi-tenancy leak.

**Fix:** Replace `withoutGlobalScopes()` with `withoutGlobalScope(TenantScope::class)`:

```php
// Before (dangerous):
PackageSession::withoutGlobalScopes()

// After (correct):
PackageSession::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
```

Create a `TenantAwareQuery` helper if this pattern is used frequently:

```php
public static function tenantQuery(): Builder {
    return (new static)->newQuery()->withoutGlobalScope(TenantScope::class);
}
```

Also add a PHPStan rule or custom sniff to detect `withoutGlobalScopes()` usage as an error.

## Triage

- Decision: `UNREVIEWED`
- Notes: