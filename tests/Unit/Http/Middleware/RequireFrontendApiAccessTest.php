<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RequireFrontendApiAccess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequireFrontendApiAccessTest extends TestCase
{
    private RequireFrontendApiAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireFrontendApiAccess();
    }

    #[Test]
    public function private_helpers_exist(): void
    {
        $ref = new \ReflectionClass($this->middleware);
        $this->assertTrue($ref->hasMethod('handle'));
        $this->assertTrue($ref->hasMethod('unauthorized'));
        $this->assertTrue($ref->hasMethod('forbidden'));
    }
}
