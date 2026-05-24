---
status: pending
file: app/Domain/Packages/Models/PackageSession.php
line: 1
severity: critical
author: claude-code
provider_ref:
---

# Issue 007: PackageSession Missing Multi-Tenancy Traits

## Review Comment

The `package_sessions` table has a `tenant_id` column in its migration, but `PackageSession` model does not use `BelongsToTenant` or `ScopeTenantAware` traits. All queries on this model silently bypass multi-tenancy filtering, potentially returning sessions from other tenants. This is especially dangerous in the `debitSessionForAppointment()` flow.

**Fix:** Add the tenant traits to `PackageSession`:

```php
class PackageSession extends Model
{
    use BelongsToTenant, ScopeTenantAware;
    // ...
}
```

Also verify that `ProfessionalServiceCommission`, `CommissionRun`, and `CommissionPayment` models have the proper tenant traits — `CommissionRun::getPayments()` at line 55-59 uses `withoutGlobalScopes()` which bypasses all scoping.

## Triage

- Decision: `UNREVIEWED`
- Notes: