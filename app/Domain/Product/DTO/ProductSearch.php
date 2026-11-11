<?php

declare(strict_types=1);

namespace App\Domain\Product\DTO;

use App\Domain\Product\Enums\ProductSort;
use App\Domain\Product\ValueObjects\ProductId;

/**
 * One page of the user's products. The term matches the name or description; null $active lists active and
 * inactive products alike.
 */
final readonly class ProductSearch
{
    /**
     * @param  list<ProductId>  $excluded  products already picked, left out of the page
     */
    public function __construct(
        public ?string $term,
        public ?bool $active,
        public ProductSort $sort,
        public bool $ascending,
        public array $excluded,
    ) {}
}
