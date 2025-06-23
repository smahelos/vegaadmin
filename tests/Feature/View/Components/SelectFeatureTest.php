<?php

namespace Tests\Feature\View\Components;

use App\View\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SelectFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function getBasicOptions(): array
    {
        return [
            ['value' => '1', 'text' => 'Option 1'],
            ['value' => '2', 'text' => 'Option 2'],
            ['value' => '3', 'text' => 'Option 3']
        ];
    }

    #[Test]
    public function component_renders_successfully(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'test_select');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'test_select');
        
        $view = $component->render();
        
        $this->assertEquals('components.select', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'test_select');
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.select', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/select.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_handles_different_option_structures(): void
    {
        // Test with object-like arrays (default structure)
        $objectOptions = $this->getBasicOptions();
        $component1 = new Select($objectOptions, 'test1');
        $view1 = $component1->render();
        
        $this->assertInstanceOf(View::class, $view1);
        $this->assertEquals($objectOptions, $component1->options);
        
        // Test with key-value pairs
        $keyValueOptions = ['key1' => 'Value 1', 'key2' => 'Value 2'];
        $component2 = new Select($keyValueOptions, 'test2');
        $view2 = $component2->render();
        
        $this->assertInstanceOf(View::class, $view2);
        $this->assertEquals($keyValueOptions, $component2->options);
        
        // Test with custom field mappings
        $customOptions = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second']
        ];
        $component3 = new Select($customOptions, 'test3', null, null, false, '', '', '', 'id', 'name');
        $view3 = $component3->render();
        
        $this->assertInstanceOf(View::class, $view3);
        $this->assertEquals($customOptions, $component3->options);
        $this->assertEquals('id', $component3->valueField);
        $this->assertEquals('name', $component3->textField);
    }

    #[Test]
    public function component_handles_different_configuration_options(): void
    {
        $options = $this->getBasicOptions();
        
        // Test with all custom parameters
        $component = new Select(
            $options,
            'custom_select',
            'custom_id',
            '2',
            true,
            'Custom Label',
            'form-control custom-class',
            'label-bold',
            'id',
            'label',
            'Please select an option',
            'yes',
            'Choose...'
        );
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals($options, $component->options);
        $this->assertEquals('custom_select', $component->name);
        $this->assertEquals('custom_id', $component->id);
        $this->assertEquals('2', $component->selected);
        $this->assertTrue($component->required);
        $this->assertEquals('Custom Label', $component->label);
        $this->assertEquals('form-control custom-class', $component->class);
        $this->assertEquals('label-bold', $component->labelClass);
        $this->assertEquals('id', $component->valueField);
        $this->assertEquals('label', $component->textField);
        $this->assertEquals('Please select an option', $component->hint);
        $this->assertEquals('yes', $component->allowsNull);
        $this->assertEquals('Choose...', $component->placeholder);
    }

    #[Test]
    public function component_handles_default_values(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'simple_select');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals($options, $component->options);
        $this->assertEquals('simple_select', $component->name);
        $this->assertEquals('simple_select', $component->id); // Should default to name
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
    public function component_handles_empty_and_special_option_cases(): void
    {
        // Test with empty options
        $component1 = new Select([], 'empty_select');
        $view1 = $component1->render();
        
        $this->assertInstanceOf(View::class, $view1);
        $this->assertEquals([], $component1->options);
        
        // Test with single option
        $singleOption = [['value' => 'only', 'text' => 'Only Option']];
        $component2 = new Select($singleOption, 'single_select');
        $view2 = $component2->render();
        
        $this->assertInstanceOf(View::class, $view2);
        $this->assertCount(1, $component2->options);
        
        // Test with many options
        $manyOptions = [];
        for ($i = 1; $i <= 100; $i++) {
            $manyOptions[] = ['value' => $i, 'text' => "Option $i"];
        }
        $component3 = new Select($manyOptions, 'many_select');
        $view3 = $component3->render();
        
        $this->assertInstanceOf(View::class, $view3);
        $this->assertCount(100, $component3->options);
    }

    #[Test]
    public function component_properties_are_accessible(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'accessible_select', 'access_id', '2', true, 'Test Label');
        
        $view = $component->render();
        
        // Test that all properties are accessible
        $this->assertIsArray($component->options);
        $this->assertIsString($component->name);
        $this->assertIsString($component->id);
        $this->assertTrue(is_string($component->selected) || is_null($component->selected));
        $this->assertIsBool($component->required);
        $this->assertIsString($component->label);
        $this->assertIsString($component->class);
        $this->assertIsString($component->labelClass);
        $this->assertIsString($component->valueField);
        $this->assertIsString($component->textField);
        $this->assertIsString($component->hint);
        $this->assertIsString($component->allowsNull);
        $this->assertIsString($component->placeholder);
        
        // View should render successfully
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $options = $this->getBasicOptions();
        $component1 = new Select($options, 'consistent_select', null, '1');
        $component2 = new Select($options, 'consistent_select', null, '1');
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->options, $component2->options);
        $this->assertEquals($component1->name, $component2->name);
        $this->assertEquals($component1->selected, $component2->selected);
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $options = $this->getBasicOptions();
        $component = new Select($options, 'integration_select', 'int_id', '3', true, 'Integration Test');
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        
        // Test component properties are accessible
        $this->assertEquals($options, $component->options);
        $this->assertEquals('integration_select', $component->name);
        $this->assertEquals('int_id', $component->id);
        $this->assertEquals('3', $component->selected);
        $this->assertTrue($component->required);
        $this->assertEquals('Integration Test', $component->label);
    }

    #[Test]
    public function component_maintains_state_consistency_through_render_cycle(): void
    {
        $originalOptions = $this->getBasicOptions();
        $component = new Select($originalOptions, 'state_select', 'state_id', '2', true, 'State Test');
        
        // Get initial state
        $initialOptions = $component->options;
        $initialName = $component->name;
        $initialSelected = $component->selected;
        $initialRequired = $component->required;
        
        // Render the component
        $view = $component->render();
        
        // Verify state remains unchanged after rendering
        $this->assertEquals($originalOptions, $component->options);
        $this->assertEquals($initialOptions, $component->options);
        $this->assertEquals($initialName, $component->name);
        $this->assertEquals($initialSelected, $component->selected);
        $this->assertEquals($initialRequired, $component->required);
        
        // Render again to ensure consistency
        $view2 = $component->render();
        
        $this->assertEquals($originalOptions, $component->options);
        $this->assertEquals($initialName, $component->name);
        $this->assertEquals($initialSelected, $component->selected);
        $this->assertEquals($initialRequired, $component->required);
    }

    #[Test]
    public function component_handles_form_integration_scenarios(): void
    {
        $options = $this->getBasicOptions();
        
        // Scenario 1: Form with validation (required field)
        $requiredComponent = new Select($options, 'required_field', null, null, true, 'Required Field', 'is-invalid');
        $requiredView = $requiredComponent->render();
        
        $this->assertInstanceOf(View::class, $requiredView);
        $this->assertTrue($requiredComponent->required);
        $this->assertEquals('is-invalid', $requiredComponent->class);
        
        // Scenario 2: Form with preselected value
        $preselectedComponent = new Select($options, 'preselected_field', null, '2', false, 'Preselected Field');
        $preselectedView = $preselectedComponent->render();
        
        $this->assertInstanceOf(View::class, $preselectedView);
        $this->assertEquals('2', $preselectedComponent->selected);
        $this->assertFalse($preselectedComponent->required);
        
        // Scenario 3: Form with help text and placeholder
        $helpComponent = new Select(
            $options, 
            'help_field', 
            null, 
            null, 
            false, 
            'Field with Help', 
            '', 
            '', 
            'value', 
            'text', 
            'This field helps you select an option',
            '',
            'Please choose...'
        );
        $helpView = $helpComponent->render();
        
        $this->assertInstanceOf(View::class, $helpView);
        $this->assertEquals('This field helps you select an option', $helpComponent->hint);
        $this->assertEquals('Please choose...', $helpComponent->placeholder);
    }

    #[Test]
    public function component_handles_special_character_scenarios(): void
    {
        // Test with special characters in options
        $specialOptions = [
            ['value' => 'test&amp;', 'text' => 'Test & Ampersand'],
            ['value' => 'test<script>', 'text' => 'Test <script> Tag'],
            ['value' => 'test"quote', 'text' => 'Test "Quote" Marks'],
            ['value' => "test'apostrophe", 'text' => "Test 'Apostrophe' Marks"],
            ['value' => 'test€symbol', 'text' => 'Test € Symbol'],
            ['value' => 'test中文', 'text' => 'Test 中文 Unicode']
        ];
        
        $component = new Select($specialOptions, 'special_select');
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals($specialOptions, $component->options);
        $this->assertCount(6, $component->options);
        
        // Test special characters in other fields
        $specialComponent = new Select(
            $this->getBasicOptions(),
            'special_name&test',
            'special<id>',
            'special"value',
            false,
            'Label with "quotes" & symbols',
            'class-with-special&chars',
            'label-class-"special"',
            'value',
            'text',
            'Hint with special chars: <>&"\'',
            '',
            'Placeholder with € & symbols'
        );
        
        $specialView = $specialComponent->render();
        $this->assertInstanceOf(View::class, $specialView);
    }

    #[Test]
    public function component_works_with_real_world_data_scenarios(): void
    {
        // Scenario 1: Country selection
        $countries = [
            ['value' => 'CZ', 'text' => 'Czech Republic'],
            ['value' => 'SK', 'text' => 'Slovakia'],
            ['value' => 'DE', 'text' => 'Germany'],
            ['value' => 'AT', 'text' => 'Austria']
        ];
        
        $countryComponent = new Select($countries, 'country', null, 'CZ', true, 'Country');
        $countryView = $countryComponent->render();
        
        $this->assertInstanceOf(View::class, $countryView);
        $this->assertEquals('CZ', $countryComponent->selected);
        
        // Scenario 2: User role selection
        $roles = [
            ['value' => '1', 'text' => 'Administrator'],
            ['value' => '2', 'text' => 'Editor'],
            ['value' => '3', 'text' => 'Viewer']
        ];
        
        $roleComponent = new Select($roles, 'user_role', null, null, true, 'User Role', '', '', 'value', 'text', 'Select the user role');
        $roleView = $roleComponent->render();
        
        $this->assertInstanceOf(View::class, $roleView);
        $this->assertEquals('Select the user role', $roleComponent->hint);
        
        // Scenario 3: Database records with custom field mapping
        $dbRecords = [
            ['id' => 101, 'name' => 'Product A', 'active' => true],
            ['id' => 102, 'name' => 'Product B', 'active' => false],
            ['id' => 103, 'name' => 'Product C', 'active' => true]
        ];
        
        $dbComponent = new Select($dbRecords, 'product_id', null, '102', false, 'Product', '', '', 'id', 'name');
        $dbView = $dbComponent->render();
        
        $this->assertInstanceOf(View::class, $dbView);
        $this->assertEquals('id', $dbComponent->valueField);
        $this->assertEquals('name', $dbComponent->textField);
        $this->assertEquals('102', $dbComponent->selected);
    }
}
