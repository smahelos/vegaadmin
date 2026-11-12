<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceListFiltersDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;

/**
 * The businesses and contacts the owner's invoices were issued with, archived ones included, for the list filter.
 *
 * @implements QueryInterface<InvoiceListFiltersDTO>
 */
final readonly class ListInvoiceFilters extends OwnedUseCase implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_VIEW);
    }
}
