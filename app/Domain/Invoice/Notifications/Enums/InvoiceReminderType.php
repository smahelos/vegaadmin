<?php

namespace App\Domain\Invoice\Notifications\Enums;

enum InvoiceReminderType: string
{
    case UPCOMING_DUE = 'upcoming_due';
    case DUE_TODAY = 'due_today';
    case OVERDUE = 'overdue';
}
