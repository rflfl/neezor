---
status: pending
file: app/Http/Middleware/HandleInertiaRequests.php
line: 23
severity: high
author: claude-code
provider_ref:
---

# Issue 017: TenantContext Null Exposed to Frontend

## Review Comment

`HandleInertiaRequests::share()` returns `tenant` and `tenant_id` via `TenantContext::current()` which can return `null` if middleware order is wrong or context wasn't set. Exposing `null` tenant_id to the Vue frontend could cause runtime errors or unexpected behavior in components that rely on this prop.

**Fix:** Always provide a fallback or validate:

```php
public function share(Request $request): array
{
    $tenantId = TenantContext::current();
    if ($tenantId === null) {
        $tenantId = $request->user()?->tenant_id;
    }
    if ($tenantId === null) {
        return []; // Don't expose null tenant to frontend
    }
    return [
        'tenant' => Tenant::find($tenantId),
        'tenant_id' => $tenantId,
    ];
}
```

## Triage

- Decision: `UNREVIEWED`
- Notes: