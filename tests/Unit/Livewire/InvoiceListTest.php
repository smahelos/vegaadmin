<?php

namespace Tests\Unit\Livewire;

use App\Livewire\InvoiceList;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ReflectionClass;

class InvoiceListTest extends TestCase
{
    private InvoiceList $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new InvoiceList();
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
        $reflection = new ReflectionClass($this->component);
        
        $searchProperty = $reflection->getProperty('search');
        $this->assertTrue($searchProperty->isPublic());
        
        $statusProperty = $reflection->getProperty('status');
        $this->assertTrue($statusProperty->isPublic());
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $this->assertTrue($orderByProperty->isPublic());
        
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $this->assertTrue($orderAscProperty->isPublic());
        
        $pageProperty = $reflection->getProperty('page');
        $this->assertTrue($pageProperty->isPublic());
        
        $errorMessageProperty = $reflection->getProperty('errorMessage');
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
        $reflection = new ReflectionClass($this->component);
        
        $searchProperty = $reflection->getProperty('search');
        $searchType = $searchProperty->getType();
        $this->assertEquals('string', $searchType->getName());
        
        $statusProperty = $reflection->getProperty('status');
        $statusType = $statusProperty->getType();
        $this->assertEquals('string', $statusType->getName());
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderByType = $orderByProperty->getType();
        $this->assertEquals('string', $orderByType->getName());
        
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $orderAscType = $orderAscProperty->getType();
        $this->assertEquals('bool', $orderAscType->getName());
        
        $pageProperty = $reflection->getProperty('page');
        $pageType = $pageProperty->getType();
        $this->assertEquals('int', $pageType->getName());
        
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        $errorMessageType = $errorMessageProperty->getType();
        $this->assertEquals('string', $errorMessageType->getName());
        $this->assertTrue($errorMessageType->allowsNull());
    }

    #[Test]
    public function mount_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'mount'));
    }

    #[Test]
    public function mount_method_has_correct_return_type(): void
    {
        $reflection = new ReflectionClass($this->component);
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
        $reflection = new ReflectionClass($this->component);
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
        $reflection = new ReflectionClass($this->component);
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
        $reflection = new ReflectionClass($this->component);
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
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function sort_by_method_has_correct_parameter_type(): void
    {
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $fieldParam = $parameters[0];
        $this->assertEquals('field', $fieldParam->getName());
        
        $paramType = $fieldParam->getType();
        $this->assertNotNull($paramType);
        $this->assertEquals('string', $paramType->getName());
    }

    #[Test]
    public function render_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'render'));
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Contracts\View\View', $returnType->getName());
    }

    #[Test]
    public function component_has_correct_namespace(): void
    {
        $reflection = new ReflectionClass($this->component);
        $this->assertEquals('App\Livewire', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $fileContent = file_get_contents(app_path('Livewire/InvoiceList.php'));
        
        $this->assertStringContainsString('use App\Models\Invoice;', $fileContent);
        $this->assertStringContainsString('use Livewire\Component;', $fileContent);
        $this->assertStringContainsString('use Livewire\WithPagination;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $fileContent);
        $this->assertStringContainsString('use Livewire\Attributes\Url;', $fileContent);
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('InvoiceList Livewire Component', $docComment);
        $this->assertStringContainsString('invoices', $docComment);
    }

    #[Test]
    public function component_has_url_attributes(): void
    {
        $reflection = new ReflectionClass($this->component);
        
        $searchProperty = $reflection->getProperty('search');
        $attributes = $searchProperty->getAttributes();
        $this->assertCount(1, $attributes);
        
        $statusProperty = $reflection->getProperty('status');
        $attributes = $statusProperty->getAttributes();
        $this->assertCount(1, $attributes);
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $attributes = $orderByProperty->getAttributes();
        $this->assertCount(1, $attributes);
        
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $attributes = $orderAscProperty->getAttributes();
        $this->assertCount(1, $attributes);
        
        $pageProperty = $reflection->getProperty('page');
        $attributes = $pageProperty->getAttributes();
        $this->assertCount(1, $attributes);
    }

    #[Test]
    public function component_has_pagination_theme_property(): void
    {
        $reflection = new ReflectionClass($this->component);
        $this->assertTrue($reflection->hasProperty('paginationTheme'));
        
        $property = $reflection->getProperty('paginationTheme');
        $this->assertTrue($property->isProtected());
    }

    #[Test]
    public function component_structure_validation(): void
    {
        $reflection = new ReflectionClass($this->component);
        
        // Check that class is not abstract or final
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        
        // Check that it has the expected methods
        $expectedMethods = ['mount', 'updatingSearch', 'updatingStatus', 'resetFilters', 'sortBy', 'render'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method $method should exist");
        }
    }
}
