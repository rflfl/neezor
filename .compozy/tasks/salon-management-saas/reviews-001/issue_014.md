---
status: pending
file: app/Http/Controllers/Booking/BookingController.php
line: 210
severity: high
author: claude-code
provider_ref:
---

# Issue 014: Race Condition in Booking Slot Creation

## Review Comment

`BookingController::store()` checks availability through `AppointmentService`, but between the frontend slot selection and the backend `store()` call, another concurrent booking could take the same slot. There's no database-level lock or atomic conflict check at booking time.

**Fix:** The `AppointmentService::create()` method should re-validate slot availability atomically inside a transaction with row-level locking:

```php
return DB::transaction(function () use ($data) {
    // Re-check availability with lock
    $hasConflict = Appointment::where('tenant_id', $data['tenant_id'])
        ->where('professional_id', $data['professional_id'])
        ->where(function ($q) use ($start, $end) {
            $q->where('start_at', '<', $end)->where('end_at', '>', $start);
        })
        ->lockForUpdate() // row-level lock
        ->exists();

    if ($hasConflict) {
        throw new SlotAlreadyBookedException();
    }

    return Appointment::create($data);
});
```

The database unique constraint on `(tenant_id, professional_id, start_at)` catches exact-time conflicts but not overlapping appointments.

## Triage

- Decision: `UNREVIEWED`
- Notes: