<?php

namespace App\Domain\Commission\Controllers;

use App\Domain\Commission\Services\CommissionService;
use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CommissionController extends Controller
{
    public function __construct(
        private readonly CommissionService $commissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::current();
        $professionalId = $request->query('professional_id');
        $period = $request->query('period', 'monthly');

        [$start, $end] = $this->resolvePeriod($period);

        if ($professionalId) {
            $result = $this->commissionService->calculateForPeriod(
                $tenantId,
                (int) $professionalId,
                $start,
                $end
            );

            return response()->json([
                'commission_run' => $result['commission_run'],
                'total_gross' => $result['total_gross'],
                'total_commission' => $result['total_commission'],
                'appointments_count' => $result['appointments']->count(),
            ]);
        }

        $runs = \App\Domain\Commission\Models\CommissionRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->with('professional')
            ->get();

        return response()->json(['commission_runs' => $runs]);
    }

    public function byProfessional(int $professional, Request $request): JsonResponse
    {
        $tenantId = TenantContext::current();
        $period = $request->query('period', 'monthly');

        [$start, $end] = $this->resolvePeriod($period);

        $result = $this->commissionService->calculateForPeriod(
            $tenantId,
            $professional,
            $start,
            $end
        );

        return response()->json([
            'commission_run' => $result['commission_run'],
            'total_gross' => $result['total_gross'],
            'total_commission' => $result['total_commission'],
            'appointments' => $result['appointments'],
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $request->validate([
            'commission_run_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $tenantId = TenantContext::current();
        $userId = $request->user()?->id;

        try {
            $payment = $this->commissionService->recordPayment(
                $tenantId,
                $request->input('commission_run_id'),
                $request->input('amount'),
                Carbon::parse($request->input('paid_at')),
                $request->input('note'),
                $userId
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['payment' => $payment], 201);
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'commission_run_id' => 'required|integer',
            'adjustment' => 'required|integer',
            'reason' => 'required|string|min:1',
        ]);

        $tenantId = TenantContext::current();
        $userId = $request->user()?->id;

        $run = $this->commissionService->recordAdjustment(
            $tenantId,
            $request->input('commission_run_id'),
            $request->input('adjustment'),
            $request->input('reason'),
            $userId
        );

        return response()->json(['commission_run' => $run]);
    }

    private function resolvePeriod(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'monthly' => [$now->startOfMonth(), $now->endOfMonth()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }
}