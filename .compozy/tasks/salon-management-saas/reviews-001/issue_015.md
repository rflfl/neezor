---
status: pending
file: database/migrations/2026_05_23_180400_create_appointments_table.php
line: 24
severity: critical
author: claude-code
provider_ref:
---

# Issue 015: Wrong Unique Constraint for Double-Booking Prevention

## Review Comment

The appointments table uses `->unique(['tenant_id', 'professional_id', 'start_at'])` which only prevents appointments starting at the exact same second. Appointments from 09:00-10:00 and 09:01-10:01 would both be allowed for the same professional, even though they overlap. The application-level `hasConflict()` logic in `AvailabilityService` correctly checks time overlap (`start < otherEnd AND end > otherStart`), but the DB constraint doesn't match it.

The correct constraint depends on the overlap detection strategy:

**Option A (recommended):** Unique constraint on `(tenant_id, professional_id, start_at)` is acceptable if the system rounds all bookings to fixed time slots (e.g., 15-min increments). If appointments can start at any minute, this constraint is insufficient.

**Option B (correct for variable-length appointments):** Add a trigger or application-level check with a proper overlapping query:

```sql
-- At DB level, use an exclusion constraint (PostgreSQL) or handle in application
-- Application-level in AppointmentService::create():
$conflict = Appointment::where('tenant_id', $tenantId)
    ->where('professional_id', $professionalId)
    ->where('start_at', '<', $end)
    ->where('end_at', '>', $start)
    ->exists();
if ($conflict) abort(409, 'Slot no longer available.');
```

Document which approach is used and ensure it's enforced consistently.

## Triage

- Decision: `UNREVIEWED`
- Notes: