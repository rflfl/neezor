---
status: pending
title: "Public Booking Portal"
type: frontend
complexity: high
dependencies:
  - task_09
---

# Task 12: Public Booking Portal

## Overview

Build the public booking portal (embedded in same SPA, token-authenticated) that allows clients to self-schedule without logging in. This delivers the "agende online" PRD feature via a shareable link.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `PublicBookingToken` middleware to validate token from URL query param
- MUST create BookingToken model: tenant_id, token (UUID), expires_at
- MUST create `resources/js/Pages/Booking/Index.vue` — landing: salon name, available services list
- MUST create `resources/js/Pages/Booking/Professional.vue` — select professional or "best availability"
- MUST create `resources/js/Pages/Booking/TimeSlot.vue` — show available time slots for selected service/professional/date
- MUST create `resources/js/Pages/Booking/Confirm.vue` — confirmation: client info form (name, phone), review details
- MUST create backend API endpoints for booking portal: `/booking/{slug}/services`, `/booking/{slug}/slots`, `/booking/{slug}/appointments`
- MUST implement: slot availability check via AvailabilityService at booking time (race condition handling)
- MUST implement: double-booking prevention (DB unique constraint is last-resort guard)
- MUST create simple layout `BookingLayout.vue` (minimal, no Jetstream auth components)
- MUST generate shareable links via dashboard (copy link button)
- MUST add routes: `/booking/{slug}` (public), `/booking/{slug}/services`, `/booking/{slug}/slots`, `/booking/{slug}/appointments`
- MUST write E2E tests for full booking flow
- MUST write integration test: booking portal appointment appears in dashboard calendar
</requirements>

## Subtasks
- [ ] 12.1 Migration: create `booking_tokens` table
- [ ] 12.2 Create `BookingToken` model
- [ ] 12.3 Create `PublicBookingToken` middleware
- [ ] 12.4 Create backend API controllers for booking portal endpoints
- [ ] 12.5 Register booking portal routes
- [ ] 12.6 Create `resources/js/Layouts/BookingLayout.vue`
- [ ] 12.7 Create Booking/Index.vue: service selection
- [ ] 12.8 Create Booking/Professional.vue: professional selection
- [ ] 12.9 Create Booking/TimeSlot.vue: slot picker with date navigation
- [ ] 12.10 Create Booking/Confirm.vue: client form + confirmation
- [ ] 12.11 Implement slot availability check at booking time
- [ ] 12.12 Add "copy link" button in dashboard
- [ ] 12.13 Write E2E tests: full booking flow (service → professional → slot → confirm)
- [ ] 12.14 Write integration test: booking → appears in dashboard calendar
- [ ] 12.15 Verify that booking portal requires no login

## Implementation Details

See ADR-007 for the "same SPA with public pages" decision. Token-based auth: client receives link like `/booking/salon-slug?token=uuid`. See TechSpec "API Endpoints" for public booking endpoints. Booking flow: client selects service → professional → date/time → confirms with name/phone.

### Relevant Files
- `app/Http/Middleware/PublicBookingToken.php` — create
- `app/Models/BookingToken.php` — create
- `app/Http/Controllers/Booking/BookingController.php` — create
- `app/Domain/Scheduling/Services/AvailabilityService.php` — modify: add public interface
- `resources/js/Layouts/BookingLayout.vue` — create
- `resources/js/Pages/Booking/Index.vue` — create
- `resources/js/Pages/Booking/Professional.vue` — create
- `resources/js/Pages/Booking/TimeSlot.vue` — create
- `resources/js/Pages/Booking/Confirm.vue` — create
- `routes/web.php` — add public booking routes (no auth middleware)
- `database/migrations/` — create booking_tokens table

### Dependent Files
- task_09: depends on Calendar + Appointment backend

### Related ADRs
- [ADR-007: Frontend de Auto-Agendamento — Mesma SPA com Páginas Públicas](../adrs/adr-007.md)