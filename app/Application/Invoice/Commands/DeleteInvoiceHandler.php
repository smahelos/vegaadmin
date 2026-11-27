<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Party\Contracts\DocumentLogoStorageInterface;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

final class DeleteInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly DocumentLogoStorageInterface $logos,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(DeleteInvoice $command, WriteScope $scope): void
    {
        $draft = $this->transactions->transaction(
            fn (): InvoiceDTO => $this->invoiceService->deleteDraft($scope, $command->id),
        );
        $logo = $draft->parties->supplierLogo;
        if ($logo !== null) {
            $this->logos->delete($logo);
        }
    }
}
