<?php

namespace Tests\Feature\Traits;

use App\Traits\TaxFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private object $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create anonymous class using the trait
        $this->traitInstance = new class {
            use TaxFormFields;
            
            public function callGetTaxFields(): array
            {
                return $this->getTaxFields();
            }
        };
    }

    #[Test]
    public function getTaxFields_returns_properly_structured_tax_fields(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        $this->assertIsArray($fields);
        $this->assertCount(4, $fields);
        
        // Check name field
        $nameField = collect($fields)->firstWhere('name', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        $this->assertArrayHasKey('label', $nameField);
        $this->assertArrayHasKey('hint', $nameField);
        
        // Check rate field
        $rateField = collect($fields)->firstWhere('name', 'rate');
        $this->assertNotNull($rateField);
        $this->assertEquals('number', $rateField['type']);
        $this->assertTrue($rateField['required']);
        $this->assertEquals(0, $rateField['default']);
        
        // Check slug field
        $slugField = collect($fields)->firstWhere('name', 'slug');
        $this->assertNotNull($slugField);
        $this->assertEquals('text', $slugField['type']);
        $this->assertTrue($slugField['required']);
        
        // Check description field
        $descriptionField = collect($fields)->firstWhere('name', 'description');
        $this->assertNotNull($descriptionField);
        $this->assertEquals('textarea', $descriptionField['type']);
        $this->assertFalse($descriptionField['required']);
    }

    #[Test]
    public function getTaxFields_uses_translation_keys(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        foreach ($fields as $field) {
            // Check that labels and hints are strings (they might be translation keys if translations don't exist)
            if (isset($field['label'])) {
                $this->assertIsString($field['label']);
            }
            
            if (isset($field['hint'])) {
                $this->assertIsString($field['hint']);
            }
        }
    }

    #[Test]
    public function getTaxFields_field_names_are_correct(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        $fieldNames = collect($fields)->pluck('name')->toArray();
        
        $expectedFields = ['name', 'rate', 'slug', 'description'];
        
        $this->assertEquals($expectedFields, $fieldNames);
    }

    #[Test]
    public function getTaxFields_required_fields_are_properly_set(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        $requiredFields = collect($fields)->where('required', true)->pluck('name')->toArray();
        $optionalFields = collect($fields)->where('required', false)->pluck('name')->toArray();
        
        $this->assertContains('name', $requiredFields);
        $this->assertContains('rate', $requiredFields);
        $this->assertContains('slug', $requiredFields);
        
        $this->assertContains('description', $optionalFields);
    }

    #[Test]
    public function getTaxFields_field_types_are_appropriate(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        $fieldTypes = collect($fields)->pluck('type', 'name')->toArray();
        
        $this->assertEquals('text', $fieldTypes['name']);
        $this->assertEquals('number', $fieldTypes['rate']);
        $this->assertEquals('text', $fieldTypes['slug']);
        $this->assertEquals('textarea', $fieldTypes['description']);
    }

    #[Test]
    public function getTaxFields_default_values_are_set_correctly(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        $rateField = collect($fields)->firstWhere('name', 'rate');
        $this->assertEquals(0, $rateField['default']);
    }

    #[Test]
    public function getTaxFields_all_fields_have_required_attributes(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        foreach ($fields as $field) {
            // Every field should have these basic attributes
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('hint', $field);
            $this->assertArrayHasKey('required', $field);
            
            // Check that name is a non-empty string
            $this->assertIsString($field['name']);
            $this->assertNotEmpty($field['name']);
            
            // Check that type is a valid field type
            $this->assertIsString($field['type']);
            $this->assertContains($field['type'], ['text', 'number', 'textarea', 'select', 'boolean']);
            
            // Check that required is boolean
            $this->assertIsBool($field['required']);
        }
    }

    #[Test]
    public function getTaxFields_rate_field_has_number_type(): void
    {
        $fields = $this->traitInstance->callGetTaxFields();
        
        $rateField = collect($fields)->firstWhere('name', 'rate');
        
        // Rate field should be number type for proper validation
        $this->assertEquals('number', $rateField['type']);
        $this->assertIsNumeric($rateField['default']);
        $this->assertEquals(0, $rateField['default']);
    }
}
