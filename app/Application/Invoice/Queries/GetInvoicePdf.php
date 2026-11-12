<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<PdfInvoiceDTO> */
final readonly class GetInvoicePdf extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
        public ?string $locale,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_VIEW);
    }
}
