<?php

namespace Tests\Unit\Livewire\Invoice;

use App\Livewire\Invoice\InvoiceListRecent;
use Livewire\Component;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListRecentTest extends TestCase
{
    private InvoiceListRecent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new InvoiceListRecent;
    }

    #[Test]
    public function component_extends_livewire_component(): void
    {
        $this->assertInstanceOf(Component::class, $this->component);
    }

    #[Test]
    public function render_method_exists(): void
    {
        $this->assertTrue(method_exists($this->component, 'render'));
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
    public function component_has_correct_namespace(): void
    {
        $this->assertEquals('App\Livewire\Invoice', (new \ReflectionClass($this->component))->getNamespaceName());
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('InvoiceListRecent Livewire Component', $docComment);
    }
}
