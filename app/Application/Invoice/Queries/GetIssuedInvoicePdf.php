<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;

/**
 * The admin downloads the stored PDF of any owner's issued invoice (D-050, consequences).
 *
 * @implements QueryInterface<PdfInvoiceDTO>
 */
final readonly class GetIssuedInvoicePdf implements QueryInterface
{
    public function __construct(
        public InvoiceId $id,
    ) {}

    public static function access(): Access
    {
        return Access::allOwners(Permission::INVOICE_VIEW);
    }
}
