<?php

namespace App\Domain\Cashbox\Controllers;

use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Cashbox\Services\CashboxService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CashboxController extends Controller
{
    public function __construct(
        private readonly CashboxService $cashboxService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::current();
        $date = $request->input('date', now()->toDateString());

        $cashboxDay = $this->cashboxService->getByDate($tenantId, \Carbon\Carbon::parse($date));

        return response()->json([
            'cashbox' => $cashboxDay,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'opening_balance' => 'required|integer|min:0',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantId = TenantContext::current();
        $date = $request->input('date', now());

        try {
            $cashboxDay = $this->cashboxService->open(
                $tenantId,
                \Carbon\Carbon::parse($date),
                $request->input('opening_balance'),
                $request->user()?->id
            );

            return response()->json(['cashbox' => $cashboxDay], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function entry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'appointment_id' => 'nullable|exists:appointments,id',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cashboxDay = CashboxDay::find($request->input('cashbox_day_id'));

        try {
            $movement = $this->cashboxService->recordEntry(
                $cashboxDay,
                $request->input('amount'),
                $request->input('payment_method'),
                $request->input('appointment_id'),
                $request->input('note'),
                $request->user()?->id
            );

            return response()->json(['movement' => $movement], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function expense(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cashboxDay = CashboxDay::find($request->input('cashbox_day_id'));

        try {
            $movement = $this->cashboxService->recordExpense(
                $cashboxDay,
                $request->input('amount'),
                $request->input('payment_method'),
                $request->input('expense_category_id'),
                $request->input('note'),
                $request->user()?->id
            );

            return response()->json(['movement' => $movement], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function close(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cashbox_day_id' => 'required|exists:cashbox_days,id',
            'closing_balance' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cashboxDay = CashboxDay::find($request->input('cashbox_day_id'));

        try {
            $cashboxDay = $this->cashboxService->close(
                $cashboxDay,
                $request->input('closing_balance'),
                $request->user()?->id
            );

            return response()->json([
                'cashbox' => $cashboxDay,
                'has_discrepancy' => $cashboxDay->hasDiscrepancy(),
                'discrepancy_amount' => $cashboxDay->discrepancy_amount,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function categories(): JsonResponse
    {
        $tenantId = TenantContext::current();

        $categories = ExpenseCategory::where('tenant_id', $tenantId)->get();

        return response()->json(['categories' => $categories]);
    }
}