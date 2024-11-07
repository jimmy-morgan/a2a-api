<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Transfer = 'transfer';
}
