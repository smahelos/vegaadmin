<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\EntityUsageRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EntityUsageRequestTest extends TestCase
{
    private EntityUsageRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new EntityUsageRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('bool', $returnType->getName());
        } else {
            $this->fail('authorize return type is not a named type');
        }
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('rules return type is not a named type');
        }
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('attributes return type is not a named type');
        }
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('messages return type is not a named type');
        }
    }

    #[Test]
    public function authorize_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function rules_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function attributes_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function messages_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $this->assertTrue($method->isPublic());
    }
}
