---
status: pending
file: app/Domain/Commission/Services/CommissionService.php
line: 28
severity: critical
author: claude-code
provider_ref:
---

# Issue 004: Float Math in Financial Calculation

## Review Comment

The commission calculation uses direct float multiplication `(int) round($price * $rate)` on financial data. AGENTS.md mandates `moneyphp/money` for all monetary operations to avoid floating-point precision errors (e.g., `0.30 * 0.10` in floats does not equal `0.03` exactly).

The fix is to use `moneyphp/money`:

```php
use Money\Money;
use Money\Percentage;

// Before:
return (int) round($price * $rate);

// After:
$gross = new Money((int) round($price), new BRLCurrency());
$commissionRate = Percentage::of($rate * 100); // $rate is already decimal (0.40)
return $gross->allocateTo($rate * 100, (100 - $rate * 100));
```

Or simpler, since commission is a percentage of a known amount:

```php
$gross = (int) round($price * 100); // cents
$commissionCents = (int) round($gross * $rate); // rate is decimal like 0.40
return $commissionCents;
```

Same issue exists at lines 143-157 in `getEffectiveRate()` — rate lookups should also use integer math.

## Triage

- Decision: `UNREVIEWED`
- Notes: