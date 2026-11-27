<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Models\Product;

final class EloquentCatalogProductRepository implements CatalogProductReadRepositoryInterface
{
    public function isOwnedBy(ProductId $id, ReadScope $scope): bool
    {
        $query = Product::query();
        $query->where('user_id', $scope->owner->value);
        $query->whereKey($id->value);

        return $query->exists();
    }
}
