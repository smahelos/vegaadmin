<?php

namespace Tests\Feature\View\Components;

use App\View\Components\DropdownLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DropdownLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_successfully(): void
    {
        $component = new DropdownLink('/test-url');
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $component = new DropdownLink('/test-url');
        
        $view = $component->render();
        
        $this->assertEquals('components.dropdown-link', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $component = new DropdownLink('/test-url');
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.dropdown-link', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/dropdown-link.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_view_data_includes_component_properties(): void
    {
        $testUrl = '/dashboard/users';
        $component = new DropdownLink($testUrl);
        
        $view = $component->render();
        $data = $view->getData();
        
        $this->assertIsArray($data);
        // Component properties should be available in view data
        $this->assertArrayHasKey('href', $data);
        
        // Verify the actual value
        $this->assertEquals($testUrl, $data['href']);
    }

    #[Test]
    public function component_handles_different_url_types(): void
    {
        $testUrls = [
            '/dashboard',
            'https://example.com',
            'mailto:test@example.com',
            'tel:+420123456789',
            '#',
            'javascript:void(0)',
            '',
            '/admin/users?filter=active',
            'https://laravel.com/docs/12.x'
        ];
        
        foreach ($testUrls as $url) {
            $component = new DropdownLink($url);
            $view = $component->render();
            $data = $view->getData();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $data['href']);
            $this->assertEquals($url, $component->href);
        }
    }

    #[Test]
    public function component_properties_are_accessible_in_view(): void
    {
        $testUrl = '/settings';
        $component = new DropdownLink($testUrl);
        
        $view = $component->render();
        
        // Test that href property is accessible
        $this->assertEquals($testUrl, $component->href);
        
        // This property should be available in the view context
        $data = $view->getData();
        $this->assertEquals($component->href, $data['href']);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $url = '/consistent-test';
        $component1 = new DropdownLink($url);
        $component2 = new DropdownLink($url);
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->href, $component2->href);
        
        $data1 = $view1->getData();
        $data2 = $view2->getData();
        $this->assertEquals($data1['href'], $data2['href']);
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $component = new DropdownLink('/integration-test');
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        $this->assertIsArray($view->getData());
        
        // Test that href is properly passed to view data
        $data = $view->getData();
        $this->assertEquals('/integration-test', $data['href']);
        
        // Test component property is accessible
        $this->assertEquals('/integration-test', $component->href);
    }

    #[Test]
    public function component_handles_special_characters_in_urls(): void
    {
        $specialUrls = [
            '/search?q=test%20query',
            '/user/123/profile',
            '/api/v1/data',
            '/files/document.pdf',
            '/#section-anchor',
            '/path/with spaces',
            '/unicode/Příklad',
            '/symbols/!@#$%^&*()'
        ];
        
        foreach ($specialUrls as $url) {
            $component = new DropdownLink($url);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            
            $data = $view->getData();
            $this->assertEquals($url, $data['href']);
        }
    }

    #[Test]
    public function component_handles_empty_and_null_like_urls(): void
    {
        // Test empty string
        $component1 = new DropdownLink('');
        $view1 = $component1->render();
        $this->assertInstanceOf(View::class, $view1);
        $this->assertEquals('', $component1->href);
        
        // Test just hash
        $component2 = new DropdownLink('#');
        $view2 = $component2->render();
        $this->assertInstanceOf(View::class, $view2);
        $this->assertEquals('#', $component2->href);
        
        // Test javascript void
        $component3 = new DropdownLink('javascript:void(0)');
        $view3 = $component3->render();
        $this->assertInstanceOf(View::class, $view3);
        $this->assertEquals('javascript:void(0)', $component3->href);
    }

    #[Test]
    public function component_maintains_href_integrity_through_render_cycle(): void
    {
        $originalUrl = '/original/url/path';
        $component = new DropdownLink($originalUrl);
        
        // Get initial state
        $initialHref = $component->href;
        
        // Render the component
        $view = $component->render();
        $data = $view->getData();
        
        // Verify href remains unchanged after rendering
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($initialHref, $component->href);
        $this->assertEquals($originalUrl, $data['href']);
        
        // Render again to ensure consistency
        $view2 = $component->render();
        $data2 = $view2->getData();
        
        $this->assertEquals($originalUrl, $component->href);
        $this->assertEquals($originalUrl, $data2['href']);
    }

    #[Test]
    public function component_works_with_route_helper_style_urls(): void
    {
        // Test URLs that might come from route() helper
        $routeStyleUrls = [
            'http://localhost/admin/dashboard',
            'https://app.example.com/users/create',
            'http://127.0.0.1:8000/test',
            '/admin/users/1/edit',
            '/api/v1/endpoint'
        ];
        
        foreach ($routeStyleUrls as $url) {
            $component = new DropdownLink($url);
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($url, $component->href);
            
            // Ensure the URL is properly passed to the view
            $data = $view->getData();
            $this->assertEquals($url, $data['href']);
        }
    }
}
