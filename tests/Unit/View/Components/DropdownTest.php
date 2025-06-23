<?php

namespace Tests\Unit\View\Components;

use App\View\Components\Dropdown;
use Illuminate\View\Component;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DropdownTest extends TestCase
{
    private Dropdown $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new Dropdown();
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(Dropdown::class, $this->component);
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
        
        $reflection = new \ReflectionClass(Dropdown::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function constructor_sets_default_values_correctly(): void
    {
        $component = new Dropdown();
        
        $this->assertEquals('right', $component->align);
        $this->assertEquals(48, $component->width);
        $this->assertEquals('', $component->contentClasses);
    }

    #[Test]
    public function constructor_accepts_custom_values(): void
    {
        $component = new Dropdown('left', 64, 'custom-class');
        
        $this->assertEquals('left', $component->align);
        $this->assertEquals(64, $component->width);
        $this->assertEquals('custom-class', $component->contentClasses);
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $expectedProperties = ['align', 'width', 'contentClasses'];
        
        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $propertyNames);
        }
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the dropdown component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
    }

    #[Test]
    public function constructor_parameters_have_correct_types(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(3, $parameters);
        
        // Check parameter names
        $this->assertEquals('align', $parameters[0]->getName());
        $this->assertEquals('width', $parameters[1]->getName());
        $this->assertEquals('contentClasses', $parameters[2]->getName());
        
        // Check default values
        $this->assertEquals('right', $parameters[0]->getDefaultValue());
        $this->assertEquals(48, $parameters[1]->getDefaultValue());
        $this->assertEquals('', $parameters[2]->getDefaultValue());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }

    #[Test]
    public function component_properties_are_documented(): void
    {
        $reflection = new \ReflectionClass(Dropdown::class);
        $alignProperty = $reflection->getProperty('align');
        $widthProperty = $reflection->getProperty('width');
        $contentClassesProperty = $reflection->getProperty('contentClasses');
        
        // Check docblocks exist
        $this->assertNotFalse($alignProperty->getDocComment());
        $this->assertNotFalse($widthProperty->getDocComment());
        $this->assertNotFalse($contentClassesProperty->getDocComment());
        
        // Check docblock content
        $this->assertStringContainsString('@var string', $alignProperty->getDocComment());
        $this->assertStringContainsString('@var int', $widthProperty->getDocComment());
        $this->assertStringContainsString('@var string', $contentClassesProperty->getDocComment());
    }
}
