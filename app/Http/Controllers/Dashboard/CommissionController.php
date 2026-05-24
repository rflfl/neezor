<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Commission\Services\CommissionService;
use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CommissionController extends Controller
{
    public function __construct(
        private readonly CommissionService $commissionService
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::current();
        $period = $request->query('period', 'monthly');
        [$start, $end] = $this->resolvePeriod($period);

        $runs = CommissionRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->with('professional', 'payments')
            ->get()
            ->map(fn($run) => $this->mapRun($run));

        $professionals = Professional::all()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
        ]);

        return Inertia::render('Dashboard/Commissions/Index', [
            'commissionRuns' => $runs,
            'professionals' => $professionals,
            'period' => $period,
            'periodStart' => $start->toDateString(),
            'periodEnd' => $end->toDateString(),
        ]);
    }

    public function byProfessional(Request $request, int $professional): Response
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

        $professional = Professional::find($professional);

        $appointments = $result['appointments']->map(fn($a) => [
            'id' => $a->id,
            'client' => $a->client ? ['id' => $a->client->id, 'name' => $a->client->name] : null,
            'service' => $a->service ? ['id' => $a->service->id, 'name' => $a->service->name] : null,
            'price' => $a->price,
            'start_at' => $a->start_at->toIso8601String(),
            'commission' => $a->commission ?? 0,
        ]);

        $run = $this->mapRun($result['commission_run']);

        return Inertia::render('Dashboard/Commissions/Professional', [
            'professional' => ['id' => $professional->id, 'name' => $professional->name],
            'commissionRun' => $run,
            'appointments' => $appointments,
            'totalGross' => $result['total_gross'],
            'totalCommission' => $result['total_commission'],
            'period' => $period,
            'periodStart' => $start->toDateString(),
            'periodEnd' => $end->toDateString(),
        ]);
    }

    public function pay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_run_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $tenantId = TenantContext::current();

        try {
            $this->commissionService->recordPayment(
                $tenantId,
                (int) $validated['commission_run_id'],
                (int) $validated['amount'],
                Carbon::parse($validated['paid_at']),
                $validated['note'] ?? null,
                $request->user()?->id
            );

            return redirect()->back()
                ->with('success', 'Comissão marcada como paga.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
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

    private function mapRun(CommissionRun $run): array
    {
        return [
            'id' => $run->id,
            'professional_id' => $run->professional_id,
            'professional' => $run->professional ? [
                'id' => $run->professional->id,
                'name' => $run->professional->name,
            ] : null,
            'period_start' => $run->period_start,
            'period_end' => $run->period_end,
            'total_gross' => $run->total_gross,
            'total_commission' => $run->total_commission,
            'status' => $run->status->value,
            'payments' => $run->payments->map(fn($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'paid_at' => $p->paid_at->toDateString(),
                'note' => $p->note,
            ]),
            'pending_amount' => $run->pending_amount,
        ];
    }
}