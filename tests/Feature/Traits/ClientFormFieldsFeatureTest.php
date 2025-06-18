<?php

namespace Tests\Feature\Traits;

use App\Traits\ClientFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private object $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create anonymous class using the trait
        $this->traitInstance = new class {
            use ClientFormFields;
            
            public function callGetClientFields(): array
            {
                return $this->getClientFields();
            }
        };
    }

    #[Test]
    public function getClientFields_returns_properly_structured_client_fields(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $this->assertIsArray($fields);
        $this->assertCount(12, $fields);
        
        // Check name field (required)
        $nameField = collect($fields)->firstWhere('name', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        
        // Check email field (optional with email type)
        $emailField = collect($fields)->firstWhere('name', 'email');
        $this->assertNotNull($emailField);
        $this->assertEquals('email', $emailField['type']);
        $this->assertArrayNotHasKey('required', $emailField);
        
        // Check street field (required)
        $streetField = collect($fields)->firstWhere('name', 'street');
        $this->assertNotNull($streetField);
        $this->assertEquals('text', $streetField['type']);
        $this->assertTrue($streetField['required']);
        
        // Check is_default field (checkbox)
        $isDefaultField = collect($fields)->firstWhere('name', 'is_default');
        $this->assertNotNull($isDefaultField);
        $this->assertEquals('checkbox', $isDefaultField['type']);
        
        // Check description field (textarea)
        $descriptionField = collect($fields)->firstWhere('name', 'description');
        $this->assertNotNull($descriptionField);
        $this->assertEquals('textarea', $descriptionField['type']);
    }

    #[Test]
    public function getClientFields_field_names_are_correct(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        $fieldNames = collect($fields)->pluck('name')->toArray();
        
        $expectedFields = [
            'name', 'shortcut', 'email', 'phone', 'street', 'city', 
            'zip', 'country', 'ico', 'dic', 'description', 'is_default'
        ];
        
        $this->assertEquals($expectedFields, $fieldNames);
    }

    #[Test]
    public function getClientFields_required_fields_are_properly_set(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $requiredFields = collect($fields)->where('required', true)->pluck('name')->toArray();
        $optionalFields = collect($fields)->whereNotIn('name', $requiredFields)->pluck('name')->toArray();
        
        // Required fields
        $this->assertContains('name', $requiredFields);
        $this->assertContains('street', $requiredFields);
        $this->assertContains('city', $requiredFields);
        $this->assertContains('zip', $requiredFields);
        $this->assertContains('country', $requiredFields);
        
        // Optional fields
        $this->assertContains('shortcut', $optionalFields);
        $this->assertContains('email', $optionalFields);
        $this->assertContains('phone', $optionalFields);
        $this->assertContains('ico', $optionalFields);
        $this->assertContains('dic', $optionalFields);
        $this->assertContains('description', $optionalFields);
        $this->assertContains('is_default', $optionalFields);
    }

    #[Test]
    public function getClientFields_field_types_are_appropriate(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $fieldTypes = collect($fields)->pluck('type', 'name')->toArray();
        
        $this->assertEquals('text', $fieldTypes['name']);
        $this->assertEquals('text', $fieldTypes['shortcut']);
        $this->assertEquals('email', $fieldTypes['email']);
        $this->assertEquals('text', $fieldTypes['phone']);
        $this->assertEquals('text', $fieldTypes['street']);
        $this->assertEquals('text', $fieldTypes['city']);
        $this->assertEquals('text', $fieldTypes['zip']);
        $this->assertEquals('text', $fieldTypes['country']);
        $this->assertEquals('text', $fieldTypes['ico']);
        $this->assertEquals('text', $fieldTypes['dic']);
        $this->assertEquals('textarea', $fieldTypes['description']);
        $this->assertEquals('checkbox', $fieldTypes['is_default']);
    }

    #[Test]
    public function getClientFields_uses_translation_keys(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        foreach ($fields as $field) {
            // Check that labels are strings (translated or keys)
            if (isset($field['label'])) {
                $this->assertIsString($field['label']);
            }
            
            // Check that hints are strings (translated or keys)
            if (isset($field['hint'])) {
                $this->assertIsString($field['hint']);
            }
            
            // Check placeholders if present
            if (isset($field['placeholder'])) {
                $this->assertIsString($field['placeholder']);
            }
        }
    }

    #[Test]
    public function getClientFields_country_field_has_placeholder(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $countryField = collect($fields)->firstWhere('name', 'country');
        
        $this->assertArrayHasKey('placeholder', $countryField);
        $this->assertIsString($countryField['placeholder']);
    }

    #[Test]
    public function getClientFields_dic_field_has_compound_hint(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $dicField = collect($fields)->firstWhere('name', 'dic');
        
        // DIC field has a compound hint that combines two translation keys
        $this->assertArrayHasKey('hint', $dicField);
        $this->assertIsString($dicField['hint']);
        $this->assertStringContainsString(' ', $dicField['hint']); // Should contain space from concatenation
    }

    #[Test]
    public function getClientFields_all_fields_have_required_attributes(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        foreach ($fields as $field) {
            // Every field should have these basic attributes
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('hint', $field);
            
            // Check that name is a non-empty string
            $this->assertIsString($field['name']);
            $this->assertNotEmpty($field['name']);
            
            // Check that type is a valid field type
            $this->assertIsString($field['type']);
            $this->assertContains($field['type'], ['text', 'email', 'textarea', 'checkbox', 'select', 'number']);
        }
    }

    #[Test]
    public function getClientFields_address_fields_are_all_required(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $addressFields = ['street', 'city', 'zip', 'country'];
        
        foreach ($addressFields as $fieldName) {
            $field = collect($fields)->firstWhere('name', $fieldName);
            $this->assertNotNull($field, "Field $fieldName should exist");
            $this->assertTrue($field['required'], "Field $fieldName should be required");
        }
    }

    #[Test]
    public function getClientFields_contact_fields_are_optional(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        $contactFields = ['email', 'phone', 'shortcut'];
        
        foreach ($contactFields as $fieldName) {
            $field = collect($fields)->firstWhere('name', $fieldName);
            $this->assertNotNull($field, "Field $fieldName should exist");
            $this->assertArrayNotHasKey('required', $field, "Field $fieldName should not have required key or should be false");
        }
    }

    #[Test]
    public function getClientFields_business_fields_have_appropriate_hints(): void
    {
        $fields = $this->traitInstance->callGetClientFields();
        
        // ICO field should have validation hint (translated)
        $icoField = collect($fields)->firstWhere('name', 'ico');
        $this->assertIsString($icoField['hint']);
        $this->assertNotEmpty($icoField['hint']);
        
        // is_default field should have explanation (translated)
        $isDefaultField = collect($fields)->firstWhere('name', 'is_default');
        $this->assertIsString($isDefaultField['hint']);
        $this->assertNotEmpty($isDefaultField['hint']);
    }
}
