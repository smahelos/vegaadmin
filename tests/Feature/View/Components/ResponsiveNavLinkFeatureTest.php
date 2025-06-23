<?php

namespace Tests\Feature\View\Components;

use App\View\Components\ResponsiveNavLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResponsiveNavLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully(): void
    {
        $component = new ResponsiveNavLink('/test-url');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $component = new ResponsiveNavLink('/test-url');
        
        $view = $component->render();
        
        $this->assertEquals('components.responsive-nav-link', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $component = new ResponsiveNavLink('/test-url');
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.responsive-nav-link', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/responsive-nav-link.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_handles_different_active_states(): void
    {
        // Test inactive state (default)
        $component1 = new ResponsiveNavLink('/dashboard');
        $this->assertFalse($component1->active);
        $this->assertEquals('/dashboard', $component1->href);
        
        // Test active state
        $component2 = new ResponsiveNavLink('/dashboard', true);
        $this->assertTrue($component2->active);
        $this->assertEquals('/dashboard', $component2->href);
        
        // Test explicitly inactive
        $component3 = new ResponsiveNavLink('/dashboard', false);
        $this->assertFalse($component3->active);
        $this->assertEquals('/dashboard', $component3->href);
    }

    #[Test]
    public function component_handles_different_url_types(): void
    {
        $testUrls = [
            '/mobile-dashboard',
            '/mobile/users',
            '/settings',
            'https://external-mobile.com',
            '#mobile-anchor',
            '/mobile/admin/reports?date=today',
            '',
            '/',
            'tel:+420123456789',
            'mailto:mobile@example.com'
        ];
        
        foreach ($testUrls as $url) {
            // Test with inactive state
            $component1 = new ResponsiveNavLink($url, false);
            $view1 = $component1->render();
            
            $this->assertInstanceOf(View::class, $view1);
            $this->assertEquals($url, $component1->href);
            $this->assertFalse($component1->active);
            
            // Test with active state
            $component2 = new ResponsiveNavLink($url, true);
            $view2 = $component2->render();
            
            $this->assertInstanceOf(View::class, $view2);
            $this->assertEquals($url, $component2->href);
            $this->assertTrue($component2->active);
        }
    }

    #[Test]
    public function component_properties_are_accessible(): void
    {
        $testUrl = '/responsive-profile';
        $component = new ResponsiveNavLink($testUrl, true);
        
        $view = $component->render();
        
        // Test that all properties are accessible
        $this->assertEquals($testUrl, $component->href);
        $this->assertTrue($component->active);
        
        // View should render successfully
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $url = '/mobile-consistent-test';
        $active = true;
        $component1 = new ResponsiveNavLink($url, $active);
        $component2 = new ResponsiveNavLink($url, $active);
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->href, $component2->href);
        $this->assertEquals($component1->active, $component2->active);
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $component = new ResponsiveNavLink('/responsive-integration-test', true);
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        
        // Test component properties are accessible
        $this->assertEquals('/responsive-integration-test', $component->href);
        $this->assertTrue($component->active);
    }

    #[Test]
    public function component_maintains_state_consistency_through_render_cycle(): void
    {
        $originalUrl = '/responsive-state/test';
        $originalActive = true;
        $component = new ResponsiveNavLink($originalUrl, $originalActive);
        
        // Get initial state
        $initialHref = $component->href;
        $initialActive = $component->active;
        
        // Render the component
        $view = $component->render();
        
        // Verify state remains unchanged after rendering
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($originalActive, $component->active);
        $this->assertEquals($initialHref, $component->href);
        $this->assertEquals($initialActive, $component->active);
        
        // Render again to ensure consistency
        $view2 = $component->render();
        
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($originalActive, $component->active);
    }

    #[Test]
    public function component_handles_mobile_navigation_workflow_scenarios(): void
    {
        // Scenario 1: Mobile main navigation (hamburger menu)
        $mobileNavLinks = [
            ['/mobile/dashboard', true],   // Current page
            ['/mobile/users', false],      // Other pages
            ['/mobile/reports', false],
            ['/mobile/settings', false],
            ['/mobile/logout', false]
        ];
        
        foreach ($mobileNavLinks as [$url, $isActive]) {
            $component = new ResponsiveNavLink($url, $isActive);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            $this->assertEquals($isActive, $component->active);
        }
        
        // Scenario 2: Mobile responsive menu for different screen sizes
        $responsiveLinks = [
            ['/', false],                    // Home
            ['/mobile-dashboard', false],    // Mobile dashboard
            ['/tablet-view', false],         // Tablet view
            ['/mobile/profile', true]        // Current mobile profile
        ];
        
        foreach ($responsiveLinks as [$url, $isActive]) {
            $component = new ResponsiveNavLink($url, $isActive);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            $this->assertEquals($isActive, $component->active);
        }
    }

    #[Test]
    public function component_works_with_mobile_specific_urls(): void
    {
        // Test URLs specific to mobile/responsive navigation
        $mobileUrls = [
            '/mobile/dashboard',
            '/responsive/menu',
            '/hamburger-nav',
            '/touch-friendly/interface',
            '/mobile/app/deep-link',
            'app://open-mobile-view',
            'intent://mobile-action',
            '/mobile/swipe-navigation'
        ];
        
        foreach ($mobileUrls as $url) {
            // Test both active and inactive states
            $activeComponent = new ResponsiveNavLink($url, true);
            $inactiveComponent = new ResponsiveNavLink($url, false);
            
            $activeView = $activeComponent->render();
            $inactiveView = $inactiveComponent->render();
            
            $this->assertInstanceOf(View::class, $activeView);
            $this->assertInstanceOf(View::class, $inactiveView);
            
            $this->assertEquals($url, $activeComponent->href);
            $this->assertEquals($url, $inactiveComponent->href);
            $this->assertTrue($activeComponent->active);
            $this->assertFalse($inactiveComponent->active);
        }
    }

    #[Test]
    public function component_handles_responsive_navigation_patterns(): void
    {
        // Test off-canvas menu link
        $offCanvasComponent = new ResponsiveNavLink('#off-canvas-toggle', false);
        $offCanvasView = $offCanvasComponent->render();
        $this->assertInstanceOf(View::class, $offCanvasView);
        
        // Test drawer navigation link
        $drawerComponent = new ResponsiveNavLink('javascript:toggleDrawer();', false);
        $drawerView = $drawerComponent->render();
        $this->assertInstanceOf(View::class, $drawerView);
        
        // Test touch-optimized external link
        $touchComponent = new ResponsiveNavLink('https://mobile.docs.laravel.com', false);
        $touchView = $touchComponent->render();
        $this->assertInstanceOf(View::class, $touchView);
        
        // Test swipe navigation with query parameters
        $swipeComponent = new ResponsiveNavLink('/mobile/swipe?direction=left&page=2', true);
        $swipeView = $swipeComponent->render();
        $this->assertInstanceOf(View::class, $swipeView);
        
        $this->assertEquals('/mobile/swipe?direction=left&page=2', $swipeComponent->href);
        $this->assertTrue($swipeComponent->active);
    }

    #[Test]
    public function component_supports_accessibility_navigation_patterns(): void
    {
        // Test skip navigation link (common in responsive design)
        $skipComponent = new ResponsiveNavLink('#main-content', false);
        $skipView = $skipComponent->render();
        $this->assertInstanceOf(View::class, $skipView);
        
        // Test focus management link
        $focusComponent = new ResponsiveNavLink('javascript:manageFocus();', false);
        $focusView = $focusComponent->render();
        $this->assertInstanceOf(View::class, $focusView);
        
        // Test screen reader navigation
        $srComponent = new ResponsiveNavLink('/mobile/screen-reader-navigation', true);
        $srView = $srComponent->render();
        $this->assertInstanceOf(View::class, $srView);
        
        $this->assertEquals('/mobile/screen-reader-navigation', $srComponent->href);
        $this->assertTrue($srComponent->active);
    }

    #[Test]
    public function component_handles_responsive_breakpoint_scenarios(): void
    {
        // Test navigation that might behave differently across breakpoints
        $breakpointUrls = [
            '/mobile-only',      // Only shown on mobile
            '/tablet-up',        // Shown on tablet and up
            '/desktop-hidden',   // Hidden on desktop
            '/all-responsive'    // Shown on all breakpoints
        ];
        
        foreach ($breakpointUrls as $url) {
            $component = new ResponsiveNavLink($url, false);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            $this->assertFalse($component->active);
            
            // Test with active state
            $activeComponent = new ResponsiveNavLink($url, true);
            $activeView = $activeComponent->render();
            
            $this->assertInstanceOf(View::class, $activeView);
            $this->assertTrue($activeComponent->active);
        }
    }
}
