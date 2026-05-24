---
status: pending
file: app/Domain/Commission/Services/CommissionService.php
line: 55
severity: high
author: claude-code
provider_ref:
---

# Issue 011: CommissionService Overwrites Paid Commission Runs

## Review Comment

`calculateForPeriod()` is idempotent on creation but overwrites existing commission runs. If called after payments have been recorded, it silently replaces `total_gross` and `total_commission` — potentially changing a paid commission's amount after the professional has already been paid. There's no guard checking that the run's status isn't already `PAID`.

**Fix:** Add a status guard at the start of recalculation:

```php
$run = CommissionRun::where('period_start', $start)
    ->where('period_end', $end)
    ->first();

if ($run && $run->status === CommissionRunStatus::PAID) {
    throw new InvalidOperationException(
        'Cannot recalculate a paid commission run. Create a new period or reverse the payment first.'
    );
}
```

Same guard should be added to `recordAdjustment()`.

## Triage

- Decision: `UNREVIEWED`
- Notes: