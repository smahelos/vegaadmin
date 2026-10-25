<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly TransactionBoundaryInterface $transaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        array $data,
        array $products,
    ): InvoiceDTO {
        return $this->transaction->transaction(
            fn (): InvoiceDTO => $this->invoiceService->createInvoice(
                $userId,
                $data,
                $products,
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
        array $products,
    ): InvoiceDTO {
        return $this->transaction->transaction(
            fn (): InvoiceDTO => $this->invoiceService->updateInvoice(
                $userId,
                $invoiceId,
                $data,
                $products,
            ),
        );
    }
}
