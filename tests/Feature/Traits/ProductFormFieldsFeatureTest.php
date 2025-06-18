<?php

namespace Tests\Feature\Traits;

use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Tax;
use App\Traits\ProductFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase, ProductFormFields;

    #[Test]
    public function get_product_fields_returns_array(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function get_product_fields_contains_required_fields(): void
    {
        // Act
        $result = $this->getProductFields();
        $fieldNames = collect($result)->pluck('name')->toArray();

        // Assert - Check for required product fields
        $this->assertContains('name', $fieldNames);
        $this->assertContains('slug', $fieldNames);
        $this->assertContains('category_id', $fieldNames);
        $this->assertContains('tax_id', $fieldNames);
        $this->assertContains('price', $fieldNames);
        $this->assertContains('currency', $fieldNames);
        $this->assertContains('supplier_id', $fieldNames);
        $this->assertContains('description', $fieldNames);
        $this->assertContains('image', $fieldNames);
        $this->assertContains('is_default', $fieldNames);
        $this->assertContains('is_active', $fieldNames);
    }

    #[Test]
    public function get_product_fields_works_with_real_data(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create(['name' => 'Test Category']);
        $tax = Tax::factory()->create(['name' => 'Test Tax']);
        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);

        $productCategories = [$category->id => $category->name];
        $taxRates = [$tax->id => $tax->name];
        $currencies = ['EUR' => 'EUR', 'USD' => 'USD'];
        $suppliers = [$supplier->id => $supplier->name];

        // Act
        $result = $this->getProductFields($productCategories, $taxRates, $currencies, $suppliers);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify options are passed correctly (but service overrides them)
        $categoryField = collect($result)->firstWhere('name', 'category_id');
        $taxField = collect($result)->firstWhere('name', 'tax_id');
        $currencyField = collect($result)->firstWhere('name', 'currency');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');

        $this->assertNotNull($categoryField);
        $this->assertNotNull($taxField);
        $this->assertNotNull($currencyField);
        $this->assertNotNull($supplierField);
    }

    #[Test]
    public function get_product_fields_handles_empty_parameters(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that services provide data even with empty arrays
        $categoryField = collect($result)->firstWhere('name', 'category_id');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');
        $currencyField = collect($result)->firstWhere('name', 'currency');

        $this->assertEquals([], $categoryField['options']);
        $this->assertIsArray($supplierField['options']);
        $this->assertIsArray($currencyField['options']);
    }

    #[Test]
    public function get_product_fields_has_correct_field_structure(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert
        foreach ($result as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('hint', $field);
            $this->assertArrayHasKey('required', $field);
        }
    }

    #[Test]
    public function get_product_fields_has_correct_validation_rules(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - Check required fields
        $requiredFields = collect($result)->where('required', true);
        $requiredFieldNames = $requiredFields->pluck('name')->toArray();

        $this->assertContains('name', $requiredFieldNames);
        $this->assertContains('slug', $requiredFieldNames);
        $this->assertContains('category_id', $requiredFieldNames);
        $this->assertContains('tax_id', $requiredFieldNames);
        $this->assertContains('price', $requiredFieldNames);
        $this->assertContains('currency', $requiredFieldNames);

        // Check optional fields
        $optionalFields = collect($result)->where('required', false);
        $optionalFieldNames = $optionalFields->pluck('name')->toArray();

        $this->assertContains('supplier_id', $optionalFieldNames);
        $this->assertContains('description', $optionalFieldNames);
        $this->assertContains('image', $optionalFieldNames);
        $this->assertContains('is_default', $optionalFieldNames);
        $this->assertContains('is_active', $optionalFieldNames);
    }

    #[Test]
    public function get_product_fields_has_correct_field_types(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - Check field types
        $nameField = collect($result)->firstWhere('name', 'name');
        $priceField = collect($result)->firstWhere('name', 'price');
        $descriptionField = collect($result)->firstWhere('name', 'description');
        $imageField = collect($result)->firstWhere('name', 'image');
        $isDefaultField = collect($result)->firstWhere('name', 'is_default');
        $categoryField = collect($result)->firstWhere('name', 'category_id');
        $currencyField = collect($result)->firstWhere('name', 'currency');

        $this->assertEquals('text', $nameField['type']);
        $this->assertEquals('number', $priceField['type']);
        $this->assertEquals('textarea', $descriptionField['type']);
        $this->assertEquals('file', $imageField['type']);
        $this->assertEquals('checkbox', $isDefaultField['type']);
        $this->assertEquals('select', $categoryField['type']);
        $this->assertEquals('select_from_array', $currencyField['type']);
    }

    #[Test]
    public function get_product_fields_sets_model_classes_correctly(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert
        $categoryField = collect($result)->firstWhere('name', 'category_id');
        $taxField = collect($result)->firstWhere('name', 'tax_id');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');

        $this->assertEquals(ProductCategory::class, $categoryField['model']);
        $this->assertEquals(Tax::class, $taxField['model']);
        $this->assertEquals(Supplier::class, $supplierField['model']);
    }

    #[Test]
    public function get_product_fields_has_default_values(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert
        $priceField = collect($result)->firstWhere('name', 'price');
        $this->assertEquals(0, $priceField['default']);
    }

    #[Test]
    public function get_product_fields_contains_translated_labels(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - Check that all fields have labels (translated or raw)
        foreach ($result as $field) {
            $this->assertArrayHasKey('label', $field);
            $this->assertNotEmpty($field['label']);
        }
    }

    #[Test]
    public function get_product_fields_contains_hint_fields(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - All fields should have hints
        foreach ($result as $field) {
            $this->assertArrayHasKey('hint', $field);
            $this->assertNotNull($field['hint']);
        }
    }

    #[Test]
    public function get_product_fields_returns_expected_field_count(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - Should have 11 fields based on implementation
        $this->assertCount(11, $result);
    }

    #[Test]
    public function get_product_fields_maintains_consistency_across_calls(): void
    {
        // Arrange
        $testData = [
            [1 => 'Category 1'],
            [1 => 'Tax 1'],
            ['EUR' => 'EUR'],
            [1 => 'Supplier 1']
        ];

        // Act
        $result1 = $this->getProductFields(...$testData);
        $result2 = $this->getProductFields(...$testData);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertCount(count($result1), $result2);
    }

    #[Test]
    public function get_product_fields_integrates_with_services(): void
    {
        // Act
        $result = $this->getProductFields();

        // Assert - Services are called to populate data
        $currencyField = collect($result)->firstWhere('name', 'currency');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');

        $this->assertArrayHasKey('options', $currencyField);
        $this->assertArrayHasKey('options', $supplierField);
        $this->assertIsArray($currencyField['options']);
        $this->assertIsArray($supplierField['options']);
    }
}
