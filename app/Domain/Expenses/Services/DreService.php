<?php

namespace App\Domain\Expenses\Services;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Expenses\Contracts\DreServiceInterface;
use App\Domain\Expenses\DTO\DreReport;
use App\Domain\Expenses\Models\Expense;
use Carbon\Carbon;

class DreService implements DreServiceInterface
{
    public function calculateMonthlyReport(int $tenantId, int $year, int $month): DreReport
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $totalRevenue = (int) CashMovement::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('type', CashMovementType::ENTRY)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $totalCommission = (int) CommissionRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('period_start', [$start, $end])
            ->orWhereBetween('period_end', [$start, $end])
            ->where('status', '!=', 'draft')
            ->sum('total_commission');

        $expenses = $this->getMonthlyExpenses($tenantId, $start, $end);
        $totalExpenses = $expenses['total'];
        $recurringExpenses = $expenses['recurring'];
        $variableExpenses = $expenses['variable'];

        $netProfit = $totalRevenue - $totalCommission - $totalExpenses;

        $profitMargin = 0.0;
        if ($totalRevenue > 0) {
            $profitMargin = round(($netProfit / $totalRevenue) * 100, 2);
        }

        return new DreReport(
            year: $year,
            month: $month,
            totalRevenue: $totalRevenue,
            totalCommission: $totalCommission,
            totalExpenses: $totalExpenses,
            netProfit: $netProfit,
            profitMarginPercentage: $profitMargin,
        );
    }

    private function getMonthlyExpenses(int $tenantId, Carbon $start, Carbon $end): array
    {
        $expenses = Expense::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->get();

        $total = (int) $expenses->sum('amount');
        $recurring = (int) $expenses->where('is_recurring', true)->sum('amount');
        $variable = $total - $recurring;

        return [
            'total' => $total,
            'recurring' => $recurring,
            'variable' => $variable,
        ];
    }
}
