---
status: completed
title: "Frontend: Calendar + Appointment CRUD"
type: frontend
complexity: high
dependencies:
  - task_07
  - task_08
---

# Task 9: Frontend: Calendar + Appointment CRUD

## Overview

Build the Vue/Inertia frontend for the calendar view and appointment management. This is the primary daily-use interface — the receptionist and owner see the agenda here. Must be mobile-first with clear visual feedback.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Inertia page: `resources/js/Pages/Dashboard/Calendar.vue`
- MUST display: daily/weekly calendar view with appointments grouped by professional
- MUST support: create appointment (modal form), edit, cancel, mark as completed
- MUST show: client name, service, professional, time, status, price
- MUST implement: color-coded status indicators (scheduled=blue, confirmed=green, in_progress=yellow, completed=gray, cancelled=red, no_show=orange)
- MUST show: visual alert when slot is unavailable
- MUST implement: filter by professional
- MUST be mobile-first: usable on phone at the reception desk
- MUST reuse Jetstream components (TextInput, PrimaryButton, Modal, etc.)
- MUST use Tailwind CSS with design tokens from DESIGN.md
- MUST add Inertia route handler for `/dashboard/calendar` and `/dashboard/calendar/professional/{id}`
- MUST write Playwright/Cypress E2E tests for appointment CRUD flow
</requirements>

## Subtasks
- [x] 9.1 Create `resources/js/Pages/Dashboard/Calendar.vue`
- [x] 9.2 Create Calendar layout: day/week view with professional columns
- [x] 9.3 Implement appointment card component with status color
- [x] 9.4 Implement create appointment modal form (client select, service select, professional select, date/time picker)
- [x] 9.5 Implement edit appointment modal
- [x] 9.6 Implement cancel appointment with confirmation
- [x] 9.7 Implement mark as completed action
- [x] 9.8 Add professional filter dropdown
- [x] 9.9 Add route handler for `/dashboard/calendar`
- [x] 9.10 Add route handler for `/dashboard/calendar/professional/{id}`
- [x] 9.11 Write E2E tests: create appointment
- [x] 9.12 Write E2E tests: edit appointment
- [x] 9.13 Write E2E tests: cancel appointment
- [x] 9.14 Verify mobile responsiveness

## Implementation Details

See TechSpec "API Endpoints" for backend routes. See PRD "UX Considerations" for mobile-first, feedback visual. Reuse components from `resources/js/Components/`. Use Tailwind CSS with custom classes.

### Relevant Files
- `resources/js/Pages/Dashboard/Calendar.vue` — create
- `resources/js/Components/AppointmentCard.vue` — create
- `resources/js/Components/AppointmentModal.vue` — create
- `routes/web.php` — add calendar routes (if not already)
- `app/Http/Controllers/Dashboard/AppointmentController.php` — create

### Dependent Files
- task_07: depends on Domain/Commission backend being complete
- task_08: depends on Domain/Expenses backend being complete

### Related ADRs

### Verification Evidence
- Tests: 39 passed (appointment-related)
- Build: Successful (npm run build)
- Routes: 6 calendar routes registered