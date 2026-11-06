<?php

declare(strict_types=1);

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceMutationApplicationServiceInterface
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
    ): InvoiceActionResult;

    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceActionResult;
}
