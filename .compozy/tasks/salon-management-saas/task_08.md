---
status: completed
title: Domain/Expenses
type: backend
complexity: medium
dependencies:
  - task_06
---

# Task 8: Domain/Expenses

## Overview

Build the expenses domain (Domain/Expenses) and DRE (Demonstrativo de Resultado) calculation. This domain handles fixed and variable expenses, categorization, and the monthly P&L statement that shows the salon owner their actual profit.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Expenses directory structure: Models/, Services/, Contracts/
- MUST create Expense model: tenant_id, amount, category_id, is_recurring, description, due_date
- MUST reuse ExpenseCategory from task_06 (not recreate)
- MUST implement DreService: calculateMonthlyReport(tenantId, year, month)
- MUST calculate DRE: Total Revenue - Commission Costs - Total Expenses = Net Profit
- MUST calculate profit margin percentage
- MUST support recurring expenses: auto-generate entries from template
- MUST create ExpenseController with CRUD
- MUST add routes: `/dashboard/expenses` (GET/POST), `/dashboard/expenses/{id}` (GET/PUT/DELETE), `/dashboard/dre` (GET)
- MUST use moneyphp/money for all monetary operations
- MUST write unit tests for DRE formula
- MUST write integration tests for expense CRUD and DRE report
</requirements>

## Subtasks
- [x] 8.1 Migration: create `expenses` table (category_id references expense_categories)
- [x] 8.2 Create Domain/Expenses/Models/Expense.php
- [x] 8.3 Create Domain/Expenses/Contracts/DreServiceInterface.php
- [x] 8.4 Create Domain/Expenses/Services/DreService.php
- [x] 8.5 Create ExpenseController
- [x] 8.6 Register expense routes and DRE route
- [x] 8.7 Create ExpenseFactory
- [x] 8.8 Write unit tests: DRE calculation with revenue, commissions, expenses
- [x] 8.9 Write unit tests: profit margin calculation
- [x] 8.10 Write unit tests: zero revenue edge case
- [x] 8.11 Write integration test: expense CRUD
- [x] 8.12 Write integration test: DRE report for known month

## Implementation Details

See TechSpec "Data Models" for Expense entity. See PRD "F6: Despesas e DRE" for DRE formula: Receitas - Comissões - Despesas = Lucro. Revenue comes from CashMovement entries of type "entry" for the month. Commissions come from CommissionRun records.

### Relevant Files
- `app/Domain/Expenses/Models/Expense.php` — create
- `app/Domain/Expenses/Services/DreService.php` — create
- `app/Domain/Expenses/Contracts/DreServiceInterface.php` — create
- `app/Domain/Expenses/Controllers/ExpenseController.php` — create
- `database/migrations/` — create expenses table

### Dependent Files
- task_06: depends on ExpenseCategory model
- task_07: depends on CommissionRun for DRE calculation

### Related ADRs