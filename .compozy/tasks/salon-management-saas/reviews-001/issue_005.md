---
status: pending
file: app/Domain/Expenses/Services/DreService.php
line: 29
severity: critical
author: claude-code
provider_ref:
---

# Issue 005: OR Query Breaks Multi-Tenant Filter

## Review Comment

The DRE query uses `where` + `orWhereBetween` without proper grouping, causing the `tenant_id` filter to apply only to the first branch. Commission runs from other tenants (or with null tenant_id) can leak into the DRE report:

```php
// Current (BROKEN):
->where('tenant_id', $tenantId)
    ->whereBetween('period_start', [$start, $end])
->orWhereBetween('period_end', [$start, $end])
->where('status', '!=', 'draft')
```

Translates to: `(tenant AND start) OR (end) AND status != draft` — the `orWhereBetween` bypasses the tenant filter.

**Fix:** Wrap OR conditions in a closure:

```php
->where('tenant_id', $tenantId)
->where(function ($q) use ($start, $end) {
    $q->whereBetween('period_start', [$start, $end])
      ->orWhereBetween('period_end', [$start, $end]);
})
->where('status', '!=', 'draft')
```

Same pattern applies to the cash movements revenue query on line 20-24 — verify the `tenant_id` filter is inside the closure.

## Triage

- Decision: `UNREVIEWED`
- Notes: