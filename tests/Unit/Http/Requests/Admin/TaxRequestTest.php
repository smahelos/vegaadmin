<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\TaxRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class TaxRequestTest extends TestCase
{
    private TaxRequest $request;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TaxRequest();
        $this->reflection = new ReflectionClass($this->request);
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function has_rules_method(): void
    {
        $this->assertTrue($this->reflection->hasMethod('rules'));
        
        $rulesMethod = $this->reflection->getMethod('rules');
        $this->assertTrue($rulesMethod->isPublic());
        $this->assertEquals('array', $rulesMethod->getReturnType()?->getName());
    }

    #[Test]
    public function has_authorize_method(): void
    {
        $this->assertTrue($this->reflection->hasMethod('authorize'));
        
        $authorizeMethod = $this->reflection->getMethod('authorize');
        $this->assertTrue($authorizeMethod->isPublic());
        $this->assertEquals('bool', $authorizeMethod->getReturnType()?->getName());
    }

    #[Test]
    public function has_attributes_method(): void
    {
        $this->assertTrue($this->reflection->hasMethod('attributes'));
        
        $attributesMethod = $this->reflection->getMethod('attributes');
        $this->assertTrue($attributesMethod->isPublic());
        $this->assertEquals('array', $attributesMethod->getReturnType()?->getName());
    }

    #[Test]
    public function has_messages_method(): void
    {
        $this->assertTrue($this->reflection->hasMethod('messages'));
        
        $messagesMethod = $this->reflection->getMethod('messages');
        $this->assertTrue($messagesMethod->isPublic());
        $this->assertEquals('array', $messagesMethod->getReturnType()?->getName());
    }

    #[Test]
    public function rules_method_has_correct_signature(): void
    {
        $rulesMethod = $this->reflection->getMethod('rules');
        $parameters = $rulesMethod->getParameters();
        
        $this->assertCount(0, $parameters);
        $this->assertEquals('array', $rulesMethod->getReturnType()?->getName());
    }

    #[Test]
    public function authorize_method_has_correct_signature(): void
    {
        $authorizeMethod = $this->reflection->getMethod('authorize');
        $parameters = $authorizeMethod->getParameters();
        
        $this->assertCount(0, $parameters);
        $this->assertEquals('bool', $authorizeMethod->getReturnType()?->getName());
    }

    #[Test]
    public function attributes_method_has_correct_signature(): void
    {
        $attributesMethod = $this->reflection->getMethod('attributes');
        $parameters = $attributesMethod->getParameters();
        
        $this->assertCount(0, $parameters);
        $this->assertEquals('array', $attributesMethod->getReturnType()?->getName());
    }

    #[Test]
    public function messages_method_has_correct_signature(): void
    {
        $messagesMethod = $this->reflection->getMethod('messages');
        $parameters = $messagesMethod->getParameters();
        
        $this->assertCount(0, $parameters);
        $this->assertEquals('array', $messagesMethod->getReturnType()?->getName());
    }
}
