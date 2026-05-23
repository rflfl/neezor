---
status: pending
title: Domain/Notifications + Onboarding
type: backend
complexity: medium
dependencies:
  - task_09
---

# Task 13: Domain/Notifications + Onboarding

## Overview

Implement the notification service (Domain/Notifications) with mock WhatsApp driver (as per ADR-005) and build the 3-screen onboarding wizard. Notifications are triggered by appointment state changes via Laravel jobs.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Notifications directory structure: Contracts/, Drivers/, Jobs/
- MUST create NotificationServiceInterface: sendReminder(), sendConfirmation(), sendCancellation(), sendPackageAlert()
- MUST create MockWhatsAppDriver: implements interface, logs to Laravel log with structured fields
- MUST create notification jobs: SendReminderJob, SendConfirmationJob, SendCancellationJob, SendPackageAlertJob
- MUST dispatch jobs on appointment state transitions: reminder on creation (24h before), confirmation request, cancellation notice
- MUST implement package alert: dispatch when PackageSession has 1-2 remaining sessions or <7 days to expiration
- MUST use Laravel queue with retry (3 attempts, exponential backoff)
- MUST create Onboarding wizard: 3 screens as per PRD
  - Screen 1: Add professionals (name, email, phone, commission rate)
  - Screen 2: Add services (name, duration, price)
  - Screen 3: Configure agenda (default working hours per professional)
- MUST create `resources/js/Pages/Onboarding/` pages
- MUST create `resources/js/Pages/Dashboard/Setup.vue` that detects unconfigured tenants and redirects
- MUST write unit tests for notification service (assert log output)
- MUST write integration test: appointment creation dispatches reminder job
</requirements>

## Subtasks
- [ ] 13.1 Create Domain/Notifications/Contracts/NotificationServiceInterface.php
- [ ] 13.2 Create Domain/Notifications/Drivers/MockWhatsAppDriver.php
- [ ] 13.3 Create Domain/Notifications/Jobs/SendReminderJob.php
- [ ] 13.4 Create Domain/Notifications/Jobs/SendConfirmationJob.php
- [ ] 13.5 Create Domain/Notifications/Jobs/SendCancellationJob.php
- [ ] 13.6 Create Domain/Notifications/Jobs/SendPackageAlertJob.php
- [ ] 13.7 Register interface → mock driver binding in AppServiceProvider
- [ ] 13.8 Update AppointmentService: dispatch reminder job on appointment creation
- [ ] 13.9 Update AppointmentService: dispatch cancellation job on cancel
- [ ] 13.10 Implement package alert dispatch logic
- [ ] 13.11 Create Onboarding/Step1.vue: add professionals
- [ ] 13.12 Create Onboarding/Step2.vue: add services
- [ ] 13.13 Create Onboarding/Step3.vue: configure agenda
- [ ] 13.14 Create Onboarding/Index.vue: step container with navigation
- [ ] 13.15 Add Setup redirect check in dashboard middleware
- [ ] 13.16 Write unit tests: MockWhatsAppDriver log assertions
- [ ] 13.17 Write integration test: appointment triggers reminder job dispatch
- [ ] 13.18 Configure Laravel queue (database driver already scaffolded)

## Implementation Details

See ADR-005 for mock WhatsApp decision. See TechSpec "Integration Points" for notification interface. Structured log format per TechSpec "Monitoring and Observability". Onboarding wizard follows PRD "UX Considerations": 3 screens as a guided setup.

### Relevant Files
- `app/Domain/Notifications/Contracts/NotificationServiceInterface.php` — create
- `app/Domain/Notifications/Drivers/MockWhatsAppDriver.php` — create
- `app/Domain/Notifications/Jobs/` — create 4 job files
- `app/Domain/Scheduling/Services/AppointmentService.php` — modify: dispatch jobs
- `app/Providers/AppServiceProvider.php` — modify: register bindings
- `resources/js/Pages/Onboarding/Index.vue` — create
- `resources/js/Pages/Onboarding/Step1.vue` — create
- `resources/js/Pages/Onboarding/Step2.vue` — create
- `resources/js/Pages/Onboarding/Step3.vue` — create
- `resources/js/Pages/Dashboard/Setup.vue` — create
- `app/Http/Middleware/HandleInertiaRequests.php` — modify: setup check
- `routes/web.php` — add onboarding routes

### Dependent Files
- task_09: depends on dashboard structure

### Related ADRs
- [ADR-005: Integração WhatsApp — Mock no MVP](../adrs/adr-005.md)