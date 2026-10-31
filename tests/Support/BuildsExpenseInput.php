<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Business;
use App\Models\ExpenseCategory;

trait BuildsExpenseInput
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function expenseInput(Business $business, array $overrides = []): array
    {
        return $overrides + [
            'user_id' => $business->user_id,
            'business_id' => $business->id,
            'expense_date' => now()->toDateString(),
            'amount' => '123.45',
            'currency' => 'CZK',
            'category_id' => ExpenseCategory::factory()->create()->id,
            'reference_number' => 'EXP-' . uniqid(),
            ...$this->partyInput('supplier', 'One-off supplier'),
        ];
    }
}
