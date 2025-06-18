<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\UserRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRequestTest extends TestCase
{
    private UserRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UserRequest();
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
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function is_create_operation_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('isCreateOperation');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function has_required_methods(): void
    {
        $requiredMethods = [
            'authorize',
            'rules',
            'attributes',
            'messages',
            'isCreateOperation'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->request, $method),
                "Method {$method} does not exist in UserRequest class"
            );
        }
    }

    #[Test]
    public function is_create_operation_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('isCreateOperation');
        
        $this->assertTrue($method->isPublic());
    }
}
