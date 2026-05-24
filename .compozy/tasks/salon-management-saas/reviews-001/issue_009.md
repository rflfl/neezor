---
status: pending
file: app/Http/Controllers/Dashboard/ClientController.php
line: 73
severity: critical
author: claude-code
provider_ref:
---

# Issue 009: Client/Service Store Methods Missing tenant_id

## Review Comment

`ClientController::store()` creates records without setting `tenant_id`. The `fill()` method at line 73 doesn't include `tenant_id`, and unlike `bulkStore()` which correctly sets it via `$request->user()->tenant_id`, this creates records with `tenant_id = NULL`. The same issue exists in `ServiceController::store()` (line 32). This violates multi-tenancy isolation and breaks the global scope filtering.

**Fix:** Set `tenant_id` during creation in both methods:

```php
// ClientController::store() line ~73:
Client::create(array_merge($validated, [
    'tenant_id' => $request->user()->tenant_id,
]));
```

And add `'tenant_id'` to the model's `$fillable` array. Same fix for `ServiceController`.

## Triage

- Decision: `UNREVIEWED`
- Notes: