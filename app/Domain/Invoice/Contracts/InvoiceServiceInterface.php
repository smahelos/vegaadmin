<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Exceptions\NonPayerVatRateException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

interface InvoiceServiceInterface
{
    /**
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     * @param  bool  $vatPayer  Of the supplier business; a non-payer's lines have rate 0
     *
     * @throws NonPayerVatRateException
     */
    public function createInvoice(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
        bool $vatPayer,
    ): InvoiceDTO;

    /**
     * Replaces the invoice and its lines.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     * @param  bool  $vatPayer  Of the supplier business; a non-payer's lines have rate 0
     *
     * @throws NonPayerVatRateException
     */
    public function updateInvoice(
        WriteScope $scope,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
        bool $vatPayer,
    ): InvoiceDTO;

    public function generateInvoiceNumber(ReadScope $scope, int $year): string;
}
