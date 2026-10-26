<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceProductFactory extends Factory
{
    protected $model = InvoiceProduct::class;

    public function definition(): array
    {
        $line = new InvoiceProductWriteData(
            name: $this->faker->words(2, true),
            quantity: (string) $this->faker->randomFloat(
                3,
                1,
                10,
            ),
            unit: $this->faker->randomElement(Unit::cases()),
            price: Money::fromString((string) $this->faker->randomFloat(2, 10, 1000), Currency::CZK->value),
            taxRate: $this->faker->randomElement([
                '0',
                '12',
                '21',
            ]),
        );

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'name' => $line->name,
            'quantity' => $line->quantity,
            'price' => $line->price->getAmount(),
            'currency' => $line->price->getCurrency(),
            'unit' => $line->unit->value,
            'category' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'is_custom_product' => $this->faker->boolean(20), // 20% chance of being custom
            'tax_rate' => $line->taxRate,
            'tax_amount' => $line->taxAmount()->getAmount(),
            'total_price' => $line->total()->getAmount(),
        ];
    }
}
