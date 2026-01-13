<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RequireBackpackApiAccess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequireBackpackApiAccessTest extends TestCase
{
    private RequireBackpackApiAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackApiAccess();
    }

    #[Test]
    public function handle_and_private_helpers_exist(): void
    {
        $ref = new \ReflectionClass($this->middleware);
        $this->assertTrue($ref->hasMethod('handle'));
        $this->assertTrue($ref->hasMethod('unauthorized'));
        $this->assertTrue($ref->hasMethod('forbidden'));
    }
}
