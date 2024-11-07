<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case Reviewed = 'reviewed';
    case Reconciled = 'reconciled';
}
