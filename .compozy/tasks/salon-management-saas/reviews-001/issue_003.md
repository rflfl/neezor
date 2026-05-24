---
status: pending
file: app/Http/Middleware/EnsureTenantIsSet.php
line: 22
severity: critical
author: claude-code
provider_ref:
---

# Issue 003: Session-Based tenant_id Manupulation

## Review Comment

The middleware reads `tenant_id` from the session (`session('tenant_id')`) and sets it in `TenantContext`. An attacker can manipulate the session cookie to set arbitrary tenant IDs, bypassing multi-tenancy isolation. The session tenant_id should be derived exclusively from the authenticated user's `tenant_id`, not from a writable session value.

**Fix:** Remove session-based tenant reading. Only trust the authenticated user's `tenant_id`:

```php
$user = $request->user();
if (!$user || !$user->tenant_id) {
    abort(403, 'No tenant associated with this account.');
}
TenantContext::setCurrent($user->tenant_id);
```

The `session('tenant_id')` read and the `session()->put('tenant_id', ...)` calls on lines 22 and 41 should be removed entirely.

## Triage

- Decision: `UNREVIEWED`
- Notes: