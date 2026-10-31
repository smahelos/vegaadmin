<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Mappers;

use App\Domain\Product\DTO\CatalogProductDTO;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Models\Product;

final class EloquentCatalogProductMapper
{
    public static function toDTO(Product $product): CatalogProductDTO
    {
        return new CatalogProductDTO(
            id: ProductId::fromInt($product->id),
            name: $product->name,
            price: Money::fromString((string) $product->price, $product->currency),
            unit: Unit::fromInput($product->unit),
            taxRate: (string) ($product->tax?->rate ?? '0'),
            category: $product->category?->name,
            description: $product->description,
        );
    }
}
