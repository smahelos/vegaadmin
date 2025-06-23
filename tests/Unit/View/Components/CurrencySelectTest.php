<?php

namespace Tests\Unit\View\Components;

use App\Services\CurrencyService;
use App\View\Components\CurrencySelect;
use Illuminate\View\Component;
use Illuminate\View\View;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CurrencySelectTest extends TestCase
{
    private CurrencySelect $component;
    /** @var MockInterface&CurrencyService */
    private MockInterface $mockCurrencyService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock CurrencyService using Mockery 1.6 best practices
        // @phpstan-ignore-next-line
        $mockService = Mockery::mock(CurrencyService::class);
        $currencies = [
            'CZK' => 'Czech Koruna',
            'EUR' => 'Euro', 
            'USD' => 'US Dollar'
        ];
        // @phpstan-ignore-next-line  
        $mockService
            ->shouldReceive('getCommonCurrencies')
            ->andReturnUsing(function () use ($currencies) {
                return $currencies;
            });
            
        $this->mockCurrencyService = $mockService;
            
        $this->component = new CurrencySelect(
            $this->mockCurrencyService,
            'currency'
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(CurrencySelect::class, $this->component);
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
        
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function render_method_has_no_parameters(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $method = $reflection->getMethod('render');
        $parameters = $method->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function constructor_sets_default_values_correctly(): void
    {
        $component = new CurrencySelect($this->mockCurrencyService, 'test_name');
        
        $this->assertEquals('test_name', $component->name);
        $this->assertEquals('test_name', $component->id); // Should default to name
        $this->assertEquals('CZK', $component->selected); // Default selected value
        $this->assertFalse($component->required); // Default required
        $this->assertEquals('Currency', $component->label); // Default label
        $this->assertEquals('', $component->class); // Default class
        $this->assertEquals('', $component->labelClass); // Default labelClass
        $this->assertEquals('', $component->hint); // Default hint
    }

    #[Test]
    public function constructor_accepts_custom_values(): void
    {
        $component = new CurrencySelect(
            $this->mockCurrencyService,
            'custom_name',
            'custom_id',
            'EUR',
            true,
            'Custom Label',
            'custom-class',
            'custom-label-class',
            'Custom hint'
        );
        
        $this->assertEquals('custom_name', $component->name);
        $this->assertEquals('custom_id', $component->id);
        $this->assertEquals('EUR', $component->selected);
        $this->assertTrue($component->required);
        $this->assertEquals('Custom Label', $component->label);
        $this->assertEquals('custom-class', $component->class);
        $this->assertEquals('custom-label-class', $component->labelClass);
        $this->assertEquals('Custom hint', $component->hint);
    }

    #[Test]
    public function component_loads_currencies_from_service(): void
    {
        $expectedCurrencies = [
            'CZK' => 'Czech Koruna',
            'EUR' => 'Euro',
            'USD' => 'US Dollar'
        ];
        
        $this->assertEquals($expectedCurrencies, $this->component->currencies);
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $expectedProperties = [
            'currencies', 'name', 'id', 'selected', 'required', 
            'label', 'class', 'labelClass', 'hint'
        ];
        
        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $propertyNames);
        }
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Render the currency select component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param', $docComment);
        $this->assertStringContainsString('CurrencyService', $docComment);
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(CurrencySelect::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use App\Services\CurrencyService;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
    }
}
