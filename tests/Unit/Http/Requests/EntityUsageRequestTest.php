<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\EntityUsageRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityUsageRequest (frontend)
 *
 * Focus ONLY on structure & method signatures (no Laravel runtime dependencies) per Unit Test Isolation rules.
 */
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
    public function methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        foreach (['authorize','rules','attributes','messages'] as $name) {
            $this->assertTrue($reflection->hasMethod($name));
            $this->assertTrue($reflection->getMethod($name)->isPublic());
        }
    }
}
