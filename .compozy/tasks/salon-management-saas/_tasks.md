# Neezor MVP — Task List

## Tasks

| # | Title | Status | Complexity | Dependencies |
|---|---|---|---|---|
| 01 | Multi-tenancy Foundation | completed | high | — |
| 02 | User + Professional Models | completed | medium | task_01 |
| 03 | Domain/Services + Domain/Customers | completed | medium | task_01 |
| 04 | Domain/Scheduling — Core | completed | high | task_02, task_03 |
| 05 | Domain/Packages | completed | high | task_04 |
| 06 | Domain/Cashbox | completed | high | task_04 |
| 07 | Domain/Commission | completed | high | task_06 |
| 08 | Domain/Expenses | completed | medium | task_06 |
| 09 | Frontend: Calendar + Appointment CRUD | completed | high | task_07, task_08 |
| 10 | Frontend: Clients + Services + Packages | completed | medium | task_09 |
| 11 | Frontend: Cashbox + Commissions + DRE | completed | medium | task_09 |
| 12 | Public Booking Portal | pending | high | task_09 |
| 13 | Domain/Notifications + Onboarding | completed | medium | task_09 |

## Build Order

1. task_01 (no deps)
2. task_02, task_03 (both depend on task_01) — can run in parallel
3. task_04 (depends on task_02 + task_03)
4. task_05, task_06 (both depend on task_04) — can run in parallel
5. task_07, task_08 (both depend on task_06) — can run in parallel
6. task_09 (depends on task_07 + task_08)
7. task_10, task_11, task_12, task_13 (all depend on task_09) — can run in parallel