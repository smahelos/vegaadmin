<?php

namespace Tests\Feature\View\Components;

use App\View\Components\ApplicationLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationLogoFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        $this->assertEquals('components.application-logo', $view->getName());
    }

    #[Test]
    public function component_renders_without_errors(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.application-logo', $view->getName());
    }

    #[Test]
    public function component_can_be_rendered_multiple_times(): void
    {
        $component = new ApplicationLogo();
        
        $view1 = $component->render();
        $view2 = $component->render();
        
        $this->assertInstanceOf(View::class, $view1);
        $this->assertInstanceOf(View::class, $view2);
        $this->assertEquals($view1->getName(), $view2->getName());
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        // Verify view has expected Laravel view properties
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue(method_exists($view, 'render'));
        $this->assertTrue(method_exists($view, 'with'));
        $this->assertTrue(method_exists($view, 'getData'));
    }

    #[Test]
    public function component_view_data_structure(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        $data = $view->getData();
        
        $this->assertIsArray($data);
        // ApplicationLogo should not pass any specific data
        $this->assertEmpty($data);
    }

    #[Test]
    public function component_handles_view_creation(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.application-logo', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $component = new ApplicationLogo();
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.application-logo', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/application-logo.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_is_consistent_across_renders(): void
    {
        $component1 = new ApplicationLogo();
        $component2 = new ApplicationLogo();
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($view1->getData(), $view2->getData());
    }

    #[Test]
    public function component_works_in_laravel_component_context(): void
    {
        // Test that the component can be used in Laravel's component system
        $component = new ApplicationLogo();
        
        $this->assertInstanceOf(\Illuminate\View\Component::class, $component);
        
        $view = $component->render();
        $this->assertInstanceOf(View::class, $view);
        
        // Verify the component follows Laravel component conventions
        $this->assertTrue(method_exists($component, 'render'));
        $this->assertTrue(method_exists($component, 'resolveView'));
    }
}
