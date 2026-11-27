<?php

declare(strict_types=1);

namespace App\Domain\Product\Contracts;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

interface CatalogProductReadRepositoryInterface
{
    public function isOwnedBy(ProductId $id, ReadScope $scope): bool;
}
