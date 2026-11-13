<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Services\DocumentLogoService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

/**
 * The invoice and its lines are replaced together; a failure leaves the stored invoice untouched.
 */
final class UpdateInvoiceHandler
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly DocumentLogoService $logos,
        private readonly InvoiceProductOwnership $productOwnership,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(UpdateInvoice $command, WriteScope $scope): InvoiceDTO
    {
        $stored = $this->invoices->findById($command->id, $scope->read)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $this->productOwnership->assertOwned($scope->read, $command->products);
        $business = $this->businesses->findById($command->parties->businessId, $scope->read)
            ?? throw new BusinessNotFoundException($command->parties->businessId);
        $sameBusiness = $stored->parties->businessId->equals($command->parties->businessId);
        $keep = !$command->parties->reloadBusiness && $sameBusiness;

        return $this->logos->saveWithLogo(
            $stored->parties->supplierLogo,
            $keep,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transactions->transaction(
                function () use ($command, $scope, $stored, $logo): InvoiceDTO {
                    $documentParties = $this->parties->assemble(
                        $scope,
                        $command->parties,
                        $stored->parties,
                    );
                    $documentParties = $documentParties->withSupplierLogo($logo);

                    return $this->invoiceService->updateInvoice(
                        $scope,
                        $command->id,
                        $command->data,
                        $documentParties,
                        $command->products,
                    );
                },
            ),
        );
    }
}
