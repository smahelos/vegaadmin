<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentCatalogProductMapper;
use App\Models\Product;

final class EloquentCatalogProductRepository implements CatalogProductReadRepositoryInterface
{
    public function searchForUser(
        UserId $userId,
        Currency $currency,
        string $search,
    ): array {
        $query = Product::query();
        $query->where('user_id', $userId->toInt());
        $query->where('currency', $currency->value);
        $query->where('is_active', true);
        $query->where(
            'name',
            'like',
            '%' . $search . '%',
        );
        $query->with(['tax', 'category']);
        $query->orderBy('name');
        $query->orderBy('id');
        $query->limit(50);
        $products = $query->get();
        $products = $products->map(
            EloquentCatalogProductMapper::toDTO(...),
        );

        return $products->all();
    }

    public function isOwnedBy(ProductId $id, UserId $userId): bool
    {
        $query = Product::query();
        $query->where('user_id', $userId->toInt());
        $query->whereKey($id->getValue());

        return $query->exists();
    }
}
