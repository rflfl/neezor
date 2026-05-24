<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Expenses\Services\DreService;
use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly DreService $dreService
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::current();

        $expenses = Expense::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('due_date')
            ->with('category')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'amount' => $e->amount,
                'description' => $e->description,
                'due_date' => $e->due_date->toDateString(),
                'is_recurring' => $e->is_recurring,
                'category' => $e->category ? ['id' => $e->category->id, 'name' => $e->category->name] : null,
            ]);

        $categories = ExpenseCategory::where('tenant_id', $tenantId)->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        return Inertia::render('Dashboard/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::current();

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'category_id' => 'nullable|integer',
            'is_recurring' => 'boolean',
            'due_date' => 'required|date',
        ]);

        Expense::withoutGlobalScopes()->create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'category_id' => $validated['category_id'] ?? null,
        ]));

        return redirect()->route('dashboard.expenses.index')
            ->with('flash.banner', 'Despesa criada com sucesso.');
    }

    public function dre(Request $request): Response
    {
        $tenantId = TenantContext::current();
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $report = $this->dreService->calculateMonthlyReport($tenantId, $year, $month);

        $availableMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $availableMonths[] = ['value' => $m, 'label' => Carbon::create($year, $m, 1)->locale('pt_BR')->monthName];
        }

        return Inertia::render('Dashboard/Dre', [
            'report' => $report,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'availableMonths' => $availableMonths,
        ]);
    }
}