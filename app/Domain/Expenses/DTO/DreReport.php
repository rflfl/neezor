<?php

namespace App\Domain\Expenses\DTO;

class DreReport
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $totalRevenue,
        public readonly int $totalCommission,
        public readonly int $totalExpenses,
        public readonly int $netProfit,
        public readonly float $profitMarginPercentage,
    ) {}

    public static function empty(int $year, int $month): self
    {
        return new self(
            year: $year,
            month: $month,
            totalRevenue: 0,
            totalCommission: 0,
            totalExpenses: 0,
            netProfit: 0,
            profitMarginPercentage: 0.0,
        );
    }
}
