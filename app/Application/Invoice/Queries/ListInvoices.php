<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<PaginatedResult<InvoiceListDTO>> */
final readonly class ListInvoices extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceSearch $search,
        public PageRequest $page,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_VIEW);
    }
}
