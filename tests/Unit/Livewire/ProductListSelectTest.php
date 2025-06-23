<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ProductListSelect;
use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductListSelectTest extends TestCase
{
    private ProductListSelect $component;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->component = new ProductListSelect();
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
        $this->assertEquals('name', $this->component->sortField);
        $this->assertEquals('asc', $this->component->sortDirection);
        $this->assertEquals(10, $this->component->perPage);
        $this->assertEquals([], $this->component->selectedProductIds);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function component_has_correct_listeners(): void
    {
        $expectedListeners = [
            'setSelectedProductIds' => 'handleSetSelectedProductIds'
        ];
        
        $reflection = new \ReflectionClass($this->component);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);
        $listeners = $property->getValue($this->component);
        
        $this->assertEquals($expectedListeners, $listeners);
    }

    #[Test]
    public function set_selected_product_ids_sets_array_correctly(): void
    {
        $ids = [1, 2, 3];
        $this->component->setSelectedProductIds($ids);
        
        $this->assertEquals($ids, $this->component->selectedProductIds);
    }

    #[Test]
    public function sort_by_toggles_direction_for_same_field(): void
    {
        $this->component->sortField = 'name';
        $this->component->sortDirection = 'asc';
        
        $this->component->sortBy('name');
        
        $this->assertEquals('name', $this->component->sortField);
        $this->assertEquals('desc', $this->component->sortDirection);
        
        $this->component->sortBy('name');
        
        $this->assertEquals('name', $this->component->sortField);
        $this->assertEquals('asc', $this->component->sortDirection);
    }

    #[Test]
    public function sort_by_sets_ascending_for_different_field(): void
    {
        $this->component->sortField = 'name';
        $this->component->sortDirection = 'desc';
        
        $this->component->sortBy('price');
        
        $this->assertEquals('price', $this->component->sortField);
        $this->assertEquals('asc', $this->component->sortDirection);
    }

    #[Test]
    public function handle_set_selected_product_ids_with_array_structure(): void
    {
        $params = ['ids' => [1, 2, 3]];
        $this->component->handleSetSelectedProductIds($params);
        
        $this->assertEquals([1, 2, 3], $this->component->selectedProductIds);
    }

    #[Test]
    public function handle_set_selected_product_ids_with_direct_array(): void
    {
        $params = [1, 2, 3];
        $this->component->handleSetSelectedProductIds($params);
        
        $this->assertEquals([1, 2, 3], $this->component->selectedProductIds);
    }

    #[Test]
    public function handle_set_selected_product_ids_ignores_invalid_input(): void
    {
        $originalIds = [4, 5, 6];
        $this->component->selectedProductIds = $originalIds;
        
        // Test with string input (should be ignored)
        $this->component->handleSetSelectedProductIds('invalid');
        $this->assertEquals($originalIds, $this->component->selectedProductIds);
        
        // Test with null input (should be ignored)
        $this->component->handleSetSelectedProductIds(null);
        $this->assertEquals($originalIds, $this->component->selectedProductIds);
    }

    #[Test]
    public function mount_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('mount');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function render_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Contracts\View\View', $returnType->getName());
    }

    #[Test]
    public function select_product_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('selectProduct');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function set_selected_product_ids_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('setSelectedProductIds');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function sort_by_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function updating_search_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('updatingSearch');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function handle_set_selected_product_ids_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('handleSetSelectedProductIds');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }
}
