<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ProductList;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    private ProductList $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new ProductList();
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
        
        $this->assertTrue($reflection->hasProperty('search'));
        $this->assertTrue($reflection->hasProperty('status'));
        $this->assertTrue($reflection->hasProperty('orderBy'));
        $this->assertTrue($reflection->hasProperty('orderAsc'));
        $this->assertTrue($reflection->hasProperty('page'));
        $this->assertTrue($reflection->hasProperty('errorMessage'));
        
        $searchProperty = $reflection->getProperty('search');
        $statusProperty = $reflection->getProperty('status');
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $pageProperty = $reflection->getProperty('page');
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        
        $this->assertTrue($searchProperty->isPublic());
        $this->assertTrue($statusProperty->isPublic());
        $this->assertTrue($orderByProperty->isPublic());
        $this->assertTrue($orderAscProperty->isPublic());
        $this->assertTrue($pageProperty->isPublic());
        $this->assertTrue($errorMessageProperty->isPublic());
    }

    #[Test]
    public function component_has_correct_default_values(): void
    {
        $this->assertEquals('', $this->component->search);
        $this->assertEquals('', $this->component->status);
        $this->assertEquals('created_at', $this->component->orderBy);
        $this->assertFalse($this->component->orderAsc);
        $this->assertEquals(1, $this->component->page);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function component_has_correct_property_types(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        $searchProperty = $reflection->getProperty('search');
        $statusProperty = $reflection->getProperty('status');
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $pageProperty = $reflection->getProperty('page');
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        
        $this->assertEquals('string', $searchProperty->getType()->getName());
        $this->assertEquals('string', $statusProperty->getType()->getName());
        $this->assertEquals('string', $orderByProperty->getType()->getName());
        $this->assertEquals('bool', $orderAscProperty->getType()->getName());
        $this->assertEquals('int', $pageProperty->getType()->getName());
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
    public function updating_search_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'updatingSearch'));
    }

    #[Test]
    public function updating_search_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('updatingSearch');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function updating_status_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'updatingStatus'));
    }

    #[Test]
    public function updating_status_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('updatingStatus');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function reset_filters_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'resetFilters'));
    }

    #[Test]
    public function reset_filters_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('resetFilters');
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
        
        $this->assertStringContainsString('use App\Models\Product;', $fileContent);
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
        $this->assertStringContainsString('ProductList Livewire Component', $docComment);
    }

    #[Test]
    public function component_has_url_attributes(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        $searchProperty = $reflection->getProperty('search');
        $statusProperty = $reflection->getProperty('status');
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $pageProperty = $reflection->getProperty('page');
        
        $searchAttributes = $searchProperty->getAttributes();
        $statusAttributes = $statusProperty->getAttributes();
        $orderByAttributes = $orderByProperty->getAttributes();
        $orderAscAttributes = $orderAscProperty->getAttributes();
        $pageAttributes = $pageProperty->getAttributes();
        
        $this->assertCount(1, $searchAttributes);
        $this->assertCount(1, $statusAttributes);
        $this->assertCount(1, $orderByAttributes);
        $this->assertCount(1, $orderAscAttributes);
        $this->assertCount(1, $pageAttributes);
        
        $this->assertEquals('Livewire\Attributes\Url', $searchAttributes[0]->getName());
        $this->assertEquals('Livewire\Attributes\Url', $statusAttributes[0]->getName());
        $this->assertEquals('Livewire\Attributes\Url', $orderByAttributes[0]->getName());
        $this->assertEquals('Livewire\Attributes\Url', $orderAscAttributes[0]->getName());
        $this->assertEquals('Livewire\Attributes\Url', $pageAttributes[0]->getName());
    }

    #[Test]
    public function component_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        // Check if all required methods are public
        $this->assertTrue($reflection->getMethod('mount')->isPublic());
        $this->assertTrue($reflection->getMethod('updatingSearch')->isPublic());
        $this->assertTrue($reflection->getMethod('updatingStatus')->isPublic());
        $this->assertTrue($reflection->getMethod('resetFilters')->isPublic());
        $this->assertTrue($reflection->getMethod('sortBy')->isPublic());
        $this->assertTrue($reflection->getMethod('render')->isPublic());
        
        // Check that component extends the right class
        $this->assertEquals('Livewire\Component', $reflection->getParentClass()->getName());
    }
}
