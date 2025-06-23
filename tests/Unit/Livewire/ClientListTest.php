<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ClientList;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientListTest extends TestCase
{
    private ClientList $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new ClientList();
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
        $expectedProperties = ['search', 'status', 'orderBy', 'orderAsc', 'page', 'errorMessage'];
        
        foreach ($expectedProperties as $property) {
            $this->assertTrue(property_exists($this->component, $property), "Property {$property} should exist");
        }
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
        $this->assertEquals('App\Livewire\ClientList', get_class($this->component));
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $fileName = $reflection->getFileName();
        $fileContent = file_get_contents($fileName);
        
        $this->assertStringContainsString('use App\Models\Client;', $fileContent);
        $this->assertStringContainsString('use Livewire\Attributes\Url;', $fileContent);
        $this->assertStringContainsString('use Livewire\Component;', $fileContent);
        $this->assertStringContainsString('use Livewire\WithPagination;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $fileContent);
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('ClientList Livewire Component', $docComment);
    }

    #[Test]
    public function component_has_pagination_theme_property(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasProperty('paginationTheme'));
        
        $property = $reflection->getProperty('paginationTheme');
        $this->assertTrue($property->isProtected());
    }

    #[Test]
    public function component_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->component);
        
        // Verify it's a concrete class
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Verify expected method count (approximate)
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertGreaterThanOrEqual(6, count($methods));
    }
}
