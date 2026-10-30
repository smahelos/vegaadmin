<?php

declare(strict_types=1);

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\CatalogProductDTO;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\ValueObjects\UserId;

interface CatalogProductReadRepositoryInterface
{
    /** @return list<CatalogProductDTO> Up to 50 active products, ordered by name. */
    public function searchForUser(
        UserId $userId,
        Currency $currency,
        string $search,
    ): array;

    public function isOwnedBy(ProductId $id, UserId $userId): bool;
}
