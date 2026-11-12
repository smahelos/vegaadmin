<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Product\DTO\CatalogProductDTO;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The owner's active products in the invoice currency, offered as invoice lines.
 *
 * @implements QueryInterface<list<CatalogProductDTO>>
 */
final readonly class ListInvoiceCatalogProducts extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public Currency $currency,
        public string $search,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
        );
    }
}
