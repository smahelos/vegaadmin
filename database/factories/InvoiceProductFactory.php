<?php

namespace Database\Factories;

use App\Models\InvoiceProduct;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceProductFactory extends Factory
{
    protected $model = InvoiceProduct::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $price = $this->faker->randomFloat(2, 10, 1000);
        $taxRate = $this->faker->randomFloat(2, 0, 25);
        $taxAmount = ($price * $quantity * $taxRate) / 100;
        $totalPrice = ($price * $quantity) + $taxAmount;

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'name' => $this->faker->words(2, true),
            'quantity' => $quantity,
            'price' => $price,
            'currency' => $this->faker->currencyCode(),
            'unit' => $this->faker->randomElement(['piece', 'kg', 'meter', 'hour', 'liter']),
            'category' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'is_custom_product' => $this->faker->boolean(20), // 20% chance of being custom
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * State for custom products (no product_id)
     */
    public function custom(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'product_id' => null,
                'is_custom_product' => true,
                'name' => $this->faker->words(3, true),
            ];
        });
    }

    /**
     * State for regular products (with product_id)
     */
    public function regular(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_custom_product' => false,
            ];
        });
    }
}
