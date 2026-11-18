<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Forms\Expense;

use App\Infrastructure\Forms\Expense\ExpenseFormFields;
use App\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExpenseFormFieldsTest extends TestCase
{
    #[Test]
    public function category_names_include_inactive_categories(): void
    {
        $categoryFactory = ExpenseCategory::factory();
        $active = $categoryFactory->create(['is_active' => true]);
        $inactive = $categoryFactory->create(['is_active' => false]);

        $names = ExpenseFormFields::categoryNames();

        $this->assertSame($active->name, $names[$active->id]);
        $this->assertSame($inactive->name, $names[$inactive->id]);
    }
}
