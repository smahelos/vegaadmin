<?php

declare(strict_types=1);

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceCrudApplicationServiceInterface
{
    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        InvoiceWriteData $data,
        InvoicePartySelection $parties,
        array $products,
        ?UserId $actorId = null,
    ): InvoiceDTO;

    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceDTO;
}
