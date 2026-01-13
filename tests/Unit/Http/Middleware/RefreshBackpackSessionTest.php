<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RefreshBackpackSession;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshBackpackSessionTest extends TestCase
{
    private RefreshBackpackSession $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshBackpackSession();
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
