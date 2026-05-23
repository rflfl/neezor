---
status: pending
title: Domain/Commission
type: backend
complexity: high
dependencies:
  - task_06
---

# Task 7: Domain/Commission

## Overview

Build the commission domain (Domain/Commission). This is the most financially sensitive domain: calculating commissions per appointment, aggregating by professional and period, and recording payments. The PRD requires 100% test coverage for this domain.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Commission directory structure: Models/, Services/, Contracts/
- MUST create CommissionRun model: tenant_id, professional_id, period_start, period_end, total_gross, total_commission, status
- MUST create CommissionPayment model: commission_run_id, amount, paid_at, note, recorded_by
- MUST implement CommissionService: calculateForAppointment(), calculateForPeriod(), recordPayment()
- MUST support service-specific commission rates (ProfessionalServiceCommission model)
- MUST calculate: gross = appointment price, commission = gross × rate
- MUST aggregate by professional and period (weekly/monthly)
- MUST support manual adjustments with mandatory note + recorded_by
- MUST create CommissionController
- MUST add routes: `/dashboard/commissions` (GET), `/dashboard/commissions/professional/{id}` (GET), `/dashboard/commissions/pay` (POST)
- MUST use moneyphp/money for all monetary operations
- MUST write unit tests covering all calculation scenarios (PRD requirement: 100% coverage)
- MUST write integration tests for commission flows
</requirements>

## Subtasks
- [ ] 7.1 Migration: create `commission_runs` table
- [ ] 7.2 Migration: create `commission_payments` table
- [ ] 7.3 Migration: create `professional_service_commissions` table
- [ ] 7.4 Create Domain/Commission/Models/CommissionRun.php
- [ ] 7.5 Create Domain/Commission/Models/CommissionPayment.php
- [ ] 7.6 Create Domain/Commission/Models/ProfessionalServiceCommission.php
- [ ] 7.7 Create Domain/Commission/Contracts/CommissionServiceInterface.php
- [ ] 7.8 Create Domain/Commission/Services/CommissionService.php
- [ ] 7.9 Create CommissionController
- [ ] 7.10 Register commission routes
- [ ] 7.11 Update AppointmentService: trigger commission calculation on completion
- [ ] 7.12 Create factories
- [ ] 7.13 Write unit tests: calculateForAppointment with default rate
- [ ] 7.14 Write unit tests: calculateForAppointment with service-specific rate
- [ ] 7.15 Write unit tests: calculateForPeriod aggregation
- [ ] 7.16 Write unit tests: recordPayment with note
- [ ] 7.17 Write unit tests: manual adjustment with mandatory note
- [ ] 7.18 Write unit tests: zero commission edge case
- [ ] 7.19 Write integration test: appointment → commission calculated
- [ ] 7.20 Write integration test: pay commission → payment recorded
- [ ] 7.21 Coverage: achieve 100% on CommissionService public methods

## Implementation Details

See TechSpec "Core Interfaces" for CommissionServiceInterface contract. See TechSpec "Data Models" for CommissionRun and CommissionPayment entities. See PRD "F5: Comissões" for behavior. Critical requirement: 100% test coverage on public methods.

### Relevant Files
- `app/Domain/Commission/Models/CommissionRun.php` — create
- `app/Domain/Commission/Models/CommissionPayment.php` — create
- `app/Domain/Commission/Models/ProfessionalServiceCommission.php` — create
- `app/Domain/Commission/Services/CommissionService.php` — create
- `app/Domain/Commission/Contracts/CommissionServiceInterface.php` — create
- `app/Domain/Commission/Controllers/CommissionController.php` — create
- `app/Domain/Scheduling/Services/AppointmentService.php` — modify: trigger commission
- `database/migrations/` — create commission tables

### Dependent Files
- task_06: depends on CashMovement model for financial context

### Related ADRs