<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class SetInvoiceTemplateHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriter,
    ) {}

    public function handle(SetInvoiceTemplate $command, WriteScope $scope): void
    {
        if ($this->invoices->findById($command->id, $scope->read) === null) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }

        $this->invoiceWriter->setTemplate(
            $command->id,
            $scope,
            $command->template,
        );
    }
}
