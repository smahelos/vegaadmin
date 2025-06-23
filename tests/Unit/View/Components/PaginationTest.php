<?php

namespace Tests\Unit\View\Components;

use App\View\Components\Pagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;
use Illuminate\View\View;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    private Pagination $component;
    /** @var MockInterface&LengthAwarePaginator */
    private MockInterface $mockPaginator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // @phpstan-ignore-next-line
        $this->mockPaginator = Mockery::mock(LengthAwarePaginator::class);
        $this->component = new Pagination($this->mockPaginator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function component_exists_and_is_instantiable(): void
    {
        $this->assertInstanceOf(Pagination::class, $this->component);
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
        
        $reflection = new \ReflectionClass(Pagination::class);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    #[Test]
    public function constructor_sets_paginator_correctly(): void
    {
        // @phpstan-ignore-next-line
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $component = new Pagination($paginator);
        
        $this->assertSame($paginator, $component->paginator);
    }

    #[Test]
    public function component_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        
        $this->assertEquals('App\View\Components', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_correct_public_properties(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);
        
        $this->assertContains('paginator', $propertyNames);
    }

    #[Test]
    public function render_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $method = $reflection->getMethod('render');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get the view / contents that represent the component', $docComment);
    }

    #[Test]
    public function constructor_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $constructor = $reflection->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Create a new component instance', $docComment);
    }

    #[Test]
    public function constructor_has_required_parameter(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('paginator', $parameters[0]->getName());
        $this->assertFalse($parameters[0]->isOptional());
        
        // Check parameter type
        $parameterType = $parameters[0]->getType();
        $this->assertNotNull($parameterType);
        $this->assertEquals('Illuminate\Pagination\LengthAwarePaginator', $parameterType->getName());
    }

    #[Test]
    public function component_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\View\Component;', $fileContent);
        $this->assertStringContainsString('use Illuminate\View\View;', $fileContent);
        $this->assertStringContainsString('use Illuminate\Pagination\LengthAwarePaginator;', $fileContent);
    }

    #[Test]
    public function paginator_property_is_documented(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        $paginatorProperty = $reflection->getProperty('paginator');
        
        $this->assertNotFalse($paginatorProperty->getDocComment());
        $this->assertStringContainsString('@var', $paginatorProperty->getDocComment());
        $this->assertStringContainsString('LengthAwarePaginator', $paginatorProperty->getDocComment());
        $this->assertStringContainsString('Paginator instance', $paginatorProperty->getDocComment());
    }

    #[Test]
    public function render_method_returns_correct_view(): void
    {
        $reflection = new \ReflectionClass(Pagination::class);
        
        // Check that the method body contains the expected view call
        $fileContent = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString("view('components.pagination')", $fileContent);
    }

    #[Test]
    public function component_accepts_real_paginator_instance(): void
    {
        // Create a real paginator instance for more realistic testing
        $items = collect(['item1', 'item2', 'item3']);
        $perPage = 2;
        $currentPage = 1;
        $path = '/test';
        
        $paginator = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => $path]
        );
        
        $component = new Pagination($paginator);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $component->paginator);
        $this->assertEquals($perPage, $component->paginator->perPage());
        $this->assertEquals($currentPage, $component->paginator->currentPage());
    }
}
