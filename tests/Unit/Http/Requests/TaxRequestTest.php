<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TaxRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TaxRequestTest extends TestCase
{
    private TaxRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TaxRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_returns_true(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function validation_rules_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function validation_rules_returns_array(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
    }

    #[Test]
    public function attributes_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'attributes'));
    }

    #[Test]
    public function attributes_returns_array(): void
    {
        $attributes = $this->request->attributes();
        
        $this->assertIsArray($attributes);
    }

    #[Test]
    public function messages_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
    }

    #[Test]
    public function messages_returns_array(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
    }

    #[Test]
    public function authorize_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }
}
