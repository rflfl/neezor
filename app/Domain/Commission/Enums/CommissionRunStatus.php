<?php

namespace App\Domain\Commission\Enums;

enum CommissionRunStatus: string
{
    case DRAFT = 'draft';
    case CALCULATED = 'calculated';
    case PAID = 'paid';
}