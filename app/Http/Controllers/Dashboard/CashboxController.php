<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Cashbox\Services\CashboxService;
use App\Domain\Scheduling\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CashboxController extends Controller
{
    public function __construct(
        private readonly CashboxService $cashboxService
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::current();
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : \Carbon\Carbon::today();

        $cashboxDay = $this->cashboxService->getByDate($tenantId, $date);

        $entries = [];
        $expenses = [];
        $appointments = [];

        if ($cashboxDay) {
            $entries = CashMovement::withoutGlobalScopes()
                ->where('cashbox_day_id', $cashboxDay->id)
                ->where('type', CashMovementType::ENTRY)
                ->with('appointment.client', 'appointment.service')
                ->orderBy('created_at')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'amount' => $m->amount,
                    'payment_method' => $m->payment_method,
                    'note' => $m->note,
                    'appointment' => $m->appointment,
                    'created_at' => $m->created_at->toIso8601String(),
                ]);

            $expenses = CashMovement::withoutGlobalScopes()
                ->where('cashbox_day_id', $cashboxDay->id)
                ->where('type', CashMovementType::EXPENSE)
                ->with('category')
                ->orderBy('created_at')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'amount' => $m->amount,
                    'payment_method' => $m->payment_method,
                    'note' => $m->note,
                    'category' => $m->category,
                    'created_at' => $m->created_at->toIso8601String(),
                ]);

            $appointments = Appointment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereDate('start_at', $date->toDateString())
                ->whereIn('status', ['completed', 'in_progress'])
                ->with('client', 'service')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'client' => $a->client ? ['id' => $a->client->id, 'name' => $a->client->name] : null,
                    'service' => $a->service ? ['id' => $a->service->id, 'name' => $a->service->name] : null,
                ]);
        }

        $categories = ExpenseCategory::where('tenant_id', $tenantId)->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        $cashboxData = null;
        if ($cashboxDay) {
            $cashboxData = [
                'id' => $cashboxDay->id,
                'date' => $cashboxDay->date->toDateString(),
                'opening_balance' => $cashboxDay->opening_balance,
                'closing_balance' => $cashboxDay->closing_balance,
                'status' => $cashboxDay->status->value,
                'discrepancy_amount' => $cashboxDay->discrepancy_amount,
            ];
        }

        return Inertia::render('Dashboard/Cashbox', [
            'cashbox' => $cashboxData,
            'entries' => $entries,
            'expenses' => $expenses,
            'categories' => $categories,
            'appointments' => $appointments,
            'selectedDate' => $date->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'opening_balance' => 'required|integer|min:0',
        ]);

        $tenantId = TenantContext::current();
        $date = $validated['date'] ?? now()->toDateString();

        try {
            $this->cashboxService->open(
                $tenantId,
                \Carbon\Carbon::parse($date),
                (int) $validated['opening_balance'],
                $request->user()?->id
            );

            return redirect()->route('dashboard.cashbox.index', ['date' => $date])
                ->with('success', 'Caixa aberto com sucesso.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function entry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'appointment_id' => 'nullable|exists:appointments,id',
            'note' => 'nullable|string',
        ]);

        $cashboxDay = CashboxDay::find($validated['cashbox_day_id']);

        try {
            $this->cashboxService->recordEntry(
                $cashboxDay,
                (int) $validated['amount'],
                $validated['payment_method'],
                $validated['appointment_id'] ?? null,
                $validated['note'] ?? null,
                $request->user()?->id
            );

            return redirect()->back()
                ->with('success', 'Receita registrada.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function expense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'note' => 'nullable|string',
        ]);

        $cashboxDay = CashboxDay::find($validated['cashbox_day_id']);

        try {
            $this->cashboxService->recordExpense(
                $cashboxDay,
                (int) $validated['amount'],
                $validated['payment_method'],
                (int) ($validated['expense_category_id'] ?? 0) ?: null,
                $validated['note'] ?? null,
                $request->user()?->id
            );

            return redirect()->back()
                ->with('success', 'Despesa registrada.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function close(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'closing_balance' => 'required|integer|min:0',
        ]);

        $cashboxDay = CashboxDay::find($validated['cashbox_day_id']);

        try {
            $this->cashboxService->close(
                $cashboxDay,
                (int) $validated['closing_balance'],
                $request->user()?->id
            );

            return redirect()->route('dashboard.cashbox.index')
                ->with('success', 'Caixa fechado.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
}