<?php

declare(strict_types=1);

namespace App\Domain\Product\DTO;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

final readonly class CatalogProductDTO
{
    public function __construct(
        public ProductId $id,
        public string $name,
        public Money $price,
        public Unit $unit,
        public string $taxRate,
        public ?string $category,
        public ?string $description,
    ) {}
}
