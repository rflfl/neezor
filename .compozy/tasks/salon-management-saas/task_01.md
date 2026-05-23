---
status: completed
title: Multi-tenancy Foundation
type: backend
complexity: high
dependencies: []
---

# Task 1: Multi-tenancy Foundation

## Overview

Establish the multi-tenancy infrastructure that isolates every piece of data by tenant. This is the security foundation — a leak here is a critical vulnerability. All subsequent tasks depend on this being correctly implemented.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST add `tenant_id` column to the `users` table via migration
- MUST create `Tenant` model with fields: name, slug, subscription_plan, status
- MUST create `TenantScope` global scope that filters all queries by current `tenant_id`
- MUST create `BelongsToTenant` trait that auto-applies `tenant_id` on model creation
- MUST create `ScopeTenantAware` trait for models that must be tenant-scoped
- MUST create `TenantContext` singleton to hold current tenant ID during request lifecycle
- MUST create `EnsureTenantIsSet` middleware that validates `tenant_id` on all authenticated routes
- MUST add unique composite index on `(tenant_id, ...)` for all multi-tenant tables per TechSpec
- MUST ensure all new migrations include `tenant_id` where appropriate
- MUST use `moneyphp/money` for all monetary operations (install if not present)
</requirements>

## Subtasks
- [x] 1.1 Create migration: `tenants` table (id, name, slug, subscription_plan, status, timestamps)
- [x] 1.2 Create Tenant Eloquent model
- [x] 1.3 Create migration: add `tenant_id` to `users` table, add role column (admin/professional/reception)
- [x] 1.4 Create `TenantScope` global scope in `App/Models/Scopes/`
- [x] 1.5 Create `BelongsToTenant` trait in `App/Models/Traits/`
- [x] 1.6 Create `ScopeTenantAware` trait in `App/Models/Traits/`
- [x] 1.7 Create `TenantContext` service in `App/Services/`
- [x] 1.8 Create `EnsureTenantIsSet` middleware in `App/Http/Middleware/`
- [x] 1.9 Update `config/database.php` for MySQL with utf8mb4/unicode_ci and InnoDB engine
- [x] 1.10 Update `HandleInertiaRequests` middleware to share tenant data
- [x] 1.11 Update Jetstream `CreateNewUser` action to accept `tenant_id`
- [x] 1.12 Install `moneyphp/money` package if not already installed
- [x] 1.13 Create base test trait `PerformsTenantTests` for test setup
- [x] 1.14 Write unit and integration tests

## Implementation Details

See TechSpec "System Architecture" section for the tenant scoping flow. See ADR-002 for the Global Scope implementation decision.

### Relevant Files
- `app/Models/User.php` — modify: add tenant_id, role, BelongsToTenant trait
- `app/Http/Middleware/EnsureTenantIsSet.php` — create: validate tenant in request
- `app/Http/Middleware/HandleInertiaRequests.php` — modify: share tenant in page props
- `app/Providers/AppServiceProvider.php` — modify: register TenantContext singleton
- `database/migrations/` — new: tenants table, users.tenant_id
- `tests/TestCase.php` — modify: add PerformsTenantTests trait

### Dependent Files
- All subsequent tasks depend on this task's models and middleware

### Related ADRs
- [ADR-002: Estratégia de Multi-Tenancy — tenant_id com Global Scopes](../adrs/adr-002.md) — defines the Global Scope + trait approach
- [ADR-003: Banco de Dados — MySQL](../adrs/adr-003.md) — MySQL 8.0+ configuration

## Deliverables
- Working Tenant model with CRUD (for onboarding)
- All models use BelongsToTenant + ScopeTenantAware traits
- EnsureTenantIsSet middleware registered on all dashboard routes
- Unit tests covering scope isolation
- Integration test: Tenant A cannot access Tenant B data
- Test coverage >=80%

## Tests
- Unit tests:
  - [ ] BelongsToTenant trait auto-sets tenant_id on creating event
  - [ ] TenantScope filters query by current tenant_id
  - [ ] TenantContext singleton can be set and retrieved
  - [ ] EnsureTenantIsSet returns 403 when no tenant in session
- Integration tests:
  - [ ] User from Tenant A queries only Tenant A's users
  - [ ] Raw query bypass detection (document that raw queries need manual filtering)
- Test coverage target: >=80%
- All tests must pass