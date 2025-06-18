<?php

namespace Tests\Feature\Traits;

use App\Traits\SupplierFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TestSupplierController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new TestSupplierController();
    }

    #[Test]
    public function get_supplier_fields_returns_correct_structure(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        
        // Should have essential supplier fields
        $fieldNames = array_column($fields, 'name');
        $this->assertContains('name', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('street', $fieldNames);
        $this->assertContains('city', $fieldNames);
    }

    #[Test]
    public function get_supplier_fields_contains_required_name_field(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        $nameField = array_filter($fields, fn($field) => $field['name'] === 'name');
        $this->assertNotEmpty($nameField);
        
        $nameField = array_values($nameField)[0];
        $this->assertEquals('name', $nameField['name']);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        $this->assertArrayHasKey('label', $nameField);
        $this->assertArrayHasKey('hint', $nameField);
    }

    #[Test]
    public function get_supplier_fields_contains_required_email_field(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        $emailField = array_filter($fields, fn($field) => $field['name'] === 'email');
        $this->assertNotEmpty($emailField);
        
        $emailField = array_values($emailField)[0];
        $this->assertEquals('email', $emailField['name']);
        $this->assertEquals('email', $emailField['type']);
        $this->assertTrue($emailField['required']);
        $this->assertArrayHasKey('label', $emailField);
        $this->assertArrayHasKey('hint', $emailField);
    }

    #[Test]
    public function get_supplier_fields_contains_address_fields(): void
    {
        $fields = $this->controller->getSupplierFields();
        $fieldNames = array_column($fields, 'name');
        
        // Should have address related fields
        $this->assertContains('street', $fieldNames);
        $this->assertContains('city', $fieldNames);
        
        // Check street field
        $streetField = array_filter($fields, fn($field) => $field['name'] === 'street');
        $streetField = array_values($streetField)[0];
        $this->assertTrue($streetField['required']);
        
        // Check city field
        $cityField = array_filter($fields, fn($field) => $field['name'] === 'city');
        $cityField = array_values($cityField)[0];
        $this->assertTrue($cityField['required']);
    }

    #[Test]
    public function get_supplier_fields_contains_optional_fields(): void
    {
        $fields = $this->controller->getSupplierFields();
        $fieldNames = array_column($fields, 'name');
        
        // Should have optional fields
        $this->assertContains('shortcut', $fieldNames);
        $this->assertContains('phone', $fieldNames);
        
        // Check shortcut field is optional
        $shortcutField = array_filter($fields, fn($field) => $field['name'] === 'shortcut');
        $shortcutField = array_values($shortcutField)[0];
        $this->assertArrayNotHasKey('required', $shortcutField);
        
        // Check phone field is optional
        $phoneField = array_filter($fields, fn($field) => $field['name'] === 'phone');
        $phoneField = array_values($phoneField)[0];
        $this->assertArrayNotHasKey('required', $phoneField);
    }

    #[Test]
    public function get_supplier_fields_uses_translations(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        foreach ($fields as $field) {
            if (isset($field['label'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['label']);
                // Allow empty strings as translations might not exist in test environment
            }
            
            if (isset($field['hint'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['hint']);
                // Allow empty strings as translations might not exist in test environment
            }
        }
    }

    #[Test]
    public function get_supplier_fields_has_proper_field_structure(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        foreach ($fields as $field) {
            // Each field should have required keys
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('hint', $field);
            
            // Validate field types
            $this->assertIsString($field['name']);
            $this->assertIsString($field['label']);
            $this->assertIsString($field['type']);
            $this->assertIsString($field['hint']);
            
            // Check valid field types
            $validTypes = ['text', 'email', 'select', 'number', 'date', 'textarea', 'checkbox'];
            $this->assertContains($field['type'], $validTypes);
        }
    }

    #[Test]
    public function get_supplier_fields_maintains_consistent_structure(): void
    {
        $fields = $this->controller->getSupplierFields();
        
        // All fields should have consistent structure
        foreach ($fields as $field) {
            $this->assertIsArray($field);
            $this->assertNotEmpty($field['name']);
            
            // Required fields should have boolean true value
            if (isset($field['required'])) {
                $this->assertTrue($field['required']);
            }
        }
    }
}

/**
 * Test controller class that uses SupplierFormFields trait for testing
 */
class TestSupplierController
{
    use SupplierFormFields {
        getSupplierFields as public;
    }
}
