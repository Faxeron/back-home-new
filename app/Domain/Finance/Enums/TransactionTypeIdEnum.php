<?php

namespace App\Domain\Finance\Enums;

enum TransactionTypeIdEnum: int
{
    case INCOME = 1;
    case OUTCOME = 2;
    case TRANSFER_IN = 3;
    case TRANSFER_OUT = 4;
    case ADVANCE = 5;
    case REFUND = 6;
    case DIRECTOR_LOAN = 7;
    case DIRECTOR_WITHDRAWAL = 8;
}
