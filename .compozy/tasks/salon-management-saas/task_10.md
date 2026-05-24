---
status: completed
title: "Frontend: Clients + Services + Packages"
type: frontend
complexity: medium
dependencies:
  - task_09
---

# Task 10: Frontend: Clients + Services + Packages

## Overview

Build Vue/Inertia pages for client management, service catalog, and package management. These are CRUD-focused interfaces used by the salon owner to configure the business.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `resources/js/Pages/Dashboard/Clients/Index.vue` — client list with search, pagination
- MUST create `resources/js/Pages/Dashboard/Clients/Show.vue` — client detail: profile, appointment history, packages, inactive flag
- MUST create client create/edit form modal
- MUST create `resources/js/Pages/Dashboard/Services/Index.vue` — service catalog list
- MUST create service create/edit form
- MUST create `resources/js/Pages/Dashboard/Packages/Index.vue` — package list
- MUST create package create/edit form (add services with session counts)
- MUST create `resources/js/Pages/Dashboard/Packages/Sessions.vue` — package session management view
- MUST add route handlers for all pages
- MUST reuse Jetstream/Tailwind components
- MUST write E2E tests for CRUD flows
</requirements>

## Subtasks
- [x] 10.1 Create Clients/Index.vue: table, search, pagination
- [x] 10.2 Create Clients/Show.vue: profile + history + packages
- [x] 10.3 Create client form modal (create + edit)
- [x] 10.4 Create Services/Index.vue: catalog list
- [x] 10.5 Create service form (create + edit)
- [x] 10.6 Create Packages/Index.vue: package list with services preview
- [x] 10.7 Create package form: add multiple services with session counts
- [x] 10.8 Create Packages/Sessions.vue: sessions management per package
- [x] 10.9 Add route handlers
- [x] 10.10 Write E2E tests for client CRUD
- [x] 10.11 Write E2E tests for service CRUD
- [x] 10.12 Write E2E tests for package CRUD

## Implementation Details

Reuse component patterns from Jetstream scaffold. Use Tailwind for responsive tables and forms.

### Relevant Files
- `resources/js/Pages/Dashboard/Clients/Index.vue` — create ✓
- `resources/js/Pages/Dashboard/Clients/Show.vue` — create ✓
- `resources/js/Pages/Dashboard/Services/Index.vue` — create ✓
- `resources/js/Pages/Dashboard/Packages/Index.vue` — create ✓
- `resources/js/Pages/Dashboard/Packages/Sessions.vue` — create ✓
- `app/Http/Controllers/Dashboard/ClientController.php` — modified ✓
- `app/Http/Controllers/Dashboard/ServiceController.php` — modified if needed ✓
- `app/Http/Controllers/Dashboard/PackageController.php` — modified ✓

### New Components Created
- `resources/js/Components/ClientFormModal.vue` — client create/edit modal
- `resources/js/Components/ServiceFormModal.vue` — service create/edit modal
- `resources/js/Components/PackageFormModal.vue` — package create/edit modal with services

### New E2E Tests Created
- `tests/Browser/ClientCrudTest.php` — client CRUD flow tests
- `tests/Browser/ServiceCrudTest.php` — service CRUD flow tests
- `tests/Browser/PackageCrudTest.php` — package CRUD flow tests

### Dependent Files
- task_09: depends on Calendar page patterns ✓

### Related ADRs
- ADR-001: MVP Scope
- ADR-002: Multi-tenancy Strategy