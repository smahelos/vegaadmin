<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RedirectIfAuthenticatedTest extends TestCase
{
    private RedirectIfAuthenticated $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RedirectIfAuthenticated();
    }

    #[Test]
    public function handle_method_signature_is_valid(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('handle', $method->getName());
        $params = $method->getParameters();
        $this->assertCount(3, $params); // request, next, guards variadic
        $this->assertTrue($params[2]->isVariadic());
    }
}
