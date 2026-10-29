<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly DocumentLogoApplicationService $logos,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transaction,
    ) {}

    /**
     * A customer saved to the contacts is created with the invoice or not at all.
     *
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceDTO {
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);

        return $this->logos->saveWithLogo(
            null,
            false,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                fn (): InvoiceDTO => $this->invoiceService->createInvoice(
                    $userId,
                    $data,
                    $this->parties->assemble($userId, $parties, null)->withSupplierLogo($logo),
                    $products,
                ),
            ),
        );
    }

    /**
     * The invoice and its lines are replaced together; a failure leaves the stored invoice untouched.
     *
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        InvoicePartySelection $parties,
        ?array $products,
    ): InvoiceDTO {
        $stored = $this->invoices->findByIdForUser($invoiceId, $userId)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);
        $keep = ! $parties->reloadBusiness && $stored->parties->businessId->equals($parties->businessId);

        return $this->logos->saveWithLogo(
            $stored->parties->supplierLogo,
            $keep,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                fn (): InvoiceDTO => $this->invoiceService->updateInvoice(
                    $userId,
                    $invoiceId,
                    $data,
                    $this->parties->assemble($userId, $parties, $stored->parties)->withSupplierLogo($logo),
                    $products,
                ),
            ),
        );
    }
}
