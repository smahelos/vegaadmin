<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Forms\Party;

use App\Infrastructure\Forms\Party\BankFormFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BankFormFieldsTest extends TestCase
{
    private const COUNTRIES = [
        'CZ' => 'Česko',
        'SK' => 'Slovensko',
    ];

    #[Test]
    public function returns_properly_structured_bank_fields(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);

        $this->assertIsArray($fields);
        $this->assertCount(6, $fields);

        // Check name field
        $fieldList = collect($fields);
        $nameField = $fieldList->firstWhere('name', 'name');
        $this->assertNotNull($nameField);
        $this->assertEquals('text', $nameField['type']);
        $this->assertTrue($nameField['required']);
        $this->assertArrayHasKey('label', $nameField);

        // Check code field
        $codeField = $fieldList->firstWhere('name', 'code');
        $this->assertNotNull($codeField);
        $this->assertEquals('text', $codeField['type']);
        $this->assertTrue($codeField['required']);

        // Check swift field
        $swiftField = $fieldList->firstWhere('name', 'swift');
        $this->assertNotNull($swiftField);
        $this->assertEquals('text', $swiftField['type']);
        $this->assertFalse($swiftField['required']);

        // Check country field
        $countryField = $fieldList->firstWhere('name', 'country');
        $this->assertNotNull($countryField);
        $this->assertEquals('select_from_array', $countryField['type']);
        $this->assertTrue($countryField['required']);
        $this->assertEquals('CZ', $countryField['default']);
        $this->assertFalse($countryField['allows_null']);
        $this->assertArrayHasKey('options', $countryField);

        // Check active field
        $activeField = $fieldList->firstWhere('name', 'active');
        $this->assertNotNull($activeField);
        $this->assertEquals('boolean', $activeField['type']);
        $this->assertTrue($activeField['default']);

        // Check description field
        $descriptionField = $fieldList->firstWhere('name', 'description');
        $this->assertNotNull($descriptionField);
        $this->assertEquals('textarea', $descriptionField['type']);
        $this->assertFalse($descriptionField['required']);
    }

    #[Test]
    public function the_country_select_offers_the_given_countries(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);

        $collection = collect($fields);
        $countryField = $collection->firstWhere('name', 'country');
        $this->assertSame(self::COUNTRIES, $countryField['options']);
    }

    #[Test]
    public function labels_and_hints_are_translated(): void
    {
        foreach (BankFormFields::fields(self::COUNTRIES) as $field) {
            $this->assertStringNotContainsString('banks.', $field['label']);
            $this->assertStringNotContainsString('banks.', $field['hint'] ?? '');
        }
    }

    #[Test]
    public function field_names_are_correct(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);
        $collection = collect($fields);
        $values = $collection->pluck('name');
        $fieldNames = $values->toArray();

        $expectedFields = [
            'name',
            'code',
            'swift',
            'country',
            'active',
            'description',
        ];

        $this->assertEquals($expectedFields, $fieldNames);
    }

    #[Test]
    public function required_fields_are_properly_set(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);

        $fieldList = collect($fields);
        $matches = $fieldList->where('required', true);
        $values = $matches->pluck('name');
        $requiredFields = $values->toArray();
        $matches = $fieldList->where('required', false);
        $values = $matches->pluck('name');
        $optionalFields = $values->toArray();

        $this->assertContains('name', $requiredFields);
        $this->assertContains('code', $requiredFields);
        $this->assertContains('country', $requiredFields);

        $this->assertContains('swift', $optionalFields);
        $this->assertContains('description', $optionalFields);
    }

    #[Test]
    public function field_types_are_appropriate(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);

        $collection = collect($fields);
        $values = $collection->pluck('type', 'name');
        $fieldTypes = $values->toArray();

        $this->assertEquals('text', $fieldTypes['name']);
        $this->assertEquals('text', $fieldTypes['code']);
        $this->assertEquals('text', $fieldTypes['swift']);
        $this->assertEquals('select_from_array', $fieldTypes['country']);
        $this->assertEquals('boolean', $fieldTypes['active']);
        $this->assertEquals('textarea', $fieldTypes['description']);
    }

    #[Test]
    public function default_values_are_set_correctly(): void
    {
        $fields = BankFormFields::fields(self::COUNTRIES);

        $fieldList = collect($fields);
        $countryField = $fieldList->firstWhere('name', 'country');
        $this->assertEquals('CZ', $countryField['default']);

        $activeField = $fieldList->firstWhere('name', 'active');
        $this->assertTrue($activeField['default']);
    }
}
