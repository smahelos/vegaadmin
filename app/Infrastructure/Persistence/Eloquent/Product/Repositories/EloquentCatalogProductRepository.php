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
        return Product::query()->where('user_id', $userId->toInt())
            ->where('currency', $currency->value)->where('is_active', true)
            ->where(
                'name',
                'like',
                '%' . $search . '%',
            )
            ->with(['tax', 'category'])->orderBy('name')->orderBy('id')->limit(50)->get()
            ->map(EloquentCatalogProductMapper::toDTO(...))->all();
    }

    public function isOwnedBy(ProductId $id, UserId $userId): bool
    {
        return Product::query()->where('user_id', $userId->toInt())->whereKey($id->getValue())->exists();
    }
}
