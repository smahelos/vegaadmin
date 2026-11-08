<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly DocumentLogoApplicationService $logos,
        private readonly InvoiceProductOwnership $productOwnership,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transaction,
        private readonly EntityLimit $limits,
    ) {}

    /**
     * A customer saved to the contacts is created with the invoice or not at all.
     *
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        InvoiceWriteData $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceDTO {
        $this->productOwnership->assertOwned($userId, $products);
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);

        return $this->logos->saveWithLogo(
            null,
            false,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                function () use ($userId, $data, $parties, $products, $logo): InvoiceDTO {
                    $this->limits->assertCanCreate($userId, LimitedEntity::INVOICE);

                    $documentParties = $this->parties->assemble(
                        $userId,
                        $parties,
                        null,
                    );
                    $documentParties = $documentParties->withSupplierLogo($logo);

                    return $this->invoiceService->createInvoice(
                        $userId,
                        $data,
                        $documentParties,
                        $products,
                    );
                },
            ),
        );
    }

    /**
     * The invoice and its lines are replaced together; a failure leaves the stored invoice untouched.
     *
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceDTO {
        $stored = $this->invoices->findByIdForUser($invoiceId, $userId)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $this->productOwnership->assertOwned($userId, $products);
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);
        $keep = !$parties->reloadBusiness && $stored->parties->businessId->equals($parties->businessId);

        return $this->logos->saveWithLogo(
            $stored->parties->supplierLogo,
            $keep,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                function () use ($userId, $invoiceId, $data, $parties, $stored, $products, $logo): InvoiceDTO {
                    $documentParties = $this->parties->assemble(
                        $userId,
                        $parties,
                        $stored->parties,
                    );
                    $documentParties = $documentParties->withSupplierLogo($logo);

                    return $this->invoiceService->updateInvoice(
                        $userId,
                        $invoiceId,
                        $data,
                        $documentParties,
                        $products,
                    );
                },
            ),
        );
    }
}
