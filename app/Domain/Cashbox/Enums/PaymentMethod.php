<?php

namespace App\Domain\Cashbox\Enums;

enum PaymentMethod: string
{
    case MONEY = 'money';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case PIX = 'pix';
    case TRANSFER = 'transfer';
}