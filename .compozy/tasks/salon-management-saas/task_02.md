---
status: pending
title: User + Professional Models
type: backend
complexity: medium
dependencies:
  - task_01
---

# Task 2: User + Professional Models

## Overview

Extend the Jetstream User model with tenant awareness and role support, and create the Professional entity. Professionals are the staff who perform services and receive commissions.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST modify User model to use ScopeTenantAware trait and expose tenant_id, role
- MUST create Professional model with: tenant_id, name, email, phone, commission_rate, is_active
- MUST create User → Professional relationship (one-to-one or one-to-many)
- MUST add migration for `professionals` table with proper indexes
- MUST add composite unique index on (tenant_id, email) for professionals
- MUST create ProfessionalController with CRUD operations (orchestration only)
- MUST add route: `/dashboard/professionals` (GET/POST)
- MUST add route: `/dashboard/professionals/{id}` (GET/PUT/DELETE)
- MUST expose professionals in Inertia page props
- MUST create ProfessionalFactory for tests
- MUST write unit and feature tests
</requirements>

## Subtasks
- [ ] 2.1 Migration: create `professionals` table
- [ ] 2.2 Create Professional Eloquent model with tenant traits
- [ ] 2.3 Update User model: add tenant_id, role, BelongsToTenant, ScopeTenantAware
- [ ] 2.4 Add User → Professional relationship
- [ ] 2.5 Create migration: update users table with tenant_id and role
- [ ] 2.6 Create ProfessionalController (thin, delegation to domain)
- [ ] 2.7 Register routes for professionals CRUD
- [ ] 2.8 Create ProfessionalFactory
- [ ] 2.9 Write unit tests for Professional model
- [ ] 2.10 Write feature tests for CRUD endpoints

## Implementation Details

See TechSpec "Data Models" section for Professional entity fields. See TechSpec "API Endpoints" for routes.

### Relevant Files
- `app/Models/User.php` — modify: add tenant_id, role, relationship to Professional
- `app/Models/Professional.php` — create: tenant-scoped professional model
- `app/Http/Controllers/Dashboard/ProfessionalController.php` — create
- `routes/web.php` — add professional routes
- `database/factories/ProfessionalFactory.php` — create
- `database/migrations/` — create professionals table, update users table

### Dependent Files
- task_01: depends on BelongsToTenant trait and TenantContext
- task_04+: depends on Professional model for scheduling

### Related ADRs
- [ADR-002: Estratégia de Multi-Tenancy](../adrs/adr-002.md)