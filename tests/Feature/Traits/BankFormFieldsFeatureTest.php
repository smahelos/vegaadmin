<?php

namespace Tests\Feature\Traits;

use App\Services\CountryService;
use App\Traits\BankFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BankFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private object $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create anonymous class using the trait
        $this->traitInstance = new class {
            use BankFormFields;
            
            public function callGetBankFields(): array
            {
                return $this->getBankFields();
            }
        };
    }

    #[Test]
    public function getBankFields_returns_properly_structured_bank_fields(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        
        $this->assertIsArray($fields);
        $this->assertCount(6, $fields);
        
        // Check name field
        $nameField = collect($fields)->firstWhere('name', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        $this->assertArrayHasKey('label', $nameField);
        $this->assertArrayHasKey('hint', $nameField);
        
        // Check code field
        $codeField = collect($fields)->firstWhere('name', 'code');
        $this->assertNotNull($codeField);
        $this->assertEquals('text', $codeField['type']);
        $this->assertTrue($codeField['required']);
        
        // Check swift field
        $swiftField = collect($fields)->firstWhere('name', 'swift');
        $this->assertNotNull($swiftField);
        $this->assertEquals('text', $swiftField['type']);
        $this->assertFalse($swiftField['required']);
        
        // Check country field
        $countryField = collect($fields)->firstWhere('name', 'country');
        $this->assertNotNull($countryField);
        $this->assertEquals('select_from_array', $countryField['type']);
        $this->assertTrue($countryField['required']);
        $this->assertEquals('cz', $countryField['default']);
        $this->assertFalse($countryField['allows_null']);
        $this->assertArrayHasKey('options', $countryField);
        
        // Check active field
        $activeField = collect($fields)->firstWhere('name', 'active');
        $this->assertNotNull($activeField);
        $this->assertEquals('boolean', $activeField['type']);
        $this->assertTrue($activeField['default']);
        
        // Check description field
        $descriptionField = collect($fields)->firstWhere('name', 'description');
        $this->assertNotNull($descriptionField);
        $this->assertEquals('textarea', $descriptionField['type']);
        $this->assertFalse($descriptionField['required']);
    }

    #[Test]
    public function getBankFields_integrates_with_country_service(): void
    {
        // Mock CountryService
        $mockCountryService = $this->createMock(CountryService::class);
        $mockCountryService->expects($this->once())
            ->method('getCountryCodesForSelect')
            ->willReturn(['cz' => 'Czech Republic', 'sk' => 'Slovakia']);
        
        App::instance(CountryService::class, $mockCountryService);
        
        $fields = $this->traitInstance->callGetBankFields();
        
        $countryField = collect($fields)->firstWhere('name', 'country');
        $this->assertEquals(['cz' => 'Czech Republic', 'sk' => 'Slovakia'], $countryField['options']);
    }

    #[Test]
    public function getBankFields_uses_translation_keys(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        
        foreach ($fields as $field) {
            // Check that labels are properly translated
            if (isset($field['label'])) {
                $this->assertIsString($field['label']);
                
                // Check that fields with existing translations are translated
                if (in_array($field['name'], ['name', 'code', 'swift', 'country', 'active'])) {
                    $this->assertStringNotContainsString('bank.fields.', $field['label']);
                }
            }
            
            // For hints, some might remain as translation keys if translations don't exist
            if (isset($field['hint'])) {
                $this->assertIsString($field['hint']);
                
                // Country field uses suppliers.hints.country which should be translated
                if ($field['name'] === 'country') {
                    $this->assertStringNotContainsString('suppliers.hints.', $field['hint']);
                }
                // Other hints might remain as keys if translations don't exist
            }
        }
    }

    #[Test]
    public function getBankFields_field_names_are_correct(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        $fieldNames = collect($fields)->pluck('name')->toArray();
        
        $expectedFields = ['name', 'code', 'swift', 'country', 'active', 'description'];
        
        $this->assertEquals($expectedFields, $fieldNames);
    }

    #[Test]
    public function getBankFields_required_fields_are_properly_set(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        
        $requiredFields = collect($fields)->where('required', true)->pluck('name')->toArray();
        $optionalFields = collect($fields)->where('required', false)->pluck('name')->toArray();
        
        $this->assertContains('name', $requiredFields);
        $this->assertContains('code', $requiredFields);
        $this->assertContains('country', $requiredFields);
        
        $this->assertContains('swift', $optionalFields);
        $this->assertContains('description', $optionalFields);
    }

    #[Test]
    public function getBankFields_field_types_are_appropriate(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        
        $fieldTypes = collect($fields)->pluck('type', 'name')->toArray();
        
        $this->assertEquals('text', $fieldTypes['name']);
        $this->assertEquals('text', $fieldTypes['code']);
        $this->assertEquals('text', $fieldTypes['swift']);
        $this->assertEquals('select_from_array', $fieldTypes['country']);
        $this->assertEquals('boolean', $fieldTypes['active']);
        $this->assertEquals('textarea', $fieldTypes['description']);
    }

    #[Test]
    public function getBankFields_default_values_are_set_correctly(): void
    {
        $fields = $this->traitInstance->callGetBankFields();
        
        $countryField = collect($fields)->firstWhere('name', 'country');
        $this->assertEquals('cz', $countryField['default']);
        
        $activeField = collect($fields)->firstWhere('name', 'active');
        $this->assertTrue($activeField['default']);
    }
}
