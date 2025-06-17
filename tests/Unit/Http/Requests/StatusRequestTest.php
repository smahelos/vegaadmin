<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StatusRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusRequestTest extends TestCase
{
    private StatusRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StatusRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function validation_rules_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function authorize_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    #[Test]
    public function attributes_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'attributes'));
    }

    #[Test]
    public function messages_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
    }

    #[Test]
    public function request_class_namespace_is_correct(): void
    {
        $this->assertEquals('App\Http\Requests\StatusRequest', get_class($this->request));
    }

    #[Test]
    public function authorize_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionMethod($this->request, 'authorize');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('bool', $reflection->getReturnType()?->getName());
    }

    #[Test]
    public function rules_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionMethod($this->request, 'rules');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('array', $reflection->getReturnType()?->getName());
    }

    #[Test]
    public function attributes_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionMethod($this->request, 'attributes');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('array', $reflection->getReturnType()?->getName());
    }

    #[Test]
    public function messages_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionMethod($this->request, 'messages');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('array', $reflection->getReturnType()?->getName());
    }
}
