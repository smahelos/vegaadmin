<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\SubscriptionPlanFeatureRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SubscriptionPlanFeatureRequestTest extends TestCase
{
    private SubscriptionPlanFeatureRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new SubscriptionPlanFeatureRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_has_bool_return_type(): void
    {
        $ref = new \ReflectionClass($this->request);
        $method = $ref->getMethod('authorize');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', (string)$returnType);
    }

    #[Test]
    public function rules_has_array_return_type(): void
    {
        $ref = new \ReflectionClass($this->request);
        $method = $ref->getMethod('rules');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function attributes_has_array_return_type(): void
    {
        $ref = new \ReflectionClass($this->request);
        $method = $ref->getMethod('attributes');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function messages_has_array_return_type(): void
    {
        $ref = new \ReflectionClass($this->request);
        $method = $ref->getMethod('messages');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function rules_contain_expected_keys(): void
    {
        $rules = $this->request->rules();
        foreach (['name','slug','description','is_active','sort_order'] as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    #[Test]
    public function attributes_method_exists_and_is_public(): void
    {
        $ref = new \ReflectionClass($this->request);
        $this->assertTrue($ref->hasMethod('attributes'));
        $this->assertTrue($ref->getMethod('attributes')->isPublic());
    }

    #[Test]
    public function messages_method_exists_and_is_public(): void
    {
        $ref = new \ReflectionClass($this->request);
        $this->assertTrue($ref->hasMethod('messages'));
        $this->assertTrue($ref->getMethod('messages')->isPublic());
    }

    #[Test]
    public function prepare_for_validation_exists_and_is_protected(): void
    {
        $ref = new \ReflectionClass($this->request);
        $this->assertTrue($ref->hasMethod('prepareForValidation'));
        $method = $ref->getMethod('prepareForValidation');
        $this->assertTrue($method->isProtected());
    }
}
