<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\ArtisanCommandCategoryRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandCategoryRequestTest extends TestCase
{
    private ArtisanCommandCategoryRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ArtisanCommandCategoryRequest();
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
        
        $this->assertNotNull($returnType, 'ArtisanCommandCategoryRequest rules() method should have return type annotation');
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'ArtisanCommandCategoryRequest attributes() method should have return type annotation');
        $this->assertEquals('array', $returnType->getName());
    }
}
