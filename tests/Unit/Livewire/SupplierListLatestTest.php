<?php

namespace Tests\Unit\Livewire;

use App\Livewire\SupplierListLatest;
use Livewire\Component;
use Livewire\WithPagination;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierListLatestTest extends TestCase
{
    private SupplierListLatest $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new SupplierListLatest();
    }

    #[Test]
    public function component_extends_livewire_component(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
    }

    #[Test]
    public function component_uses_with_pagination_trait(): void
    {
        $traits = class_uses_recursive(SupplierListLatest::class);
        $this->assertContains(WithPagination::class, $traits);
    }

    #[Test]
    public function component_has_required_properties(): void
    {
        $this->assertObjectHasProperty('orderAsc', $this->component);
        $this->assertObjectHasProperty('orderBy', $this->component);
        $this->assertObjectHasProperty('errorMessage', $this->component);
    }

    #[Test]
    public function component_has_default_property_values(): void
    {
        $this->assertFalse($this->component->orderAsc);
        $this->assertEquals('created_at', $this->component->orderBy);
        $this->assertNull($this->component->errorMessage);
    }

    #[Test]
    public function sort_by_toggles_order_for_same_field(): void
    {
        $this->component->orderBy = 'name';
        $this->component->orderAsc = false;

        $this->component->sortBy('name');

        $this->assertTrue($this->component->orderAsc);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        $this->component->orderBy = 'created_at';
        $this->component->orderAsc = false;

        $this->component->sortBy('name');

        $this->assertEquals('name', $this->component->orderBy);
        $this->assertTrue($this->component->orderAsc);
    }

    #[Test]
    public function sort_by_method_exists_and_is_public(): void
    {
        $this->assertTrue(method_exists($this->component, 'sortBy'));
        
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('sortBy');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function render_method_exists_and_is_public(): void
    {
        $this->assertTrue(method_exists($this->component, 'render'));
        
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isPublic());
    }
}
