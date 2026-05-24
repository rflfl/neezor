---
status: pending
file: app/Domain/Scheduling/Services/AppointmentService.php
line: 51
severity: medium
author: claude-code
provider_ref:
---

# Issue 018: Deprecated Queue::push() and No Error Handling

## Review Comment

`AppointmentService::create()` uses the deprecated `Queue::push()` method and sends reminders even for appointments that will be cancelled or marked as no-show. No error handling exists — if job dispatch fails, the user gets no feedback and the appointment is created silently.

**Fix:** Use `SendReminderJob::dispatch()` and add a guard for cancelled/no-show appointments:

```php
// Only dispatch if appointment is confirmed/scheduled (not cancelled):
if (in_array($data['status'], [AppointmentStatus::SCHEDULED, AppointmentStatus::CONFIRMED])) {
    SendReminderJob::dispatch($appointment->id)->delay(
        $appointment->start_at->subHours(24)
    );
}
```

Also replace `Queue::push()` with `SendReminderJob::dispatch()` for Laravel 11+ compatibility.

## Triage

- Decision: `UNREVIEWED`
- Notes: