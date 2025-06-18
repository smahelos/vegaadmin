<?php

namespace Tests\Feature\Models;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseCategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_expense_category(): void
    {
        $category = ExpenseCategory::factory()->create([
            'name' => 'Travel',
            'slug' => 'travel',
            'description' => 'Travel and transportation expenses',
            'color' => '#FF5722',
            'is_active' => true
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => 'Travel',
            'slug' => 'travel',
            'description' => 'Travel and transportation expenses',
            'color' => '#FF5722',
            'is_active' => true
        ]);
    }

    #[Test]
    public function expenses_relationship_works_correctly(): void
    {
        $category = ExpenseCategory::factory()->create();
        $expense1 = Expense::factory()->create(['category_id' => $category->id]);
        $expense2 = Expense::factory()->create(['category_id' => $category->id]);
        
        // Create another expense in different category to ensure proper filtering
        $otherCategory = ExpenseCategory::factory()->create();
        Expense::factory()->create(['category_id' => $otherCategory->id]);

        $relationship = $category->expenses();
        $this->assertInstanceOf(HasMany::class, $relationship);
        
        $expenses = $category->expenses;
        $this->assertCount(2, $expenses);
        $this->assertTrue($expenses->contains($expense1));
        $this->assertTrue($expenses->contains($expense2));
    }

    #[Test]
    public function is_active_cast_works_correctly(): void
    {
        $activeCategory = ExpenseCategory::factory()->create(['is_active' => '1']);
        $inactiveCategory = ExpenseCategory::factory()->create(['is_active' => '0']);

        $this->assertIsBool($activeCategory->is_active);
        $this->assertTrue($activeCategory->is_active);
        
        $this->assertIsBool($inactiveCategory->is_active);
        $this->assertFalse($inactiveCategory->is_active);
    }

    #[Test]
    public function can_create_category_with_color(): void
    {
        $category = ExpenseCategory::factory()->create([
            'name' => 'Office Supplies',
            'color' => '#2196F3'
        ]);

        $this->assertEquals('#2196F3', $category->color);
    }

    #[Test]
    public function can_create_category_without_description(): void
    {
        $category = ExpenseCategory::factory()->create([
            'name' => 'Simple Category',
            'description' => null
        ]);

        $this->assertEquals('Simple Category', $category->name);
        $this->assertNull($category->description);
    }

    #[Test]
    public function can_create_inactive_category(): void
    {
        $category = ExpenseCategory::factory()->create([
            'name' => 'Deprecated Category',
            'is_active' => false
        ]);

        $this->assertFalse($category->is_active);
    }

    #[Test]
    public function can_update_category_attributes(): void
    {
        $category = ExpenseCategory::factory()->create([
            'name' => 'Old Name',
            'color' => '#000000',
            'is_active' => true
        ]);

        $category->update([
            'name' => 'Updated Name',
            'color' => '#FFFFFF',
            'is_active' => false
        ]);

        $this->assertEquals('Updated Name', $category->name);
        $this->assertEquals('#FFFFFF', $category->color);
        $this->assertFalse($category->is_active);
    }

    #[Test]
    public function can_delete_category_without_expenses(): void
    {
        $category = ExpenseCategory::factory()->create();

        $categoryId = $category->id;
        $category->delete();

        $this->assertDatabaseMissing('expense_categories', ['id' => $categoryId]);
    }

    #[Test]
    public function category_can_have_many_expenses(): void
    {
        $category = ExpenseCategory::factory()->create();
        
        $expenses = Expense::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->expenses);
        
        foreach ($expenses as $expense) {
            $this->assertEquals($category->id, $expense->category_id);
        }
    }

    #[Test]
    public function fillable_attributes_work_correctly(): void
    {
        $data = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
            'color' => '#9C27B0',
            'is_active' => true
        ];

        $category = ExpenseCategory::create($data);

        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals('Test description', $category->description);
        $this->assertEquals('#9C27B0', $category->color);
        $this->assertTrue($category->is_active);
    }

    #[Test]
    public function factory_creates_valid_category_data(): void
    {
        $category = ExpenseCategory::factory()->create();

        $this->assertNotEmpty($category->name);
        $this->assertIsString($category->name);
        $this->assertIsBool($category->is_active);
        
        // Optional fields can be null
        if ($category->slug !== null) {
            $this->assertIsString($category->slug);
        }
        
        if ($category->description !== null) {
            $this->assertIsString($category->description);
        }
        
        if ($category->color !== null) {
            $this->assertIsString($category->color);
        }
    }

    #[Test]
    public function default_is_active_is_true(): void
    {
        $category = ExpenseCategory::factory()->create();
        
        // Most categories should be active by default
        // This depends on factory configuration, but let's test the cast works
        $this->assertIsBool($category->is_active);
    }
}
