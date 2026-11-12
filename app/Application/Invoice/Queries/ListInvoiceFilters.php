<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Party\Enums\PartyKind;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The businesses or contacts the owner's invoices were issued with, archived ones included, for the list filter.
 *
 * @implements QueryInterface<list<InvoicePartyFilterDTO>>
 */
final readonly class ListInvoiceFilters extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public PartyKind $kind,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_VIEW);
    }
}
