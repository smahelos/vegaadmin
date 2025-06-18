<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'supplier_id' => Supplier::factory(),
            'category_id' => ExpenseCategory::factory(),
            'expense_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency' => $this->faker->currencyCode(),
            'payment_method_id' => PaymentMethod::factory(),
            'reference_number' => $this->faker->optional()->numerify('REF-########'),
            'description' => $this->faker->sentence(),
            'tax_amount' => $this->faker->randomFloat(2, 0, 1000),
            'tax_included' => $this->faker->boolean(),
            'status_id' => Status::factory(),
            'attachments' => null, // Will be handled by mutator if needed
        ];
    }

    /**
     * Create an expense with a specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create an expense for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create an expense with tax included.
     */
    public function taxIncluded(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_included' => true,
        ]);
    }

    /**
     * Create an expense without tax.
     */
    public function noTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_amount' => 0,
            'tax_included' => false,
        ]);
    }

    /**
     * Create an expense from a specific date.
     */
    public function fromDate(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_date' => $date,
        ]);
    }
}
