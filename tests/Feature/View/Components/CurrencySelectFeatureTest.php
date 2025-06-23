<?php

namespace Tests\Feature\View\Components;

use App\Services\CurrencyService;
use App\View\Components\CurrencySelect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencySelectFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully_with_real_service(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'currency');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'currency');
        
        $view = $component->render();
        
        $this->assertEquals('components.currency-select', $view->getName());
    }

    #[Test]
    public function component_integrates_with_currency_service(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'currency');
        
        // Verify that currencies are loaded from the service
        $this->assertIsArray($component->currencies);
        $this->assertNotEmpty($component->currencies);
        
        // Verify that currencies contain expected structure (code => name)
        foreach ($component->currencies as $code => $name) {
            $this->assertIsString($code);
            $this->assertIsString($name);
            $this->assertNotEmpty($code);
            $this->assertNotEmpty($name);
        }
    }

    #[Test]
    public function component_handles_different_constructor_parameters(): void
    {
        $currencyService = app(CurrencyService::class);
        
        // Test with minimal parameters
        $component1 = new CurrencySelect($currencyService, 'currency1');
        $this->assertEquals('currency1', $component1->name);
        $this->assertEquals('currency1', $component1->id);
        $this->assertEquals('CZK', $component1->selected);
        
        // Test with custom parameters
        $component2 = new CurrencySelect(
            $currencyService,
            'currency2',
            'custom-id',
            'EUR',
            true,
            'Select Currency',
            'form-select',
            'label-class',
            'Choose your currency'
        );
        
        $this->assertEquals('currency2', $component2->name);
        $this->assertEquals('custom-id', $component2->id);
        $this->assertEquals('EUR', $component2->selected);
        $this->assertTrue($component2->required);
        $this->assertEquals('Select Currency', $component2->label);
        $this->assertEquals('form-select', $component2->class);
        $this->assertEquals('label-class', $component2->labelClass);
        $this->assertEquals('Choose your currency', $component2->hint);
    }

    #[Test]
    public function component_properties_are_accessible(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'test-currency');
        
        // Test that all public properties are accessible
        $this->assertIsArray($component->currencies);
        $this->assertIsString($component->name);
        $this->assertIsString($component->id);
        $this->assertTrue(is_string($component->selected) || is_null($component->selected));
        $this->assertIsBool($component->required);
        $this->assertIsString($component->label);
        $this->assertIsString($component->class);
        $this->assertIsString($component->labelClass);
        $this->assertIsString($component->hint);
    }

    #[Test]
    public function component_view_data_includes_component_properties(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'currency');
        
        $view = $component->render();
        $data = $view->getData();
        
        $this->assertIsArray($data);
        // Component properties should be available in view data
        $this->assertArrayHasKey('currencies', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('selected', $data);
        $this->assertArrayHasKey('required', $data);
        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('class', $data);
        $this->assertArrayHasKey('labelClass', $data);
        $this->assertArrayHasKey('hint', $data);
    }

    #[Test]
    public function component_works_with_different_selected_values(): void
    {
        $currencyService = app(CurrencyService::class);
        
        // Test with null selected (should default to CZK)
        $component1 = new CurrencySelect($currencyService, 'currency1', null, null);
        $this->assertEquals('CZK', $component1->selected);
        
        // Test with custom selected value
        $component2 = new CurrencySelect($currencyService, 'currency2', null, 'USD');
        $this->assertEquals('USD', $component2->selected);
        
        // Test with empty string selected (should use empty string)
        $component3 = new CurrencySelect($currencyService, 'currency3', null, '');
        $this->assertEquals('', $component3->selected);
    }

    #[Test]
    public function component_handles_required_attribute(): void
    {
        $currencyService = app(CurrencyService::class);
        
        // Test required = false (default)
        $component1 = new CurrencySelect($currencyService, 'currency1');
        $this->assertFalse($component1->required);
        
        // Test required = true
        $component2 = new CurrencySelect($currencyService, 'currency2', null, null, true);
        $this->assertTrue($component2->required);
        
        // Test required = false explicitly
        $component3 = new CurrencySelect($currencyService, 'currency3', null, null, false);
        $this->assertFalse($component3->required);
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $currencyService = app(CurrencyService::class);
        $component = new CurrencySelect($currencyService, 'currency');
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.currency-select', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/currency-select.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $currencyService = app(CurrencyService::class);
        
        $component1 = new CurrencySelect($currencyService, 'currency', 'id1', 'EUR');
        $component2 = new CurrencySelect($currencyService, 'currency', 'id2', 'EUR');
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->currencies, $component2->currencies);
        $this->assertEquals($component1->selected, $component2->selected);
    }
}
