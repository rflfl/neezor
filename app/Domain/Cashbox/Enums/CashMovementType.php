<?php

namespace App\Domain\Cashbox\Enums;

enum CashMovementType: string
{
    case ENTRY = 'entry';
    case EXPENSE = 'expense';
}