<?php

namespace App\Domain\Expenses\Contracts;

use App\Domain\Expenses\DTO\DreReport;

interface DreServiceInterface
{
    public function calculateMonthlyReport(int $tenantId, int $year, int $month): DreReport;
}
