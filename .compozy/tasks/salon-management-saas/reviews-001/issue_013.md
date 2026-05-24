---
status: pending
file: app/Http/Controllers/Dashboard/ExpenseController.php
line: 50
severity: high
author: claude-code
provider_ref:
---

# Issue 013: ExpenseController::store() Does Nothing

## Review Comment

The `store()` method (lines 50-66) is identical to `index()` — it validates, then returns a view without creating any expense record. The route `POST /dashboard/expenses` is effectively broken. Users cannot create expenses through the form.

**Fix:** Implement the actual creation logic:

```php
public function store(StoreExpenseRequest $request): Response|RedirectResponse
{
    Expense::create([
        'tenant_id' => $request->user()->tenant_id,
        'amount' => $request->validated('amount'),
        'category_id' => $request->validated('category_id'),
        'is_recurring' => $request->boolean('is_recurring'),
        'description' => $request->validated('description'),
        'due_date' => $request->validated('due_date'),
    ]);

    return redirect()->route('dashboard.expenses.index')
        ->with('flash.banner', 'Despesa criada com sucesso.');
}
```

## Triage

- Decision: `UNREVIEWED`
- Notes: