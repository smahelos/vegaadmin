<?php

namespace Tests\Unit\Livewire;

use App\Livewire\SupplierList;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierListTest extends TestCase
{
    private SupplierList $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new SupplierList();
    }

    #[Test]
    public function component_extends_livewire_component(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
    }

    #[Test]
    public function component_uses_with_pagination_trait(): void
    {
        $this->assertContains(WithPagination::class, class_uses($this->component));
    }

    #[Test]
    public function component_has_correct_default_properties(): void
    {
        $this->assertEquals('', $this->component->search);
        $this->assertEquals('created_at', $this->component->orderBy);
        $this->assertFalse($this->component->orderAsc);
        $this->assertEquals(1, $this->component->page);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function sort_by_toggles_direction_for_same_field(): void
    {
        $this->component->orderBy = 'name';
        $this->component->orderAsc = false;
        
        $this->component->sortBy('name');
        
        $this->assertEquals('name', $this->component->orderBy);
        $this->assertTrue($this->component->orderAsc);
        
        $this->component->sortBy('name');
        
        $this->assertEquals('name', $this->component->orderBy);
        $this->assertFalse($this->component->orderAsc);
    }

    #[Test]
    public function sort_by_sets_ascending_for_different_field(): void
    {
        $this->component->orderBy = 'name';
        $this->component->orderAsc = false;
        
        $this->component->sortBy('email');
        
        $this->assertEquals('email', $this->component->orderBy);
        $this->assertTrue($this->component->orderAsc);
    }

    #[Test]
    public function mount_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('mount');
        $returnType = $method->getReturnType();
        
        // Mount method should have no return type (implicitly void)
        $this->assertNull($returnType);
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        // Render method should have no explicit return type in this component
        $this->assertNull($returnType);
    }

    #[Test]
    public function sort_by_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $returnType = $method->getReturnType();
        
        $this->assertNull($returnType);
    }

    #[Test]
    public function updating_search_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('updatingSearch');
        $returnType = $method->getReturnType();
        
        $this->assertNull($returnType);
    }
}
