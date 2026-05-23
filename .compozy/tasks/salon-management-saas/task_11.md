---
status: pending
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
- MUST create `resources/js/Pages/Dashboard/Commissions.vue` — commission overview: per-professional accumulated commissions, payment status, mark as paid button
- MUST create `resources/js/Pages/Dashboard/Commissions/Professional.vue` — per-professional detail: appointments in period, commission breakdown, payment history
- MUST create `resources/js/Pages/Dashboard/Dre.vue` — monthly DRE: revenue, commissions, expenses, net profit, margin % — displayed as table with visual emphasis on profit
- MUST add route handlers for all financial pages
- MUST write E2E tests for financial flows
</requirements>

## Subtasks
- [ ] 11.1 Create Cashbox.vue: cashbox day view with open/close flow
- [ ] 11.2 Implement record entry form (amount, method, note, appointment link)
- [ ] 11.3 Implement record expense form (amount, category, note)
- [ ] 11.4 Implement close cashbox with reconciliation check
- [ ] 11.5 Create Commissions.vue: overview by professional
- [ ] 11.6 Create Commissions/Professional.vue: per-professional detail
- [ ] 11.7 Implement mark as paid functionality
- [ ] 11.8 Create Dre.vue: monthly P&L table
- [ ] 11.9 Add route handlers
- [ ] 11.10 Write E2E tests: open cashbox → record entry → close
- [ ] 11.11 Write E2E tests: view commissions
- [ ] 11.12 Write E2E tests: view DRE

## Implementation Details

Financial pages require clear visual hierarchy. Use Tailwind for data tables with monetary formatting. DRE page should emphasize the profit figure (largest, boldest). Cashbox reconciliation indicator should be immediately visible.

### Relevant Files
- `resources/js/Pages/Dashboard/Cashbox.vue` — create
- `resources/js/Pages/Dashboard/Commissions.vue` — create
- `resources/js/Pages/Dashboard/Commissions/Professional.vue` — create
- `resources/js/Pages/Dashboard/Dre.vue` — create
- `app/Http/Controllers/Dashboard/CashboxController.php` — modify
- `app/Http/Controllers/Dashboard/CommissionController.php` — modify
- `app/Http/Controllers/Dashboard/ExpenseController.php` — modify

### Dependent Files
- task_09: depends on base dashboard page patterns

### Related ADRs