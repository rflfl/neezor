---
status: pending
file: app/Services/TenantContext.php
line: 1
severity: critical
author: claude-code
provider_ref:
---

# Issue 002: Static TenantContext Causes Request Contamination

## Review Comment

`TenantContext` uses static properties to hold the current tenant ID across the request lifecycle. In Laravel Octane or other long-running process environments (Swoole, RoadRunner), a previous request's tenant_id can leak into a new request if the context is not cleared between requests. There is also no middleware that guarantees `TenantContext::clear()` is called after every request's `terminate` phase.

**Fix:** Register a terminating cleanup in `AppServiceProvider::boot()`:

```php
app->resolving(FoundationApplication::class, function ($app) {
    $app->instance('middlewarefinished', true);
});

app->terminating(function () {
    TenantContext::clear();
});
```

Or use Laravel's built-in middleware termination pattern with a dedicated `ClearTenantContext` middleware.

Also add a PHPStan/phpcs rule to flag usage of static TenantContext in async contexts.

## Triage

- Decision: `UNREVIEWED`
- Notes: