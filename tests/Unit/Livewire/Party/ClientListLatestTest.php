<?php

namespace Tests\Unit\Livewire\Party;

use App\Livewire\Party\ClientListLatest;
use Livewire\Component;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class ClientListLatestTest extends TestCase
{
    private ClientListLatest $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new ClientListLatest;
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
        $reflection = new ReflectionClass($this->component);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Contracts\View\View', $returnType->getName());
    }

    #[Test]
    public function component_has_correct_namespace(): void
    {
        $reflection = new ReflectionClass($this->component);
        $this->assertEquals('App\Livewire\Party', $reflection->getNamespaceName());
    }

    #[Test]
    public function component_has_proper_docblock(): void
    {
        $reflection = new ReflectionClass($this->component);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('ClientListLatest Livewire Component', $docComment);
        $this->assertStringContainsString('latest clients', $docComment);
    }
}
