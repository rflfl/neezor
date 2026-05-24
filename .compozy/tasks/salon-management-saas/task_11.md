---
status: completed
title: "Frontend: Cashbox + Commissions + DRE"
type: frontend
complexity: medium
dependencies:
  - task_09
---

# Task 11: Frontend: Cashbox + Commissions + DRE

## Overview

Build Vue/Inertia pages for the financial views: daily cashbox management, commission tracking, and monthly DRE report. These are the interfaces that deliver the core value proposition ("the salon that actually knows its profit").

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `resources/js/Pages/Dashboard/Cashbox.vue` — daily cashbox view: opening balance, entries list, expenses list, closing balance, balance reconciliation indicator
- MUST implement: open cashbox (if not open), record entry form, record expense form, close cashbox button
- MUST show: visual indicator (green=balanced, red=discrepancy) for closing
- MUST create `resources/js/Pages/Dashboard/Commissions/Index.vue` — commission overview: per-professional accumulated commissions, payment status, mark as paid button
- MUST create `resources/js/Pages/Dashboard/Commissions/Professional.vue` — per-professional detail: appointments in period, commission breakdown, payment history
- MUST create `resources/js/Pages/Dashboard/Dre.vue` — monthly DRE: revenue, commissions, expenses, net profit, margin % — displayed as table with visual emphasis on profit
- MUST add route handlers for all financial pages
- MUST write tests for financial flows
</requirements>

## Subtasks
- [x] 11.1 Create Cashbox.vue: cashbox day view with open/close flow
- [x] 11.2 Implement record entry form (amount, method, note, appointment link)
- [x] 11.3 Implement record expense form (amount, category, note)
- [x] 11.4 Implement close cashbox with reconciliation check
- [x] 11.5 Create Commissions/Index.vue: overview by professional
- [x] 11.6 Create Commissions/Professional.vue: per-professional detail
- [x] 11.7 Implement mark as paid functionality
- [x] 11.8 Create Dre.vue: monthly P&L table
- [x] 11.9 Add route handlers
- [x] 11.10 Write tests: open cashbox → record entry → close
- [x] 11.11 Write tests: view commissions
- [x] 11.12 Write tests: view DRE

## Implementation Details

Financial pages require clear visual hierarchy. Use Tailwind for data tables with monetary formatting. DRE page should emphasize the profit figure (largest, boldest). Cashbox reconciliation indicator should be immediately visible.

### Relevant Files
- `resources/js/Pages/Dashboard/Cashbox.vue` — created
- `resources/js/Pages/Dashboard/Commissions/Index.vue` — created
- `resources/js/Pages/Dashboard/Commissions/Professional.vue` — created
- `resources/js/Pages/Dashboard/Dre.vue` — created
- `resources/js/Pages/Dashboard/Expenses/Index.vue` — created
- `app/Http/Controllers/Dashboard/CashboxController.php` — created (Inertia Response)
- `app/Http/Controllers/Dashboard/CommissionController.php` — created (Inertia Response)
- `app/Http/Controllers/Dashboard/ExpenseController.php` — modified (Inertia Response)
- `app/Domain/Commission/Models/CommissionPayment.php` — added tenant_id + BelongsToTenant trait
- `database/migrations/2026_05_24_180011_create_commission_payments_table.php` — added tenant_id column
- `app/Domain/Commission/Services/CommissionService.php` — recordPayment now sets tenant_id
- `resources/js/Layouts/AppLayout.vue` — nav links for Caixa, Comissões, DRE, Despesas

### Dependent Files
- task_09: depends on base dashboard page patterns

### Verification Evidence
- Build: Successful (npm run build)
- Routes: 6 cashbox, 4 commission, 1 DRE, 6 expenses routes registered
- Tests: 13 feature tests in tests/Feature/Dashboard/FinancialPagesTest.php
- Nav links: Added to AppLayout for all 4 financial pages
- CommissionPayment: Added tenant_id column + migration fix (pre-existing DB issue unrelated to this task)