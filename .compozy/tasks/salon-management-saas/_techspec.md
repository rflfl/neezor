# TechSpec — Neezor (Salon Management SaaS)

## Executive Summary

Neezor is a multi-tenant SaaS application for salon management built on Laravel 13 + Vue 3 + Inertia. The MVP delivers a full salon management system: scheduling, flexible service packages, client management, daily cashbox, commission tracking, monthly expenses/DRE, and WhatsApp notifications. Multi-tenancy is implemented via `tenant_id` on all tables with Eloquent Global Scopes. The primary technical trade-off is a custom scheduling system built from scratch (no external package) in exchange for domain-specific control, at the cost of more implementation code and rigorous testing requirements to prevent double-booking.

---

## System Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (SPA)                        │
│  Vue 3 + Inertia.js + Tailwind CSS                       │
│  Pages: Dashboard, Calendar, Clients, Cashbox, etc.       │
│  Booking Pages: public booking flow (token-auth)         │
└─────────────────────────────────────────────────────────┘
                              │
                    Inertia HTTP Bridge
                              │
┌─────────────────────────────────────────────────────────┐
│              Laravel 13 Application                     │
│                                                          │
│  Http/Layer: Controllers (orchestration only)            │
│  Domain/Layer: Business logic lives here                │
│    Domain/Scheduling    — slots, availability, conflicts │
│    Domain/Services      — catalog, packages, sessions    │
│    Domain/Customers    — clients, history                │
│    Domain/Cashbox      — daily cash, movements          │
│    Domain/Commission   — rules, rateio, payments         │
│    Domain/Expenses     — categories, DRE                 │
│    Domain/Notifications — WhatsApp (mock)                │
│    Domain/Tenancy       — tenant, tenant-aware scopes     │
│  Infrastructure/Layer: External integrations            │
│  Models/Layer: Eloquent models (persistence + relations) │
└─────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────┐
│                  MySQL 8.0+ Database                     │
│  All tables include tenant_id + indexes                  │
│  SQLite in-memory for tests                             │
└─────────────────────────────────────────────────────────┘
```

**Data Flow:**

1. User interacts with Vue SPA (Inertia)
2. Inertia request → Laravel Controller
3. Controller → Domain Service (business logic)
4. Domain Service → Eloquent Model → MySQL
5. Response serialized → Inertia page props → Vue component

**Tenant Scoping Flow:**

1. User authenticates (Jetstream/Sanctum) → `Auth::user()` with `tenant_id`
2. Middleware `EnsureTenantIsSet` attaches `TenantContext::setCurrent($tenantId)`
3. All Eloquent queries on tenant-aware models automatically filter by `tenant_id` via Global Scope
4. Domain services receive `tenant_id` implicitly from scoped models

---

## Implementation Design

### Core Interfaces

**Domain Services (key contracts):**

```php
// Domain/Scheduling/Contracts/AvailabilityServiceInterface.php
interface AvailabilityServiceInterface
{
    public function getAvailableSlots(
        int $tenantId,
        int $professionalId,
        int $serviceId,
        DateTimeInterface $date
    ): Collection;

    public function isSlotAvailable(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end
    ): bool;

    public function bookSlot(
        int $tenantId,
        int $appointmentId,
        Carbon $start,
        Carbon $end
    ): Appointment;
}
```

```php
// Domain/Commission/Contracts/CommissionServiceInterface.php
interface CommissionServiceInterface
{
    public function calculateForAppointment(Appointment $appointment): Money;

    public function calculateForPeriod(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end
    ): Collection;

    public function recordPayment(
        int $tenantId,
        int $professionalId,
        Money $amount,
        Carbon $paidAt,
        ?string $note = null
    ): CommissionPayment;
}
```

```php
// Domain/Cashbox/Contracts/CashboxServiceInterface.php
interface CashboxServiceInterface
{
    public function open(int $tenantId, Carbon $date, Money $initialBalance): CashboxDay;

    public function recordEntry(
        int $tenantId,
        int $cashboxDayId,
        Money $amount,
        PaymentMethod $method,
        ?int $appointmentId = null,
        ?string $note = null
    ): CashMovement;

    public function recordExpense(
        int $tenantId,
        int $cashboxDayId,
        Money $amount,
        ExpenseCategory $category,
        ?string $note = null
    ): CashMovement;

    public function close(int $tenantId, Carbon $date, Money $expectedBalance): CashboxDay;
}
```

```php
// Domain/Notifications/Contracts/NotificationServiceInterface.php
interface NotificationServiceInterface
{
    public function sendReminder(int $appointmentId): void;
    public function sendConfirmation(int $appointmentId): void;
    public function sendCancellation(int $appointmentId, string $reason): void;
    public function sendPackageAlert(int $clientId, int $packageId): void;
}
```

### Data Models

**Core entities (all include `tenant_id` column + index):**

| Entity | Key Fields |
|---|---|
| `Tenant` | name, slug, subscription_plan, status |
| `Professional` | tenant_id, name, email, phone, commission_rate, is_active |
| `Service` | tenant_id, name, duration_minutes, price, is_active |
| `Package` | tenant_id, name, price, valid_until_days |
| `PackageService` | package_id, service_id, session_count |
| `Client` | tenant_id, name, phone, email, notes |
| `Appointment` | tenant_id, professional_id, client_id, service_id, package_id, start_at, end_at, status, price |
| `PackageSession` | client_id, package_id, service_id, appointment_id, used_at, expires_at |
| `CashboxDay` | tenant_id, date, opening_balance, closing_balance, status |
| `CashMovement` | tenant_id, cashbox_day_id, type (entry/expense), amount, method, appointment_id, category_id, note, created_by |
| `CommissionRun` | tenant_id, professional_id, period_start, period_end, total_gross, total_commission, status |
| `CommissionPayment` | commission_run_id, amount, paid_at, note, recorded_by |
| `Expense` | tenant_id, amount, category_id, is_recurring, description, due_date |
| `BookingToken` | tenant_id, token, expires_at, created_at |
| `User` | tenant_id, name, email, password, role (admin/professional/reception) |

**Primary key strategy:** UUID v4 for `Tenant.slug` (public-facing); auto-increment for all internal IDs.

**Index strategy:**

- Composite unique index on `(tenant_id, professional_id, start_at)` for appointments — prevents double-booking at DB level
- Index on `(tenant_id, status)` for all major entities
- Index on `(tenant_id, client_id)` for appointments and package sessions

### API Endpoints

**Internal (Inertia — SPA):**

| Method | Path | Description |
|---|---|---|
| GET | `/dashboard` | Main dashboard |
| GET/POST | `/dashboard/calendar` | Calendar view + appointment CRUD |
| GET | `/dashboard/calendar/professional/{id}` | Professional schedule |
| GET/POST | `/dashboard/clients` | Client list + CRUD |
| GET | `/dashboard/clients/{id}` | Client detail + history |
| GET/POST | `/dashboard/services` | Service catalog CRUD |
| GET/POST | `/dashboard/packages` | Package CRUD |
| GET/POST | `/dashboard/packages/{id}/sessions` | Package session management |
| GET/POST | `/dashboard/cashbox` | Daily cashbox view + movements |
| POST | `/dashboard/cashbox/open` | Open daily cashbox |
| POST | `/dashboard/cashbox/entry` | Record cash entry |
| POST | `/dashboard/cashbox/expense` | Record expense |
| POST | `/dashboard/cashbox/close` | Close daily cashbox |
| GET | `/dashboard/commissions` | Commission overview |
| GET | `/dashboard/commissions/professional/{id}` | Per-professional commission |
| POST | `/dashboard/commissions/pay` | Mark commission as paid |
| GET | `/dashboard/expenses` | Expense list + CRUD |
| GET | `/dashboard/dre` | Monthly DRE report |

**Public (Booking portal):**

| Method | Path | Description |
|---|---|---|
| GET | `/booking/{tenant_slug}?token=` | Booking landing (public token) |
| GET | `/booking/{tenant_slug}/services` | Available services for tenant |
| GET | `/booking/{tenant_slug}/slots` | Available time slots |
| POST | `/booking/{tenant_slug}/appointments` | Create appointment |

---

## Integration Points

### WhatsApp Business API (Mock — MVP)

- **Purpose**: Send reminders, confirmations, cancellation notices, package alerts
- **Implementation**: `NotificationServiceInterface` with `MockWhatsAppDriver` (logs to Laravel log)
- **Driver swap**: Replace `MockWhatsAppDriver` with `WhatsAppBusinessDriver` for production (Z-API or Twilio)
- **Trigger**: Laravel Jobs dispatched after appointment state changes
- **Retry**: 3 attempts with exponential backoff via Laravel queue

### No other external integrations in MVP phase.

---

## Impact Analysis

| Component | Impact Type | Description and Risk | Required Action |
|---|---|---|---|
| `Domain/Tenancy` | new | Introduce `tenant_id` scoping, global scopes, middleware. Critical for security. | Build first; test thoroughly |
| `Domain/Scheduling` | new | Custom availability engine. Risk of double-booking bugs. | 100% test coverage; DB constraint |
| `Domain/Services` + `Packages` | new | Package entity with `PackageService` pivot. Session debiting logic. | Unit tests for session debiting |
| `Domain/Customers` | new | Client entity with history relationship. Inactive detection. | Query optimization for large client lists |
| `Domain/Cashbox` | new | Cash flow engine with entry/expense separation. Balance reconciliation. | Reconciliation tests |
| `Domain/Commission` | new | Commission calculation per appointment + period aggregation. | 100% test coverage (PRD requirement) |
| `Domain/Expenses` | new | Expense categories + DRE calculation. | DRE formula tests |
| `Domain/Notifications` | new | Mock WhatsApp service. Interface ready for real driver. | Interface contract tests |
| `User` (Jetstream) | modified | Add `tenant_id`, `role`. Remove default Jetstream user-only model. | Migrate existing users; preserve auth |
| Frontend SPA | modified | Add booking pages, cashbox UI, commission UI. New Inertia pages. | Component reuse from Jetstream scaffold |
| Middleware | new | `EnsureTenantIsSet` + `PublicBookingToken` | Integration tests for auth flows |
| Migrations | new | All new tables + `tenant_id` on existing tables | Rollback strategy |

---

## Testing Approach

### Unit Tests

**Strategy:** Every public method in `Domain/*/Services/` gets unit tests. Use PHPUnit with in-memory SQLite.

**Critical scenarios:**

- `AvailabilityService`: double-booking prevention, buffer overlap, slot generation for all duration combinations
- `CommissionService`: percentage by service, percentage by professional, mixed appointments, zero commission edge case
- `CashboxService`: opening/closing balance, entry/expense math, reconciliation mismatch
- `DreCalculator`: revenue aggregation, commission deduction, expense categorization, margin percentage
- `PackageSessionDebiter`: session count decrement, expiration check, service matching

**Mock requirements:**

- `NotificationServiceInterface` → `MockWhatsAppDriver` (assert log output)
- No external service mocks needed (mock-only MVP)

### Integration Tests

**Scope:** Test complete flows through Inertia feature tests.

**Key flows to test:**

1. Appointment creation → triggers cash entry → updates commission run
2. Package purchase → creates `PackageSession` records → debits on appointment
3. Cashbox open → entries → expense → close → reconciliation check
4. Booking portal appointment → visible in dashboard calendar
5. Multi-tenant isolation: Tenant A cannot see Tenant B's appointments

**Environment:** Uses `testing` database connection (SQLite in-memory).

---

## Development Sequencing

### Build Order

1. **Multi-tenancy foundation** — Tenant model, global scopes, `BelongsToTenant` trait, `EnsureTenantIsSet` middleware. No dependencies.
2. **User + Professional models** — Extend User with `tenant_id` + role. Professional model. Migration. Basic CRUD.
3. **Domain/Services + Domain/Customers** — Service catalog CRUD, Client CRUD, relationships.
4. **Domain/Scheduling** (iteration 1) — Appointment model, basic CRUD, availability slots, conflict check with DB constraint.
5. **Domain/Packages** — Package model, `PackageService` pivot, `PackageSession` debiting logic.
6. **Domain/Cashbox** — CashboxDay, CashMovement models. Open/close/day flow.
7. **Domain/Commission** — Commission calculation, `CommissionRun` aggregation, payment recording.
8. **Domain/Expenses** — Expense model, DRE calculation service.
9. **Frontend: Dashboard calendar** — Inertia pages for calendar + appointment CRUD.
10. **Frontend: Clients + Services + Packages** — CRUD pages.
11. **Frontend: Cashbox + Commissions + DRE** — Financial pages.
12. **Public booking portal** — Booking pages, `PublicBookingToken` middleware, booking flow.
13. **Domain/Notifications** — Mock WhatsApp service, job dispatching on appointment state changes.
14. **Onboarding wizard** — Setup flow (3 screens as per PRD).
15. **Docker setup** — docker-compose.yml for local dev and production-ready config.

**Dependencies:**

- Steps 2+ depend on step 1
- Steps 4+ depend on steps 2+3
- Steps 6+ depend on step 4
- Steps 7+8 depend on step 6
- Steps 9-14 depend on steps 1-8
- Step 15 can run in parallel after step 1

### Technical Dependencies

- **MySQL 8.0+** must be available for development and staging
- **Laravel 13** with Jetstream (already scaffolded)
- **PHP 8.3** (already met)
- All packages from `composer.json` and `package.json` are already installed

---

## Monitoring and Observability

### Key Metrics

- Response time: p95 < 500ms for all dashboard pages
- Cashbox reconciliation: count of mismatches per closed day
- Double-booking events: zero tolerance (monitored via DB unique constraint violations)
- WhatsApp notification failures: track via job failed attempts

### Log Events

Structured log fields for all financial operations:

```php
Log::info('cashbox.entry.recorded', [
    'tenant_id' => $tenantId,
    'amount' => $amount->getAmount(),
    'method' => $method->value,
    'cashbox_day_id' => $cashboxDayId,
    'appointment_id' => $appointmentId,
]);
```

```php
Log::info('commission.calculated', [
    'tenant_id' => $tenantId,
    'professional_id' => $professionalId,
    'appointment_id' => $appointmentId,
    'gross' => $gross->getAmount(),
    'commission' => $commission->getAmount(),
    'rate' => $rate,
]);
```

### Alerting Thresholds

- Queue failed jobs for notifications > 5 in 1 hour → alert on Slack
- Cashbox day closed with discrepancy > R$0.01 → alert on Slack
- API response p95 > 1s for 10 consecutive minutes → alert on Slack

---

## Technical Considerations

### Key Decisions

| Decision | Rationale | Trade-off |
|---|---|---|
| Global Scopes for tenant scoping | Simplicity, Laravel-native, automatic filter on all queries | Raw queries need manual tenant_id filter |
| MySQL 8.0+ | Dominant in Brazilian hosting market, broad tool support | Less feature-rich than PostgreSQL for analytics |
| Custom scheduling engine | Domain-specific logic not served by generic packages | More code, requires rigorous testing |
| Mock WhatsApp driver | Avoids blocking development on API setup | No real notifications in MVP |
| Same SPA for booking portal | Simplicity, code reuse, single deployment | Larger bundle, public pages in admin build |
| PHPUnit for all tests | Already scaffolded, sufficient for MVP scope | Vue component isolation requires additional setup |

### Known Risks

- **Risk**: Double-booking despite tests (availability logic edge cases are complex)
  - **Mitigation**: DB unique constraint on `(tenant_id, professional_id, start_at)` as last-resort guard
- **Risk**: Multi-tenant data leakage via raw queries
  - **Mitigação**: PHPStan rule to detect raw queries without tenant filter; code review gate
- **Risk**: Cashbox reconciliation discrepancy due to floating-point math
  - **Mitigation**: Use `moneyphp/money` for all monetary operations; store as integers (cents)
- **Risk**: WhatsApp mock driver hides message formatting bugs
  - **Mitigation**: Test mock driver log output for every notification type

---

## Architecture Decision Records

- [ADR-001: Escopo MVP — Abordagem "Financeiro Completo"](adrs/adr-001.md) — Include full financial stack (cashbox + commissions + expenses + DRE) in MVP launch
- [ADR-002: Estratégia de Multi-Tenancy — tenant_id com Global Scopes](adrs/adr-002.md) — Use Eloquent Global Scopes via traits for automatic tenant filtering
- [ADR-003: Banco de Dados — MySQL](adrs/adr-003.md) — Use MySQL 8.0+ as primary database
- [ADR-004: Sistema de Agenda — Custom Scheduler](adrs/adr-004.md) — Build custom availability engine from scratch instead of using existing packages
- [ADR-005: Integração WhatsApp — Mock no MVP](adrs/adr-005.md) — Mock WhatsApp notifications with structured logging; swap driver in next phase
- [ADR-006: Framework de Testes — PHPUnit](adrs/adr-006.md) — Use PHPUnit (already scaffolded) for all tests with SQLite in-memory for speed
- [ADR-007: Frontend de Auto-Agendamento — Mesma SPA com Páginas Públicas](adrs/adr-007.md) — Embed booking portal in same SPA with public token authentication