---
status: pending
file: app/Domain/Scheduling/Services/AvailabilityService.php
line: 121
severity: critical
author: claude-code
provider_ref:
---

# Issue 006: bookSlot() Is a No-Op

## Review Comment

The `bookSlot()` method validates time conflicts and returns the appointment, but it never saves the `$start` and `$end` times to the appointment model. A dirty model is returned without calling `save()` or `update()`. Any caller relying on this method to actually set the appointment times will silently get incorrect data.

```php
public function bookSlot(...): Appointment {
    // validates conflict...
    return $appointment; // NEVER SAVED — start/end remain null/default
}
```

**Fix:** Save the model before returning:

```php
public function bookSlot(...): Appointment {
    // validate conflict...
    $appointment->forceFill(['start_at' => $start, 'end_at' => $end]);
    $appointment->save();
    return $appointment;
}
```

Also add a test case asserting that after `bookSlot()`, the appointment's `start_at` and `end_at` are actually persisted.

## Triage

- Decision: `UNREVIEWED`
- Notes: