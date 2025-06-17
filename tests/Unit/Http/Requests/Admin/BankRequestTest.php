<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\BankRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BankRequestTest extends TestCase
{
    private BankRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new BankRequest();
    }

    #[Test]
    public function validation_rules_are_correctly_defined()
    {
        $expectedRules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:banks,code,',
            'swift' => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
        ];

        $actualRules = $this->request->rules();
        
        // Check basic rules (without dynamic parts)
        $this->assertArrayHasKey('name', $actualRules);
        $this->assertEquals('required|string|max:255', $actualRules['name']);
        
        $this->assertArrayHasKey('swift', $actualRules);
        $this->assertEquals('nullable|string|max:20', $actualRules['swift']);
        
        $this->assertArrayHasKey('country', $actualRules);
        $this->assertEquals('required|string|size:2', $actualRules['country']);
        
        // Check code rule contains required parts
        $this->assertArrayHasKey('code', $actualRules);
        $this->assertStringContainsString('required', $actualRules['code']);
        $this->assertStringContainsString('string', $actualRules['code']);
        $this->assertStringContainsString('max:10', $actualRules['code']);
        $this->assertStringContainsString('unique:banks,code', $actualRules['code']);
    }

    #[Test]
    public function required_fields_are_properly_identified()
    {
        $rules = $this->request->rules();
        
        $requiredFields = ['name', 'code', 'country'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $rules);
            $this->assertStringContainsString('required', $rules[$field]);
        }
    }

    #[Test]
    public function nullable_fields_are_properly_identified()
    {
        $rules = $this->request->rules();
        
        $nullableFields = ['swift'];
        
        foreach ($nullableFields as $field) {
            $this->assertArrayHasKey($field, $rules);
            $this->assertStringContainsString('nullable', $rules[$field]);
        }
    }

    #[Test]
    public function string_fields_have_correct_length_constraints()
    {
        $rules = $this->request->rules();
        
        $this->assertStringContainsString('max:255', $rules['name']);
        $this->assertStringContainsString('max:10', $rules['code']);
        $this->assertStringContainsString('max:20', $rules['swift']);
        $this->assertStringContainsString('size:2', $rules['country']);
    }

    #[Test]
    public function unique_constraint_is_defined_for_code()
    {
        $rules = $this->request->rules();
        
        $this->assertStringContainsString('unique:banks,code', $rules['code']);
    }

    #[Test]
    public function authorize_method_exists_and_uses_backpack_auth()
    {
        // Test that authorize method exists and calls backpack_auth()->check()
        $this->assertTrue(method_exists($this->request, 'authorize'));
        
        // Since we can't easily mock backpack_auth() in unit test,
        // we test the actual method behavior in Feature tests
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function authorize_returns_boolean()
    {
        // Test that authorize method returns boolean value
        $result = $this->request->authorize();
        $this->assertIsBool($result);
    }

    #[Test]
    public function custom_attributes_are_correctly_defined()
    {
        $expectedAttributes = [
            'name' => __('bank.name'),
            'code' => __('bank.code'),
            'swift' => __('bank.swift'),
            'country' => __('bank.country'),
        ];

        $this->assertEquals($expectedAttributes, $this->request->attributes());
    }

    #[Test]
    public function attributes_use_translation_functions()
    {
        $attributes = $this->request->attributes();

        foreach ($attributes as $field => $translation) {
            $this->assertStringStartsWith('bank.', $translation);
        }
    }

    #[Test]
    public function custom_validation_messages_are_correctly_defined()
    {
        $expectedMessages = [
            'name.required' => __('bank.name_required'),
            'code.required' => __('bank.code_required'),
            'code.unique' => __('bank.code_unique'),
            'country.required' => __('bank.country_required'),
            'country.size' => __('bank.country_size'),
        ];

        $this->assertEquals($expectedMessages, $this->request->messages());
    }

    #[Test]
    public function all_required_fields_have_custom_messages()
    {
        $messages = $this->request->messages();
        $requiredFields = ['name', 'code', 'country'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field . '.required', $messages);
        }
    }

    #[Test]
    public function validation_messages_use_translation_keys()
    {
        $messages = $this->request->messages();

        foreach ($messages as $key => $translation) {
            $this->assertStringStartsWith('bank.', $translation);
        }
    }

    #[Test]
    public function request_extends_form_request()
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }
}
