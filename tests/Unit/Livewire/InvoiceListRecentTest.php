<?php

namespace Tests\Unit\Livewire;

use App\Livewire\InvoiceListRecent;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListRecentTest extends TestCase
{
    private InvoiceListRecent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new InvoiceListRecent();
    }

    #[Test]
    public function component_extends_livewire_component(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
    }

    #[Test]
    public function component_uses_with_pagination_trait(): void
    {
        $this->assertContains(WithPagination::class, class_uses($this->component));
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        $this->assertTrue($reflection->hasProperty('orderBy'));
        $this->assertTrue($reflection->hasProperty('orderAsc'));
        $this->assertTrue($reflection->hasProperty('errorMessage'));
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        
        $this->assertTrue($orderByProperty->isPublic());
        $this->assertTrue($orderAscProperty->isPublic());
        $this->assertTrue($errorMessageProperty->isPublic());
    }

    #[Test]
    public function component_has_correct_default_values(): void
    {
        $this->assertEquals('created_at', $this->component->orderBy);
        $this->assertFalse($this->component->orderAsc);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function component_has_correct_property_types(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        
        $this->assertEquals('string', $orderByProperty->getType()->getName());
        $this->assertEquals('bool', $orderAscProperty->getType()->getName());
        $this->assertTrue($errorMessageProperty->getType()->allowsNull());
    }

    #[Test]
    public function mount_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'mount'));
    }

    #[Test]
    public function mount_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('mount');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function sort_by_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'sortBy'));
    }

    #[Test]
    public function sort_by_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function sort_by_method_has_correct_parameter_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('field', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function render_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'render'));
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Contracts\View\View', $returnType->getName());
    }

    #[Test]
    public function component_has_correct_namespace(): void
    {
        $this->assertEquals('App\Livewire', (new \ReflectionClass($this->component))->getNamespaceName());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use App\Models\Invoice;', $fileContent);
        $this->assertStringContainsString('use Livewire\Component;', $fileContent);
        $this->assertStringContainsString('use Livewire\WithPagination;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $fileContent);
        $this->assertStringContainsString('use Livewire\Attributes\Url;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Contracts\View\View;', $fileContent);
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('InvoiceListRecent Livewire Component', $docComment);
    }

    #[Test]
    public function component_has_url_attributes(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        
        $orderByAttributes = $orderByProperty->getAttributes();
        $orderAscAttributes = $orderAscProperty->getAttributes();
        
        $this->assertCount(1, $orderByAttributes);
        $this->assertCount(1, $orderAscAttributes);
        
        $this->assertEquals('Livewire\Attributes\Url', $orderByAttributes[0]->getName());
        $this->assertEquals('Livewire\Attributes\Url', $orderAscAttributes[0]->getName());
    }

    #[Test]
    public function component_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        // Check if all required methods are public
        $this->assertTrue($reflection->getMethod('mount')->isPublic());
        $this->assertTrue($reflection->getMethod('sortBy')->isPublic());
        $this->assertTrue($reflection->getMethod('render')->isPublic());
        
        // Check that component extends the right class
        $this->assertEquals('Livewire\Component', $reflection->getParentClass()->getName());
    }
}
