<?php

namespace Tests\Feature\View\Components;

use App\View\Components\Dropdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DropdownFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully(): void
    {
        $component = new Dropdown();
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $component = new Dropdown();
        
        $view = $component->render();
        
        $this->assertEquals('components.dropdown', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $component = new Dropdown();
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.dropdown', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/dropdown.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_view_data_includes_component_properties(): void
    {
        $component = new Dropdown('left', 64, 'custom-class');
        
        // The Blade template uses @props, so we need to check the component properties directly
        $this->assertEquals('left', $component->align);
        $this->assertEquals(64, $component->width);
        $this->assertEquals('custom-class', $component->contentClasses);
    }

    #[Test]
    public function component_handles_different_alignment_options(): void
    {
        // Test left alignment
        $component1 = new Dropdown('left');
        $this->assertEquals('left', $component1->align);
        
        // Test right alignment (default)
        $component2 = new Dropdown();
        $this->assertEquals('right', $component2->align);
        
        // Test custom alignment
        $component3 = new Dropdown('center');
        $this->assertEquals('center', $component3->align);
    }

    #[Test]
    public function component_handles_different_width_values(): void
    {
        // Test default width
        $component1 = new Dropdown();
        $this->assertEquals(48, $component1->width);
        
        // Test custom width
        $component2 = new Dropdown('right', 100);
        $this->assertEquals(100, $component2->width);
        
        // Test zero width
        $component3 = new Dropdown('right', 0);
        $this->assertEquals(0, $component3->width);
    }

    #[Test]
    public function component_handles_different_content_classes(): void
    {
        // Test default empty class
        $component1 = new Dropdown();
        $this->assertEquals('', $component1->contentClasses);
        
        // Test single class
        $component2 = new Dropdown('right', 48, 'shadow-lg');
        $this->assertEquals('shadow-lg', $component2->contentClasses);
        
        // Test multiple classes
        $component3 = new Dropdown('right', 48, 'shadow-lg bg-white border');
        $this->assertEquals('shadow-lg bg-white border', $component3->contentClasses);
    }

    #[Test]
    public function component_properties_are_accessible_in_view(): void
    {
        $component = new Dropdown('left', 80, 'test-class');
        
        // Test that all properties are accessible
        $this->assertEquals('left', $component->align);
        $this->assertEquals(80, $component->width);
        $this->assertEquals('test-class', $component->contentClasses);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $component1 = new Dropdown('right', 48, 'class1');
        $component2 = new Dropdown('right', 48, 'class1');
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->align, $component2->align);
        $this->assertEquals($component1->width, $component2->width);
        $this->assertEquals($component1->contentClasses, $component2->contentClasses);
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $component = new Dropdown('left', 64, 'integration-test');
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        
        // Test component properties are set correctly
        $this->assertEquals('left', $component->align);
        $this->assertEquals(64, $component->width);
        $this->assertEquals('integration-test', $component->contentClasses);
    }

    #[Test]
    public function component_handles_extreme_values_gracefully(): void
    {
        // Test with very large width
        $component1 = new Dropdown('right', 9999);
        $view1 = $component1->render();
        $this->assertInstanceOf(View::class, $view1);
        
        // Test with very long class string
        $longClass = str_repeat('class-', 100);
        $component2 = new Dropdown('right', 48, $longClass);
        $view2 = $component2->render();
        $this->assertInstanceOf(View::class, $view2);
        
        // Test with unusual alignment value
        $component3 = new Dropdown('top-left-corner');
        $view3 = $component3->render();
        $this->assertInstanceOf(View::class, $view3);
    }
}
