---
status: pending
file: routes/web.php
line: 146
severity: critical
author: claude-code
provider_ref:
---

# Issue 008: Duplicate Route Group Bypasses Onboarding Middleware

## Review Comment

`routes/web.php` contains two near-identical authenticated route groups. The first (lines 26-144) has the `onboarding` middleware nested inside, protecting routes until setup is complete. The second group (lines 146-249) is nearly identical but does NOT include the `onboarding` middleware. This means critical routes like `/dashboard/professionals`, `/dashboard/services`, `/dashboard/clients`, `/dashboard/calendar`, `/dashboard/cashbox`, `/dashboard/commissions`, and `/dashboard/expenses` can be accessed by users who haven't completed onboarding — bypassing the setup protection entirely.

**Fix:** Delete the second route group (lines 146-249). Keep only the first group with the `onboarding` middleware.

## Triage

- Decision: `UNREVIEWED`
- Notes: