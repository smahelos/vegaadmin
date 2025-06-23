<?php

namespace Tests\Feature\View\Components;

use App\View\Components\NavLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully(): void
    {
        $component = new NavLink('/test-url');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $component = new NavLink('/test-url');
        
        $view = $component->render();
        
        $this->assertEquals('components.nav-link', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $component = new NavLink('/test-url');
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.nav-link', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/nav-link.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_view_data_includes_component_properties(): void
    {
        $testUrl = '/dashboard';
        $component = new NavLink($testUrl, true);
        
        $view = $component->render();
        $data = $view->getData();
        
        $this->assertIsArray($data);
        // Component properties should be available in view data
        $this->assertArrayHasKey('href', $data);
        $this->assertArrayHasKey('active', $data);
        
        // Verify the actual values
        $this->assertEquals($testUrl, $data['href']);
        $this->assertTrue($data['active']);
    }

    #[Test]
    public function component_handles_different_active_states(): void
    {
        // Test active = false (default)
        $component1 = new NavLink('/dashboard');
        $view1 = $component1->render();
        $data1 = $view1->getData();
        $this->assertFalse($data1['active']);
        $this->assertFalse($component1->active);
        
        // Test active = true
        $component2 = new NavLink('/dashboard', true);
        $view2 = $component2->render();
        $data2 = $view2->getData();
        $this->assertTrue($data2['active']);
        $this->assertTrue($component2->active);
        
        // Test active = false explicitly
        $component3 = new NavLink('/dashboard', false);
        $view3 = $component3->render();
        $data3 = $view3->getData();
        $this->assertFalse($data3['active']);
        $this->assertFalse($component3->active);
    }

    #[Test]
    public function component_handles_different_url_types(): void
    {
        $testUrls = [
            '/dashboard',
            '/users',
            '/settings',
            'https://external.com',
            '#anchor',
            '/admin/reports?date=today',
            '',
            '/'
        ];
        
        foreach ($testUrls as $url) {
            // Test with inactive state
            $component1 = new NavLink($url, false);
            $view1 = $component1->render();
            $data1 = $view1->getData();
            
            $this->assertInstanceOf(View::class, $view1);
            $this->assertEquals($url, $data1['href']);
            $this->assertEquals($url, $component1->href);
            $this->assertFalse($data1['active']);
            
            // Test with active state
            $component2 = new NavLink($url, true);
            $view2 = $component2->render();
            $data2 = $view2->getData();
            
            $this->assertInstanceOf(View::class, $view2);
            $this->assertEquals($url, $data2['href']);
            $this->assertEquals($url, $component2->href);
            $this->assertTrue($data2['active']);
        }
    }

    #[Test]
    public function component_properties_are_accessible_in_view(): void
    {
        $testUrl = '/profile';
        $component = new NavLink($testUrl, true);
        
        $view = $component->render();
        
        // Test that all properties are accessible
        $this->assertEquals($testUrl, $component->href);
        $this->assertTrue($component->active);
        
        // These properties should be available in the view context
        $data = $view->getData();
        $this->assertEquals($component->href, $data['href']);
        $this->assertEquals($component->active, $data['active']);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $url = '/consistent-test';
        $active = true;
        $component1 = new NavLink($url, $active);
        $component2 = new NavLink($url, $active);
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->href, $component2->href);
        $this->assertEquals($component1->active, $component2->active);
        
        $data1 = $view1->getData();
        $data2 = $view2->getData();
        $this->assertEquals($data1['href'], $data2['href']);
        $this->assertEquals($data1['active'], $data2['active']);
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $component = new NavLink('/integration-test', true);
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        $this->assertIsArray($view->getData());
        
        // Test that the view has required data for rendering
        $data = $view->getData();
        $this->assertArrayHasKey('href', $data);
        $this->assertArrayHasKey('active', $data);
        $this->assertEquals('/integration-test', $data['href']);
        $this->assertTrue($data['active']);
    }

    #[Test]
    public function component_maintains_state_consistency_through_render_cycle(): void
    {
        $originalUrl = '/state/test';
        $originalActive = true;
        $component = new NavLink($originalUrl, $originalActive);
        
        // Get initial state
        $initialHref = $component->href;
        $initialActive = $component->active;
        
        // Render the component
        $view = $component->render();
        $data = $view->getData();
        
        // Verify state remains unchanged after rendering
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($originalActive, $component->active);
        $this->assertEquals($initialHref, $component->href);
        $this->assertEquals($initialActive, $component->active);
        $this->assertEquals($originalUrl, $data['href']);
        $this->assertEquals($originalActive, $data['active']);
        
        // Render again to ensure consistency
        $view2 = $component->render();
        $data2 = $view2->getData();
        
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($originalActive, $component->active);
        $this->assertEquals($originalUrl, $data2['href']);
        $this->assertEquals($originalActive, $data2['active']);
    }

    #[Test]
    public function component_handles_navigation_workflow_scenarios(): void
    {
        // Scenario 1: Main navigation links
        $mainNavLinks = [
            ['/dashboard', true],   // Current page
            ['/users', false],      // Other pages
            ['/reports', false],
            ['/settings', false]
        ];
        
        foreach ($mainNavLinks as [$url, $isActive]) {
            $component = new NavLink($url, $isActive);
            $view = $component->render();
            $data = $view->getData();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $data['href']);
            $this->assertEquals($isActive, $data['active']);
        }
        
        // Scenario 2: Breadcrumb-style navigation
        $breadcrumbLinks = [
            ['/', false],              // Home
            ['/dashboard', false],     // Dashboard
            ['/users', false],         // Users
            ['/users/123', true]       // Current user
        ];
        
        foreach ($breadcrumbLinks as [$url, $isActive]) {
            $component = new NavLink($url, $isActive);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            $this->assertEquals($isActive, $component->active);
        }
    }

    #[Test]
    public function component_works_with_route_based_navigation(): void
    {
        // Test URLs that would typically be generated by Laravel route() helper
        $routeUrls = [
            'http://localhost/admin/dashboard',
            'https://app.example.com/users',
            '/admin/users/create',
            '/api/documentation',
            'http://127.0.0.1:8000/test-route'
        ];
        
        foreach ($routeUrls as $url) {
            // Test both active and inactive states
            $activeComponent = new NavLink($url, true);
            $inactiveComponent = new NavLink($url, false);
            
            $activeView = $activeComponent->render();
            $inactiveView = $inactiveComponent->render();
            
            $this->assertInstanceOf(View::class, $activeView);
            $this->assertInstanceOf(View::class, $inactiveView);
            
            $activeData = $activeView->getData();
            $inactiveData = $inactiveView->getData();
            
            $this->assertEquals($url, $activeData['href']);
            $this->assertEquals($url, $inactiveData['href']);
            $this->assertTrue($activeData['active']);
            $this->assertFalse($inactiveData['active']);
        }
    }

    #[Test]
    public function component_handles_special_navigation_patterns(): void
    {
        // Test logout link (typically javascript or form submission)
        $logoutComponent = new NavLink('javascript:document.getElementById("logout-form").submit();', false);
        $logoutView = $logoutComponent->render();
        $this->assertInstanceOf(View::class, $logoutView);
        
        // Test external link
        $externalComponent = new NavLink('https://docs.laravel.com', false);
        $externalView = $externalComponent->render();
        $this->assertInstanceOf(View::class, $externalView);
        
        // Test anchor link
        $anchorComponent = new NavLink('#section-1', false);
        $anchorView = $anchorComponent->render();
        $this->assertInstanceOf(View::class, $anchorView);
        
        // Test query parameters
        $queryComponent = new NavLink('/search?q=test&category=all', true);
        $queryView = $queryComponent->render();
        $this->assertInstanceOf(View::class, $queryView);
        
        $queryData = $queryView->getData();
        $this->assertEquals('/search?q=test&category=all', $queryData['href']);
        $this->assertTrue($queryData['active']);
    }
}
