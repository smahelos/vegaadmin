<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Services\DocumentLogoService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

/**
 * Creates a draft; a customer saved to the contacts is created with it or not at all. The monthly quota counts
 * issued invoices, so it is checked at issue (D-050).
 */
final class CreateInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly DocumentLogoService $logos,
        private readonly InvoiceProductOwnership $productOwnership,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(CreateInvoice $command, WriteScope $scope): InvoiceDTO
    {
        $this->productOwnership->assertOwned($scope->read, $command->products);
        $business = $this->businesses->findById($command->parties->businessId, $scope->read)
            ?? throw new BusinessNotFoundException($command->parties->businessId);

        return $this->logos->saveWithLogo(
            null,
            false,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transactions->transaction(
                function () use ($command, $scope, $logo, $business): InvoiceDTO {
                    $documentParties = $this->parties->assemble(
                        $scope,
                        $command->parties,
                        null,
                    );
                    $documentParties = $documentParties->withSupplierLogo($logo);

                    return $this->invoiceService->createInvoice(
                        $scope,
                        $command->data,
                        $documentParties,
                        $command->products,
                        $business->invoicing->vatPayer,
                    );
                },
            ),
        );
    }
}
