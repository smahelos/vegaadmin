<?php

declare(strict_types=1);

namespace App\Application\Invoice\Presenters;

use App\Domain\Invoice\Enums\InvoiceStatus;

final class InvoiceStatusPresenter
{
    /** Tailwind colour name of the status, which views combine into classes (`bg-green-100`); null is a draft. */
    public function color(?InvoiceStatus $status): string
    {
        return match ($status) {
            null => 'gray',
            InvoiceStatus::PAID => 'green',
            InvoiceStatus::PENDING => 'yellow',
        };
    }
}
