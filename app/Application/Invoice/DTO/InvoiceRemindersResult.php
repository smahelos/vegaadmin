<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

final readonly class InvoiceRemindersResult
{
    public function __construct(
        public int $upcoming,
        public int $dueToday,
        public int $overdue,
    ) {}
}
