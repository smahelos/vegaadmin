<?php

namespace Tests\Unit\View\Components;

use App\View\Components\ResponsiveNavLink;
use Illuminate\View\Component;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponsiveNavLinkTest extends TestCase
{
    private ResponsiveNavLink $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new ResponsiveNavLink('/test-url');
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(ResponsiveNavLink::class, $this->component);
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
        
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function constructor_sets_default_values_correctly(): void
    {
        $href = '/test-url';
        $component = new ResponsiveNavLink($href);
        
        $this->assertEquals($href, $component->href);
        $this->assertFalse($component->active); // Default should be false
    }

    #[Test]
    public function constructor_accepts_custom_values(): void
    {
        $href = '/custom-url';
        $active = true;
        $component = new ResponsiveNavLink($href, $active);
        
        $this->assertEquals($href, $component->href);
        $this->assertTrue($component->active);
    }

    #[Test]
    public function constructor_accepts_different_href_and_active_combinations(): void
    {
        $testCases = [
            ['/dashboard', false],
            ['/users', true],
            ['https://example.com', false],
            ['#mobile-menu', true],
            ['', false]
        ];
        
        foreach ($testCases as [$url, $isActive]) {
            $component = new ResponsiveNavLink($url, $isActive);
            $this->assertEquals($url, $component->href);
            $this->assertEquals($isActive, $component->active);
        }
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $expectedProperties = ['href', 'active'];
        
        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $propertyNames);
        }
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the responsive nav link component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('string $href', $docComment);
        $this->assertStringContainsString('bool $active', $docComment);
    }

    #[Test]
    public function constructor_parameters_have_correct_types(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(2, $parameters);
        
        // Check parameter names
        $this->assertEquals('href', $parameters[0]->getName());
        $this->assertEquals('active', $parameters[1]->getName());
        
        // Check required vs optional
        $this->assertFalse($parameters[0]->isOptional());
        $this->assertTrue($parameters[1]->isOptional());
        
        // Check default value for active parameter
        $this->assertFalse($parameters[1]->getDefaultValue());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }

    #[Test]
    public function component_properties_are_documented(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $hrefProperty = $reflection->getProperty('href');
        $activeProperty = $reflection->getProperty('active');
        
        // Check docblocks exist
        $this->assertNotFalse($hrefProperty->getDocComment());
        $this->assertNotFalse($activeProperty->getDocComment());
        
        // Check docblock content
        $this->assertStringContainsString('@var string', $hrefProperty->getDocComment());
        $this->assertStringContainsString('@var bool', $activeProperty->getDocComment());
        $this->assertStringContainsString('Link target URL', $hrefProperty->getDocComment());
        $this->assertStringContainsString('Whether the link is active', $activeProperty->getDocComment());
    }

    #[Test]
    public function render_method_returns_correct_view(): void
    {
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        
        // Check that the method body contains the expected view call
        $fileContent = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString("view('components.responsive-nav-link')", $fileContent);
    }

    #[Test]
    public function component_naming_is_consistent_with_purpose(): void
    {
        // Verify this component is indeed for responsive navigation
        $reflection = new \ReflectionClass(ResponsiveNavLink::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Check class name reflects its responsive nature
        $this->assertEquals('ResponsiveNavLink', $reflection->getShortName());
        
        // Check view name reflects responsive nature
        $this->assertStringContainsString('responsive-nav-link', $fileContent);
    }
}
