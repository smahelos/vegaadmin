<?php

namespace Tests\Unit\View\Components;

use App\View\Components\Select;
use Illuminate\View\Component;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    private Select $component;
    private array $testOptions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testOptions = [
            ['value' => '1', 'text' => 'Option 1'],
            ['value' => '2', 'text' => 'Option 2'],
            ['value' => '3', 'text' => 'Option 3']
        ];
        
        $this->component = new Select($this->testOptions, 'test_select');
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(Select::class, $this->component);
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
        
        $reflection = new \ReflectionClass(Select::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function constructor_sets_default_values_correctly(): void
    {
        $options = [['value' => 'test', 'text' => 'Test']];
        $name = 'test_name';
        $component = new Select($options, $name);
        
        $this->assertEquals($options, $component->options);
        $this->assertEquals($name, $component->name);
        $this->assertEquals($name, $component->id); // Should default to name
        $this->assertNull($component->selected);
        $this->assertFalse($component->required);
        $this->assertEquals('', $component->label);
        $this->assertEquals('', $component->class);
        $this->assertEquals('', $component->labelClass);
        $this->assertEquals('value', $component->valueField);
        $this->assertEquals('text', $component->textField);
        $this->assertEquals('', $component->hint);
        $this->assertEquals('', $component->allowsNull);
        $this->assertEquals('', $component->placeholder);
    }

    #[Test]
    public function constructor_accepts_custom_values(): void
    {
        $options = [['id' => '1', 'name' => 'Custom Option']];
        $component = new Select(
            $options,
            'custom_name',
            'custom_id',
            'selected_value',
            true,
            'Custom Label',
            'custom-class',
            'custom-label-class',
            'id',
            'name',
            'Custom hint',
            'yes',
            'Select option...'
        );
        
        $this->assertEquals($options, $component->options);
        $this->assertEquals('custom_name', $component->name);
        $this->assertEquals('custom_id', $component->id);
        $this->assertEquals('selected_value', $component->selected);
        $this->assertTrue($component->required);
        $this->assertEquals('Custom Label', $component->label);
        $this->assertEquals('custom-class', $component->class);
        $this->assertEquals('custom-label-class', $component->labelClass);
        $this->assertEquals('id', $component->valueField);
        $this->assertEquals('name', $component->textField);
        $this->assertEquals('Custom hint', $component->hint);
        $this->assertEquals('yes', $component->allowsNull);
        $this->assertEquals('Select option...', $component->placeholder);
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $expectedProperties = [
            'options', 'name', 'id', 'selected', 'required', 'label', 'class',
            'labelClass', 'valueField', 'textField', 'hint', 'allowsNull', 'placeholder'
        ];
        
        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $propertyNames);
        }
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the select component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('array $options', $docComment);
        $this->assertStringContainsString('string $name', $docComment);
    }

    #[Test]
    public function constructor_parameters_have_correct_types(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(13, $parameters);
        
        // Check first two required parameters
        $this->assertEquals('options', $parameters[0]->getName());
        $this->assertEquals('name', $parameters[1]->getName());
        $this->assertFalse($parameters[0]->isOptional());
        $this->assertFalse($parameters[1]->isOptional());
        
        // Check some optional parameters
        $this->assertTrue($parameters[2]->isOptional()); // id
        $this->assertTrue($parameters[3]->isOptional()); // selected
        $this->assertTrue($parameters[4]->isOptional()); // required
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }

    #[Test]
    public function component_handles_empty_options_array(): void
    {
        $component = new Select([], 'empty_select');
        
        $this->assertEquals([], $component->options);
        $this->assertEquals('empty_select', $component->name);
    }

    #[Test]
    public function component_handles_different_option_structures(): void
    {
        // Test with key-value pairs
        $keyValueOptions = ['key1' => 'Value 1', 'key2' => 'Value 2'];
        $component1 = new Select($keyValueOptions, 'test1');
        $this->assertEquals($keyValueOptions, $component1->options);
        
        // Test with object-like arrays
        $objectOptions = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second']
        ];
        $component2 = new Select($objectOptions, 'test2', null, null, false, '', '', '', 'id', 'name');
        $this->assertEquals($objectOptions, $component2->options);
        $this->assertEquals('id', $component2->valueField);
        $this->assertEquals('name', $component2->textField);
    }

    #[Test]
    public function render_method_returns_correct_view(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        
        // Check that the method body contains the expected view call
        $fileContent = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString("view('components.select')", $fileContent);
    }

    #[Test]
    public function id_defaults_to_name_when_not_provided(): void
    {
        $component = new Select(['test' => 'Test'], 'field_name');
        
        $this->assertEquals('field_name', $component->id);
        $this->assertEquals('field_name', $component->name);
    }

    #[Test]
    public function component_properties_have_correct_types(): void
    {
        $reflection = new \ReflectionClass(Select::class);
        
        // Check property types in file content (since they're documented with types)
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('public array $options;', $fileContent);
        $this->assertStringContainsString('public string $name;', $fileContent);
        $this->assertStringContainsString('public ?string $id;', $fileContent);
        $this->assertStringContainsString('public ?string $selected;', $fileContent);
        $this->assertStringContainsString('public bool $required;', $fileContent);
    }
}
