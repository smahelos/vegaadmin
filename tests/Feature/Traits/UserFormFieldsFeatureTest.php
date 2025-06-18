<?php

namespace Tests\Feature\Traits;

use App\Traits\UserFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TestUserController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new TestUserController();
    }

    #[Test]
    public function get_user_fields_returns_correct_structure(): void
    {
        $fields = $this->controller->getUserFields();
        
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        
        // Should have at least name and email fields
        $fieldNames = array_column($fields, 'name');
        $this->assertContains('name', $fieldNames);
        $this->assertContains('email', $fieldNames);
    }

    #[Test]
    public function get_user_fields_contains_required_name_field(): void
    {
        $fields = $this->controller->getUserFields();
        
        $nameField = array_filter($fields, fn($field) => $field['name'] === 'name');
        $this->assertNotEmpty($nameField);
        
        $nameField = array_values($nameField)[0];
        $this->assertEquals('name', $nameField['name']);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        $this->assertArrayHasKey('label', $nameField);
        $this->assertArrayHasKey('placeholder', $nameField);
    }

    #[Test]
    public function get_user_fields_contains_required_email_field(): void
    {
        $fields = $this->controller->getUserFields();
        
        $emailField = array_filter($fields, fn($field) => $field['name'] === 'email');
        $this->assertNotEmpty($emailField);
        
        $emailField = array_values($emailField)[0];
        $this->assertEquals('email', $emailField['name']);
        $this->assertEquals('email', $emailField['type']);
        $this->assertTrue($emailField['required']);
        $this->assertArrayHasKey('label', $emailField);
        $this->assertArrayHasKey('placeholder', $emailField);
    }

    #[Test]
    public function get_user_fields_uses_translations(): void
    {
        $fields = $this->controller->getUserFields();
        
        foreach ($fields as $field) {
            if (isset($field['label'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['label']);
                $this->assertNotEmpty($field['label']);
            }
            
            if (isset($field['placeholder'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['placeholder']);
                $this->assertNotEmpty($field['placeholder']);
            }
        }
    }

    #[Test]
    public function get_password_fields_returns_correct_structure(): void
    {
        $fields = $this->controller->getPasswordFields();
        
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        
        // Should have password fields
        $fieldNames = array_column($fields, 'name');
        $this->assertContains('password', $fieldNames);
        $this->assertContains('password_confirmation', $fieldNames);
        $this->assertContains('current_password', $fieldNames);
    }

    #[Test]
    public function get_password_fields_with_edit_false(): void
    {
        $fields = $this->controller->getPasswordFields(false);
        
        $this->assertIsArray($fields);
        $this->assertCount(3, $fields);
        
        $fieldNames = array_column($fields, 'name');
        $this->assertEquals(['password', 'password_confirmation', 'current_password'], $fieldNames);
    }

    #[Test]
    public function get_password_fields_with_edit_true(): void
    {
        $fields = $this->controller->getPasswordFields(true);
        
        $this->assertIsArray($fields);
        $this->assertCount(3, $fields);
        
        $fieldNames = array_column($fields, 'name');
        $this->assertEquals(['password', 'password_confirmation', 'current_password'], $fieldNames);
    }

    #[Test]
    public function get_password_fields_contains_proper_field_types(): void
    {
        $fields = $this->controller->getPasswordFields();
        
        foreach ($fields as $field) {
            $this->assertEquals('password', $field['type']);
            $this->assertTrue($field['required']);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('placeholder', $field);
            $this->assertArrayHasKey('wrapper', $field);
        }
    }

    #[Test]
    public function get_password_fields_has_proper_wrapper_classes(): void
    {
        $fields = $this->controller->getPasswordFields();
        
        foreach ($fields as $field) {
            $this->assertArrayHasKey('wrapper', $field);
            $this->assertArrayHasKey('class', $field['wrapper']);
            $this->assertStringContainsString('form-group', $field['wrapper']['class']);
        }
    }

    #[Test]
    public function get_password_fields_uses_translations(): void
    {
        $fields = $this->controller->getPasswordFields();
        
        foreach ($fields as $field) {
            if (isset($field['label'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['label']);
                $this->assertNotEmpty($field['label']);
            }
            
            if (isset($field['placeholder'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['placeholder']);
                $this->assertNotEmpty($field['placeholder']);
            }
            
            if (isset($field['hint'])) {
                // Should contain translation call result or translation key
                $this->assertIsString($field['hint']);
                $this->assertNotEmpty($field['hint']);
            }
        }
    }
}

/**
 * Test controller class that uses UserFormFields trait for testing
 */
class TestUserController
{
    use UserFormFields;
}
