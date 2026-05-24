<?php

namespace App\Domain\Cashbox\Enums;

enum CashboxStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}