<?php

namespace App\Domain\Expenses\Controllers;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Services\DreService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly DreService $dreService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::current();

        $expenses = Expense::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('due_date')
            ->with('category')
            ->get();

        return response()->json(['expenses' => $expenses]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'is_recurring' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantId = TenantContext::current();

        $expense = Expense::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'amount' => $request->input('amount'),
            'expense_category_id' => $request->input('expense_category_id'),
            'is_recurring' => $request->input('is_recurring', false),
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
        ]);

        return response()->json(['expense' => $expense], 201);
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = TenantContext::current();

        $expense = Expense::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('category')
            ->firstOrFail();

        return response()->json(['expense' => $expense]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|integer|min:1',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'is_recurring' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'due_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantId = TenantContext::current();

        $expense = Expense::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $expense->update($request->only([
            'amount',
            'expense_category_id',
            'is_recurring',
            'description',
            'due_date',
        ]));

        return response()->json(['expense' => $expense->fresh()->load('category')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = TenantContext::current();

        $expense = Expense::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $expense->delete();

        return response()->json(['message' => 'Expense deleted']);
    }

    public function dre(Request $request): JsonResponse
    {
        $tenantId = TenantContext::current();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $report = $this->dreService->calculateMonthlyReport($tenantId, $year, $month);

        return response()->json(['report' => $report]);
    }
}
