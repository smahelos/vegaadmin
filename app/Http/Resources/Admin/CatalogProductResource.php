<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Domain\Product\DTO\CatalogProductDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CatalogProductResource extends JsonResource
{
    public function __construct(private readonly CatalogProductDTO $product)
    {
        parent::__construct($product);
    }

    /** @return array<string, int|string|null> */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product->id->getValue(),
            'name' => $this->product->name,
            'price' => $this->product->price->getAmount(),
            'currency' => $this->product->price->getCurrency(),
            'unit' => $this->product->unit->value,
            'tax_rate' => $this->product->taxRate,
            'category' => $this->product->category,
            'description' => $this->product->description,
        ];
    }
}
