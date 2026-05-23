---
status: pending
title: Domain/Cashbox
type: backend
complexity: high
dependencies:
  - task_04
---

# Task 6: Domain/Cashbox

## Overview

Build the daily cashbox engine (Domain/Cashbox). This is the financial core of the MVP: opening a cash drawer each day, recording entries (receipts from appointments) and expenses, and closing the day with reconciliation. Money operations must use moneyphp/money to avoid floating-point errors.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Cashbox directory structure: Models/, Services/, Contracts/, Enums/
- MUST create CashboxDay model: tenant_id, date, opening_balance, closing_balance, status (open/closed)
- MUST create CashMovement model: tenant_id, cashbox_day_id, type (entry/expense), amount, method, appointment_id (nullable), category_id (nullable), note, created_by
- MUST create PaymentMethod enum: money, credit_card, debit_card, pix, transfer
- MUST create ExpenseCategory model (for expenses): tenant_id, name, type (fixed/variable)
- MUST implement CashboxService: open(), recordEntry(), recordExpense(), close()
- MUST implement reconciliation: closing balance must equal opening + entries - expenses
- MUST detect discrepancy: if expected != actual, flag the difference
- MUST create CashboxController with CRUD
- MUST add routes: `/dashboard/cashbox` (GET/POST), `/dashboard/cashbox/open` (POST), `/dashboard/cashbox/entry` (POST), `/dashboard/cashbox/expense` (POST), `/dashboard/cashbox/close` (POST)
- MUST update AppointmentService: when appointment status → completed, automatically create cash entry
- MUST use moneyphp/money for all amounts (store as cents/integer)
- MUST write unit tests for reconciliation logic
- MUST write integration tests for complete cashbox day flow
</requirements>

## Subtasks
- [ ] 6.1 Migration: create `expense_categories` table
- [ ] 6.2 Migration: create `cashbox_days` table
- [ ] 6.3 Migration: create `cash_movements` table
- [ ] 6.4 Create Domain/Cashbox/Enums/PaymentMethod.php
- [ ] 6.5 Create Domain/Cashbox/Enums/CashboxStatus.php
- [ ] 6.6 Create Domain/Cashbox/Models/CashboxDay.php
- [ ] 6.7 Create Domain/Cashbox/Models/CashMovement.php
- [ ] 6.8 Create Domain/Cashbox/Models/ExpenseCategory.php
- [ ] 6.9 Create Domain/Cashbox/Contracts/CashboxServiceInterface.php
- [ ] 6.10 Create Domain/Cashbox/Services/CashboxService.php
- [ ] 6.11 Create CashboxController
- [ ] 6.12 Register cashbox routes
- [ ] 6.13 Update AppointmentService: auto-create cash entry on completion
- [ ] 6.14 Create factories
- [ ] 6.15 Write unit tests: opening balance, recording entries, expenses
- [ ] 6.16 Write unit tests: closing and reconciliation (match and mismatch)
- [ ] 6.17 Write integration test: open → entries → expense → close flow
- [ ] 6.18 Write integration test: appointment completion triggers cash entry

## Implementation Details

See TechSpec "Core Interfaces" for CashboxServiceInterface contract. See TechSpec "Data Models" for CashboxDay and CashMovement entities. See TechSpec "Monitoring and Observability" for structured log events. See ADR-006 for PHPUnit decision.

### Relevant Files
- `app/Domain/Cashbox/Models/CashboxDay.php` — create
- `app/Domain/Cashbox/Models/CashMovement.php` — create
- `app/Domain/Cashbox/Models/ExpenseCategory.php` — create
- `app/Domain/Cashbox/Services/CashboxService.php` — create
- `app/Domain/Cashbox/Contracts/CashboxServiceInterface.php` — create
- `app/Domain/Cashbox/Controllers/CashboxController.php` — create
- `app/Domain/Scheduling/Services/AppointmentService.php` — modify: trigger cash entry
- `database/migrations/` — create cashbox tables

### Dependent Files
- task_04: depends on Appointment model
- task_07+: depends on CashMovement model for commissions

### Related ADRs