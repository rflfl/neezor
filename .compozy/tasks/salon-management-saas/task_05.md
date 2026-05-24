---
status: completed
title: Domain/Packages
type: backend
complexity: high
dependencies:
  - task_04
---

# Task 5: Domain/Packages

## Overview

Implement the flexible package system: packages contain multiple services, each with a session count. When a client books an appointment that uses a package, a session is debited. Packages have expiration dates. This domain is complex because it bridges scheduling (appointments), clients, and services.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create Domain/Packages directory structure: Models/, Services/, Contracts/
- MUST create Package model: tenant_id, name, price, valid_until_days
- MUST create PackageService pivot model: package_id, service_id, session_count
- MUST create PackageSession model: client_id, package_id, service_id, appointment_id, used_at, expires_at
- MUST implement PackageService with: createPackage(), addService(), calculateSessionsRemaining(), purchase()
- MUST implement session debiting: when appointment uses a package, find matching PackageSession, decrement count, set used_at
- MUST handle expiration: reject session use if current_date > expires_at
- MUST implement session reservation: reserve a session when client books (before completion)
- MUST create PackageController with CRUD
- MUST add routes: `/dashboard/packages` (GET/POST), `/dashboard/packages/{id}` (GET/PUT/DELETE), `/dashboard/packages/{id}/sessions` (GET)
- MUST update AppointmentService to detect and use package when client has active package for the service
- MUST create factories: PackageFactory, PackageServiceFactory, PackageSessionFactory
- MUST write unit tests for session debiting and expiration
- MUST write integration tests for package purchase → appointment → session debiting flow
</requirements>

## Subtasks
- [x] 5.1 Migration: create `packages` table
- [x] 5.2 Migration: create `package_service` pivot table
- [x] 5.3 Migration: create `package_sessions` table
- [x] 5.4 Create Domain/Packages/Models/Package.php
- [x] 5.5 Create Domain/Packages/Models/PackageService.php
- [x] 5.6 Create Domain/Packages/Models/PackageSession.php
- [x] 5.7 Create Domain/Packages/Contracts/PackageServiceInterface.php
- [x] 5.8 Create Domain/Packages/Services/PackageService.php
- [x] 5.9 Create PackageController
- [x] 5.10 Register package routes
- [x] 5.11 Add Package → Service many-to-many relationship
- [x] 5.12 Add PackageSession → Appointment relationship
- [x] 5.13 Create factories
- [x] 5.14 Write unit tests: session debiting logic
- [x] 5.15 Write unit tests: expiration check
- [x] 5.16 Write unit tests: sessions remaining calculation
- [x] 5.17 Write integration test: full package flow (purchase → book → session debited)

## Implementation Details

See TechSpec "Data Models" for Package, PackageService, PackageSession entities. See PRD "F2: Serviços e Pacotes" for behavior. Key complexity: session debiting must find the correct PackageSession (matching service, not expired, has remaining sessions) — this requires careful query logic.

### Relevant Files
- `app/Domain/Packages/Models/Package.php` — create
- `app/Domain/Packages/Models/PackageService.php` — create
- `app/Domain/Packages/Models/PackageSession.php` — create
- `app/Domain/Packages/Services/PackageService.php` — create
- `app/Domain/Packages/Contracts/PackageServiceInterface.php` — create
- `app/Domain/Packages/Controllers/PackageController.php` — create
- `app/Domain/Scheduling/Services/AppointmentService.php` — modify: use package sessions
- `database/migrations/` — create packages, package_service, package_sessions tables

### Dependent Files
- task_04: depends on Appointment model

### Related ADRs