---
status: pending
file: app/Models/Scopes/TenantScope.php
line: 19
severity: critical
author: claude-code
provider_ref:
---

# Issue 001: TenantScope Exposes Records with NULL tenant_id

## Review Comment

When `TenantContext::current()` returns null, the scope adds `where tenant_id = null` instead of returning no results. This exposes all records that have `tenant_id = NULL` to unauthenticated or misconfigured requests. If any model has mixed null/non-null tenant_ids, data from unrelated tenants leaks.

The fix is to throw an exception or return a query that matches nothing when no tenant context is set:

```php
// Current (WRONG):
if ($tenantId !== null) {
    $builder->where($column, $tenantId);
}

// Fix: throw exception when no tenant context
if ($tenantId === null) {
    throw new RuntimeException('TenantContext not set. Cannot query tenant-scoped model.');
}
```

Alternatively, for read-only cases where null should return nothing:

```php
$builder->where($column, $tenantId ?? -1); // Matches nothing if null
```

## Triage

- Decision: `UNREVIEWED`
- Notes: