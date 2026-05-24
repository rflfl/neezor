---
status: pending
file: app/Domain/Expenses/Services/DreService.php
line: 20
severity: medium
author: claude-code
provider_ref:
---

# Issue 019: DRE Revenue by Movement Creation Date, Not Appointment Date

## Review Comment

Revenue is summed using `CashMovement::whereBetween('created_at', [$start, $end])`. If a cash entry is created days after the appointment (e.g., manual correction, delayed payment recording), it distorts the monthly DRE report. Revenue should be attributed to the month when the service was actually delivered, not when the cash movement was created.

**Fix:** Link cash movements to appointments and use the appointment date:

```php
// Sum revenue from movements whose linked appointment occurred in the period:
CashMovement::where('type', 'entry')
    ->whereHas('appointment', function ($q) use ($start, $end) {
        $q->whereBetween('start_at', [$start, $end]);
    })
    ->where('tenant_id', $tenantId)
    ->sum('amount');
```

Add a relationship `CashMovement::appointment()` and ensure `recordEntry()` stores the `appointment_id`.

## Triage

- Decision: `UNREVIEWED`
- Notes: