<?php

namespace Tests\Feature\View\Components;

use App\View\Components\Pagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createPaginator(int $perPage = 10, int $currentPage = 1, int $total = 50): LengthAwarePaginator
    {
        $items = collect(range(1, $total));
        return new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => '/test']
        );
    }

    #[Test]
    public function component_renders_successfully_with_real_paginator(): void
    {
        $paginator = $this->createPaginator();
        $component = new Pagination($paginator);
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
    }

    #[Test]
    public function component_uses_correct_view_template(): void
    {
        $paginator = $this->createPaginator();
        $component = new Pagination($paginator);
        
        $view = $component->render();
        
        $this->assertEquals('components.pagination', $view->getName());
    }

    #[Test]
    public function component_view_template_exists(): void
    {
        $paginator = $this->createPaginator();
        $component = new Pagination($paginator);
        
        $view = $component->render();
        
        // Verify the template file exists and view can be created
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.pagination', $view->getName());
        
        // Check that the view file exists
        $viewPath = resource_path('views/components/pagination.blade.php');
        $this->assertFileExists($viewPath);
    }

    #[Test]
    public function component_integrates_with_real_paginator_instance(): void
    {
        $paginator = $this->createPaginator(5, 2, 25);
        $component = new Pagination($paginator);
        
        // Verify component holds the paginator
        $this->assertSame($paginator, $component->paginator);
        
        // Verify paginator properties work correctly
        $this->assertEquals(5, $component->paginator->perPage());
        $this->assertEquals(2, $component->paginator->currentPage());
        $this->assertEquals(25, $component->paginator->total());
        $this->assertEquals(5, $component->paginator->lastPage());
    }

    #[Test]
    public function component_handles_different_pagination_scenarios(): void
    {
        // Scenario 1: First page
        $paginator1 = $this->createPaginator(10, 1, 50);
        $component1 = new Pagination($paginator1);
        $view1 = $component1->render();
        
        $this->assertInstanceOf(View::class, $view1);
        $this->assertEquals(1, $component1->paginator->currentPage());
        $this->assertTrue($component1->paginator->onFirstPage());
        
        // Scenario 2: Middle page
        $paginator2 = $this->createPaginator(10, 3, 50);
        $component2 = new Pagination($paginator2);
        $view2 = $component2->render();
        
        $this->assertInstanceOf(View::class, $view2);
        $this->assertEquals(3, $component2->paginator->currentPage());
        $this->assertFalse($component2->paginator->onFirstPage());
        $this->assertFalse($component2->paginator->onLastPage());
        
        // Scenario 3: Last page
        $paginator3 = $this->createPaginator(10, 5, 50);
        $component3 = new Pagination($paginator3);
        $view3 = $component3->render();
        
        $this->assertInstanceOf(View::class, $view3);
        $this->assertEquals(5, $component3->paginator->currentPage());
        $this->assertTrue($component3->paginator->onLastPage());
    }

    #[Test]
    public function component_works_with_empty_pagination(): void
    {
        $paginator = $this->createPaginator(10, 1, 0);
        $component = new Pagination($paginator);
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals(0, $component->paginator->total());
        $this->assertEquals(1, $component->paginator->currentPage());
        $this->assertTrue($component->paginator->onFirstPage());
        $this->assertTrue($component->paginator->onLastPage());
    }

    #[Test]
    public function component_handles_single_page_pagination(): void
    {
        $paginator = $this->createPaginator(20, 1, 15);
        $component = new Pagination($paginator);
        
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals(15, $component->paginator->total());
        $this->assertEquals(1, $component->paginator->lastPage());
        $this->assertTrue($component->paginator->onFirstPage());
        $this->assertTrue($component->paginator->onLastPage());
    }

    #[Test]
    public function component_maintains_paginator_functionality(): void
    {
        $items = collect(['item1', 'item2', 'item3', 'item4', 'item5']);
        $paginator = new LengthAwarePaginator(
            $items->forPage(1, 2),
            $items->count(),
            2,
            1,
            ['path' => '/test-path']
        );
        
        $component = new Pagination($paginator);
        
        // Test that paginator methods work correctly
        $this->assertEquals(2, $component->paginator->perPage());
        $this->assertEquals(5, $component->paginator->total());
        $this->assertEquals(3, $component->paginator->lastPage());
        $this->assertCount(2, $component->paginator->items());
        
        // Test pagination URLs
        $this->assertStringContainsString('/test-path', $component->paginator->url(2));
        $this->assertStringContainsString('page=2', $component->paginator->nextPageUrl());
    }

    #[Test]
    public function component_integrates_with_laravel_view_system(): void
    {
        $paginator = $this->createPaginator();
        $component = new Pagination($paginator);
        
        // Test that the component integrates properly with Laravel's view system
        $view = $component->render();
        
        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getName() !== '');
        
        // Test component properties are accessible
        $this->assertInstanceOf(LengthAwarePaginator::class, $component->paginator);
    }

    #[Test]
    public function component_can_be_rendered_multiple_times_consistently(): void
    {
        $paginator = $this->createPaginator(5, 2, 30);
        $component1 = new Pagination($paginator);
        $component2 = new Pagination($paginator);
        
        $view1 = $component1->render();
        $view2 = $component2->render();
        
        $this->assertEquals($view1->getName(), $view2->getName());
        $this->assertEquals($component1->paginator->currentPage(), $component2->paginator->currentPage());
        $this->assertEquals($component1->paginator->total(), $component2->paginator->total());
    }

    #[Test]
    public function component_works_with_different_per_page_values(): void
    {
        $testCases = [
            ['perPage' => 5, 'total' => 50, 'expectedPages' => 10],
            ['perPage' => 10, 'total' => 50, 'expectedPages' => 5],
            ['perPage' => 25, 'total' => 50, 'expectedPages' => 2],
            ['perPage' => 100, 'total' => 50, 'expectedPages' => 1],
        ];
        
        foreach ($testCases as $case) {
            $paginator = $this->createPaginator($case['perPage'], 1, $case['total']);
            $component = new Pagination($paginator);
            
            $view = $component->render();
            
            $this->assertInstanceOf(View::class, $view);
            $this->assertEquals($case['perPage'], $component->paginator->perPage());
            $this->assertEquals($case['total'], $component->paginator->total());
            $this->assertEquals($case['expectedPages'], $component->paginator->lastPage());
        }
    }

    #[Test]
    public function component_preserves_paginator_state_through_render_cycle(): void
    {
        $originalPaginator = $this->createPaginator(15, 3, 100);
        $component = new Pagination($originalPaginator);
        
        // Get initial state
        $initialCurrentPage = $component->paginator->currentPage();
        $initialTotal = $component->paginator->total();
        $initialPerPage = $component->paginator->perPage();
        
        // Render the component
        $view = $component->render();
        
        // Verify state remains unchanged after rendering
        $this->assertEquals($initialCurrentPage, $component->paginator->currentPage());
        $this->assertEquals($initialTotal, $component->paginator->total());
        $this->assertEquals($initialPerPage, $component->paginator->perPage());
        $this->assertSame($originalPaginator, $component->paginator);
        
        // Render again to ensure consistency
        $view2 = $component->render();
        
        $this->assertEquals($initialCurrentPage, $component->paginator->currentPage());
        $this->assertEquals($initialTotal, $component->paginator->total());
        $this->assertEquals($initialPerPage, $component->paginator->perPage());
    }

    #[Test]
    public function component_handles_pagination_edge_cases(): void
    {
        // Test with maximum items per page
        $paginator1 = $this->createPaginator(1000, 1, 500);
        $component1 = new Pagination($paginator1);
        $view1 = $component1->render();
        $this->assertInstanceOf(View::class, $view1);
        
        // Test with single item
        $paginator2 = $this->createPaginator(10, 1, 1);
        $component2 = new Pagination($paginator2);
        $view2 = $component2->render();
        $this->assertInstanceOf(View::class, $view2);
        
        // Test with very large total
        $paginator3 = $this->createPaginator(10, 1, 10000);
        $component3 = new Pagination($paginator3);
        $view3 = $component3->render();
        $this->assertInstanceOf(View::class, $view3);
        $this->assertEquals(1000, $component3->paginator->lastPage());
    }
}
