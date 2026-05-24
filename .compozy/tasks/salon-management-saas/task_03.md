---
status: completed
title: Domain/Services + Domain/Customers
type: backend
complexity: medium
dependencies:
  - task_01
---

# Task 3: Domain/Services + Domain/Customers

## Overview

Create the Service catalog domain (Domain/Services) and Client management domain (Domain/Customers). Services define what the salon offers with pricing and duration. Clients are the salon customers with history tracking and inactive detection.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `Domain/Services` directory structure: Models/, Services/, Contracts/
- MUST create Service model: tenant_id, name, duration_minutes, price, is_active
- MUST create ServiceController with CRUD
- MUST add routes: `/dashboard/services` (GET/POST), `/dashboard/services/{id}` (GET/PUT/DELETE)
- MUST create `Domain/Customers` directory structure: Models/, Services/, Contracts/
- MUST create Client model: tenant_id, name, phone, email, notes
- MUST create ClientController with CRUD
- MUST add routes: `/dashboard/clients` (GET/POST), `/dashboard/clients/{id}` (GET/PUT/DELETE)
- MUST add relationship: Client → appointments (hasMany)
- MUST implement inactive client detection (no appointments in 60+ days)
- MUST create ServiceFactory and ClientFactory
- MUST write unit and feature tests
</requirements>

## Subtasks
- [x] 3.1 Migration: create `services` table
- [x] 3.2 Create Domain/Services/Models/Service.php with tenant traits
- [x] 3.3 Create Domain/Services/Services/ServiceService.php (domain logic)
- [x] 3.4 Create Domain/Services/Contracts/ServiceServiceInterface.php
- [x] 3.5 Create ServiceController
- [x] 3.6 Register service routes
- [x] 3.7 Migration: create `clients` table
- [x] 3.8 Create Domain/Customers/Models/Client.php with tenant traits
- [x] 3.9 Create Domain/Customers/Services/ClientService.php
- [x] 3.10 Create Domain/Customers/Contracts/ClientServiceInterface.php
- [x] 3.11 Create ClientController
- [x] 3.12 Register client routes
- [x] 3.13 Create ClientFactory
- [x] 3.14 Write unit tests for Service model and domain logic
- [x] 3.15 Write unit tests for Client model (including inactive detection)
- [x] 3.16 Write feature tests for both CRUD endpoints

## Implementation Details

See TechSpec "Data Models" for Service and Client entity fields. See PRD "F2: Serviços e Pacotes" and "F3: Clientes" for behavior requirements.

### Relevant Files
- `app/Domain/Services/Models/Service.php` — create
- `app/Domain/Services/Services/ServiceService.php` — create
- `app/Domain/Services/Contracts/ServiceServiceInterface.php` — create
- `app/Domain/Services/Controllers/ServiceController.php` — create
- `app/Domain/Customers/Models/Client.php` — create
- `app/Domain/Customers/Services/ClientService.php` — create
- `app/Domain/Customers/Contracts/ClientServiceInterface.php` — create
- `app/Domain/Customers/Controllers/ClientController.php` — create
- `routes/web.php` — add service and client routes
- `database/migrations/` — create services and clients tables
- `database/factories/ServiceFactory.php` — create
- `database/factories/ClientFactory.php` — create

### Dependent Files
- task_01: depends on BelongsToTenant trait
- task_04+: depends on Service and Client models for scheduling

### Related ADRs
- [ADR-002: Estratégia de Multi-Tenancy](../adrs/adr-002.md)