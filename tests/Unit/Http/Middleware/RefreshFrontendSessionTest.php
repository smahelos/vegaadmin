<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RefreshFrontendSession;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshFrontendSessionTest extends TestCase
{
    private RefreshFrontendSession $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshFrontendSession();
    }

    #[Test]
    public function handle_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $this->assertTrue($reflection->hasMethod('handle'));
        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic());
    }
}
