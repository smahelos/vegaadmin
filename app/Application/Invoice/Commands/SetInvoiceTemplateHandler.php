<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class SetInvoiceTemplateHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriter,
    ) {}

    public function handle(SetInvoiceTemplate $command, WriteScope $scope): void
    {
        // The template is fixed at issue (D-050, point 3)
        $this->invoiceService->draftForUpdate($scope, $command->id);
        $this->invoiceWriter->setTemplate(
            $command->id,
            $scope,
            $command->template,
        );
    }
}
