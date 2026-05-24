---
status: pending
file: app/Domain/Cashbox/Services/CashboxService.php
line: 27
severity: high
author: claude-code
provider_ref:
---

# Issue 012: CashboxService Financial Operations Without Transactions

## Review Comment

`recordEntry()` and `recordExpense()` create financial movements without `DB::transaction()` wrapping. If a mid-operation failure occurs (exception, crash, timeout), partial data can persist — leaving the cashbox in an inconsistent state. Financial operations touching the same `CashboxDay` should be atomic.

**Fix:** Wrap both methods in transactions:

```php
public function recordEntry(...): CashMovement {
    return DB::transaction(function () use ($tenantId, $cashboxDayId, $amount, $method, $appointmentId, $note) {
        // existing logic
    });
}
```

This should apply to all write operations in `CashboxService`.

## Triage

- Decision: `UNREVIEWED`
- Notes: