<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * A customer saved to the contacts is created with the invoice or not at all.
 */
final class CreateInvoiceHandler
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly DocumentLogoApplicationService $logos,
        private readonly InvoiceProductOwnership $productOwnership,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EntityLimit $limits,
    ) {}

    public function handle(CreateInvoice $command, WriteScope $scope): InvoiceDTO
    {
        $this->productOwnership->assertOwned($command->owner, $command->products);
        $business = $this->businesses->findById($command->parties->businessId, $scope->read)
            ?? throw new BusinessNotFoundException($command->parties->businessId);

        return $this->logos->saveWithLogo(
            null,
            false,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transactions->transaction(
                function () use ($command, $scope, $logo): InvoiceDTO {
                    $this->limits->assertCanCreate($command->owner, LimitedEntity::INVOICE);

                    $documentParties = $this->parties->assemble(
                        $command->owner,
                        $command->parties,
                        null,
                    );
                    $documentParties = $documentParties->withSupplierLogo($logo);

                    return $this->invoiceService->createInvoice(
                        $scope,
                        $command->data,
                        $documentParties,
                        $command->products,
                    );
                },
            ),
        );
    }
}
