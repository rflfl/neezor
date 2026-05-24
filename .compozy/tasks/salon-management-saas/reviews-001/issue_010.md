---
status: pending
file: app/Domain/Packages/Services/PackageService.php
line: 97
severity: high
author: claude-code
provider_ref:
---

# Issue 010: Race Condition in Session Debit

## Review Comment

`debitSessionForAppointment()` calls `canBeUsable()` to check session validity, then in a separate transaction calls `$session->decrement('sessions_remaining')`. Two concurrent requests could both pass the `canBeUsed()` check and overdraw the session count. The `decrement()` call is not atomic with the validity check.

**Fix:** Use `lockForUpdate()` or an atomic conditional update:

```php
return DB::transaction(function () use ($session, $appointment) {
    $updated = PackageSession::where('id', $session->id)
        ->where('sessions_remaining', '>', 0)
        ->where('id', $session->id)
        ->update([
            'sessions_remaining' => DB::raw('sessions_remaining - 1'),
            'appointment_id' => $appointment->id,
            'used_at' => now(),
        ]);
    return $updated > 0;
});
```

Same race condition issue at `purchase()` lines 41-63 — verify services exist before creating sessions.

## Triage

- Decision: `UNREVIEWED`
- Notes: