<?php

namespace Tests\Unit\View\Components;

use App\View\Components\ApplicationLogo;
use Illuminate\View\Component;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationLogoTest extends TestCase
{
    private ApplicationLogo $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new ApplicationLogo();
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(ApplicationLogo::class, $this->component);
    }

    #[Test]
    public function component_extends_component_class(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
    }

    #[Test]
    public function render_method_exists_and_is_public(): void
    {
        $this->assertTrue(method_exists($this->component, 'render'));
        
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function render_method_has_no_parameters(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $method = $reflection->getMethod('render');
        $parameters = $method->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_class_structure(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        
        // Should not be abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Should extend Component
        $this->assertTrue($reflection->isSubclassOf(Component::class));
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the application logo component', $docComment);
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }

    #[Test]
    public function component_has_correct_method_count(): void
    {
        $reflection = new \ReflectionClass(ApplicationLogo::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Should have only the render method (plus inherited methods)
        $componentMethods = array_filter($publicMethods, function($method) {
            return $method->getDeclaringClass()->getName() === ApplicationLogo::class;
        });
        
        $this->assertCount(1, $componentMethods);
        $this->assertEquals('render', reset($componentMethods)->getName());
    }
}
