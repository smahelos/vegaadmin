<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\InvoiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceRequestTest extends TestCase
{
    private InvoiceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new InvoiceRequest();
    }

    #[Test]
    public function request_extends_base_entity_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
        $this->assertEquals('App\Http\Requests\Admin\BaseEntityRequest', get_parent_class($this->request));
    }

    #[Test]
    public function get_entity_type_returns_invoice(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);

        $this->assertEquals('invoice', $method->invoke($this->request));
    }

    #[Test]
    public function get_required_permission_returns_correct_permission(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('getRequiredPermission');
        $method->setAccessible(true);

        $this->assertEquals('can_create_edit_invoice', $method->invoke($this->request));
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }

    #[Test]
    public function authorize_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', (string) $returnType);
    }

    #[Test]
    public function class_implements_required_methods(): void
    {
        $reflection = new \ReflectionClass($this->request);

        // Test that all required methods exist
        $this->assertTrue($reflection->hasMethod('authorize'));
        $this->assertTrue($reflection->hasMethod('rules'));
        $this->assertTrue($reflection->hasMethod('attributes'));
        $this->assertTrue($reflection->hasMethod('messages'));
        $this->assertTrue($reflection->hasMethod('getEntityType'));
        $this->assertTrue($reflection->hasMethod('getRequiredPermission'));
    }

    #[Test]
    public function all_methods_have_correct_visibility(): void
    {
        $reflection = new \ReflectionClass($this->request);

        // Test public methods
        $this->assertTrue($reflection->getMethod('authorize')->isPublic());
        $this->assertTrue($reflection->getMethod('rules')->isPublic());
        $this->assertTrue($reflection->getMethod('attributes')->isPublic());
        $this->assertTrue($reflection->getMethod('messages')->isPublic());

        // Test protected methods
        $this->assertTrue($reflection->getMethod('getEntityType')->isProtected());
        $this->assertTrue($reflection->getMethod('getRequiredPermission')->isProtected());
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass($this->request);

        // Test that class is concrete (not abstract)
        $this->assertFalse($reflection->isAbstract());

        // Test that class is instantiable
        $this->assertTrue($reflection->isInstantiable());

        // Test namespace
        $this->assertEquals('App\Http\Requests\Admin', $reflection->getNamespaceName());

        // Test class name
        $this->assertEquals('InvoiceRequest', $reflection->getShortName());
    }

    #[Test]
    public function entity_type_and_permission_are_consistent(): void
    {
        $reflection = new \ReflectionClass($this->request);

        $entityTypeMethod = $reflection->getMethod('getEntityType');
        $entityTypeMethod->setAccessible(true);
        $entityType = $entityTypeMethod->invoke($this->request);

        $permissionMethod = $reflection->getMethod('getRequiredPermission');
        $permissionMethod->setAccessible(true);
        $permission = $permissionMethod->invoke($this->request);

        // Test that permission contains entity type
        $this->assertStringContainsString($entityType, $permission);

        // Test expected pattern: 'can_create_edit_[entity]'
        $expectedPermission = 'can_create_edit_' . $entityType;
        $this->assertEquals($expectedPermission, $permission);
    }
}
