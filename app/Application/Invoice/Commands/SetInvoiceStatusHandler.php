<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class SetInvoiceStatusHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function handle(SetInvoiceStatus $command, WriteScope $scope): void
    {
        $this->invoiceService->setStatus(
            $scope,
            $command->id,
            $command->status,
        );
    }
}
