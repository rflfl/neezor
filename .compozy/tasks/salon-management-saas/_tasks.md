# Neezor MVP — Task List

## Tasks

| # | Title | Status | Complexity | Dependencies |
|---|---|---|---|---|
| 01 | Multi-tenancy Foundation | completed | high | — |
| 02 | User + Professional Models | completed | medium | task_01 |
| 03 | Domain/Services + Domain/Customers | pending | medium | task_01 |
| 04 | Domain/Scheduling — Core | pending | high | task_02, task_03 |
| 05 | Domain/Packages | pending | high | task_04 |
| 06 | Domain/Cashbox | pending | high | task_04 |
| 07 | Domain/Commission | pending | high | task_06 |
| 08 | Domain/Expenses | pending | medium | task_06 |
| 09 | Frontend: Calendar + Appointment CRUD | pending | high | task_07, task_08 |
| 10 | Frontend: Clients + Services + Packages | pending | medium | task_09 |
| 11 | Frontend: Cashbox + Commissions + DRE | pending | medium | task_09 |
| 12 | Public Booking Portal | pending | high | task_09 |
| 13 | Domain/Notifications + Onboarding | pending | medium | task_09 |

## Build Order

1. task_01 (no deps)
2. task_02, task_03 (both depend on task_01) — can run in parallel
3. task_04 (depends on task_02 + task_03)
4. task_05, task_06 (both depend on task_04) — can run in parallel
5. task_07, task_08 (both depend on task_06) — can run in parallel
6. task_09 (depends on task_07 + task_08)
7. task_10, task_11, task_12, task_13 (all depend on task_09) — can run in parallel