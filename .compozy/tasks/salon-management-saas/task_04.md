---
status: pending
title: "Domain Scheduling — Core"
type: backend
complexity: high
dependencies:
  - task_02
  - task_03
---

# Task 4: Domain Scheduling — Core

## Overview

Build the custom scheduling engine (Domain/Scheduling) from scratch. This is the most complex domain: it handles appointment creation, availability calculation, conflict prevention, and slot generation. Double-booking is the primary risk — DB constraint and unit tests must guarantee prevention.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Scheduling directory structure: Models/, Services/, Contracts/, Events/
- MUST create Appointment model: tenant_id, professional_id, client_id, service_id, package_id (nullable), start_at, end_at, status, price
- MUST create composite unique index on (tenant_id, professional_id, start_at) — critical for double-booking prevention
- MUST implement AvailabilityService with: getAvailableSlots(), isSlotAvailable(), bookSlot()
- MUST implement slot generation algorithm: derive available slots from professional schedule + existing appointments + service duration + buffer
- MUST implement conflict detection: reject overlapping appointments for same professional
- MUST add appointment status transitions: scheduled → confirmed → in_progress → completed → cancelled
- MUST create AppointmentController with CRUD
- MUST add routes: `/dashboard/calendar` (GET/POST), `/dashboard/calendar/{id}` (GET/PUT/DELETE)
- MUST create AppointmentFactory
- MUST write extensive unit tests (critical scenarios per TechSpec)
- MUST write integration tests for appointment flows
</requirements>

## Subtasks
- [ ] 4.1 Migration: create `appointments` table with composite unique index
- [ ] 4.2 Create Domain/Scheduling/Models/Appointment.php
- [ ] 4.3 Create Domain/Scheduling/Contracts/AvailabilityServiceInterface.php
- [ ] 4.4 Create Domain/Scheduling/Services/AvailabilityService.php — slot generation logic
- [ ] 4.5 Create Domain/Scheduling/Services/AppointmentService.php — CRUD + state transitions
- [ ] 4.6 Create AppointmentController
- [ ] 4.7 Register appointment routes
- [ ] 4.8 Add Appointment → Client, Professional, Service relationships
- [ ] 4.9 Create AppointmentFactory
- [ ] 4.10 Write unit tests: availability slot generation (all duration combinations)
- [ ] 4.11 Write unit tests: conflict detection (overlapping appointments)
- [ ] 4.12 Write unit tests: status transitions
- [ ] 4.13 Write integration tests: create appointment → visible in calendar
- [ ] 4.14 Write integration test: double-booking attempt returns error

## Implementation Details

See TechSpec "Core Interfaces" for AvailabilityServiceInterface contract. See TechSpec "Data Models" for Appointment entity. See ADR-004 for custom scheduler decision. Key risk: double-booking — DB unique constraint on (tenant_id, professional_id, start_at) is the last-resort guard.

### Relevant Files
- `app/Domain/Scheduling/Models/Appointment.php` — create
- `app/Domain/Scheduling/Services/AvailabilityService.php` — create (core logic)
- `app/Domain/Scheduling/Services/AppointmentService.php` — create
- `app/Domain/Scheduling/Contracts/AvailabilityServiceInterface.php` — create
- `app/Domain/Scheduling/Controllers/AppointmentController.php` — create
- `routes/web.php` — add appointment routes
- `database/migrations/` — create appointments table with unique constraint

### Dependent Files
- task_02: depends on Professional model
- task_03: depends on Service and Client models
- task_05+: depends on Appointment model for packages

### Related ADRs
- [ADR-004: Sistema de Agenda — Custom Scheduler](../adrs/adr-004.md) — custom over package