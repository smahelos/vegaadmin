<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ClientListLatest;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ReflectionClass;

class ClientListLatestTest extends TestCase
{
    private ClientListLatest $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new ClientListLatest();
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
        
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $this->assertTrue($orderAscProperty->isPublic());
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $this->assertTrue($orderByProperty->isPublic());
        
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        $this->assertTrue($errorMessageProperty->isPublic());
    }

    #[Test]
    public function component_has_correct_default_values(): void
    {
        $this->assertFalse($this->component->orderAsc);
        $this->assertEquals('created_at', $this->component->orderBy);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function component_has_correct_property_types(): void
    {
        $reflection = new ReflectionClass($this->component);
        
        $orderAscProperty = $reflection->getProperty('orderAsc');
        $orderAscType = $orderAscProperty->getType();
        $this->assertEquals('bool', $orderAscType->getName());
        
        $orderByProperty = $reflection->getProperty('orderBy');
        $orderByType = $orderByProperty->getType();
        $this->assertEquals('string', $orderByType->getName());
        
        $errorMessageProperty = $reflection->getProperty('errorMessage');
        $errorMessageType = $errorMessageProperty->getType();
        $this->assertEquals('string', $errorMessageType->getName());
        $this->assertTrue($errorMessageType->allowsNull());
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
        $fileContent = file_get_contents(app_path('Livewire/ClientListLatest.php'));
        
        $this->assertStringContainsString('use App\Models\Client;', $fileContent);
        $this->assertStringContainsString('use Livewire\Component;', $fileContent);
        $this->assertStringContainsString('use Livewire\WithPagination;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $fileContent);
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('ClientListLatest Livewire Component', $docComment);
        $this->assertStringContainsString('latest clients', $docComment);
    }

    #[Test]
    public function sort_by_method_has_proper_docblock(): void
    {
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param string $field', $docComment);
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('Render the component', $docComment);
    }

    #[Test]
    public function component_structure_validation(): void
    {
        $reflection = new ReflectionClass($this->component);
        
        // Check that class is not abstract or final
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        
        // Check that it has the expected methods
        $expectedMethods = ['sortBy', 'render'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method $method should exist");
        }
    }
}
