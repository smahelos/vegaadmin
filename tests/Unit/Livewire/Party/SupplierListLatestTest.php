<?php

namespace Tests\Unit\Livewire\Party;

use App\Livewire\Party\SupplierListLatest;
use Livewire\Component;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierListLatestTest extends TestCase
{
    private SupplierListLatest $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new SupplierListLatest;
    }

    #[Test]
    public function component_extends_livewire_component(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
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
