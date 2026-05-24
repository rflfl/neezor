<?php

namespace App\Domain\Cashbox\Enums;

enum ExpenseCategoryType: string
{
    case FIXED = 'fixed';
    case VARIABLE = 'variable';
}