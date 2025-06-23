<?php

namespace Tests\Unit\View\Components;

use App\View\Components\DropdownLink;
use Illuminate\View\Component;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DropdownLinkTest extends TestCase
{
    private DropdownLink $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new DropdownLink('/test-url');
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(DropdownLink::class, $this->component);
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
        
        $reflection = new \ReflectionClass(DropdownLink::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function constructor_sets_href_correctly(): void
    {
        $href = '/custom-url';
        $component = new DropdownLink($href);
        
        $this->assertEquals($href, $component->href);
    }

    #[Test]
    public function constructor_accepts_different_href_values(): void
    {
        $testUrls = [
            '/dashboard',
            'https://example.com',
            '#',
            'mailto:test@example.com',
            'javascript:void(0)'
        ];
        
        foreach ($testUrls as $url) {
            $component = new DropdownLink($url);
            $this->assertEquals($url, $component->href);
        }
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $this->assertContains('href', $propertyNames);
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the dropdown link component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('string $href', $docComment);
    }

    #[Test]
    public function constructor_has_required_parameter(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('href', $parameters[0]->getName());
        $this->assertFalse($parameters[0]->isOptional());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }

    #[Test]
    public function href_property_is_documented(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $hrefProperty = $reflection->getProperty('href');
        
        $this->assertNotFalse($hrefProperty->getDocComment());
        $this->assertStringContainsString('@var string', $hrefProperty->getDocComment());
        $this->assertStringContainsString('Link target URL', $hrefProperty->getDocComment());
    }

    #[Test]
    public function render_method_returns_correct_view(): void
    {
        $reflection = new \ReflectionClass(DropdownLink::class);
        $method = $reflection->getMethod('render');
        
        // Check that the method body contains the expected view call
        $fileContent = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString("view('components.dropdown-link'", $fileContent);
    }
}
