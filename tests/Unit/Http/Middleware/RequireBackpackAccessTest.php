<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RequireBackpackAccess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequireBackpackAccessTest extends TestCase
{
    private RequireBackpackAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackAccess();
    }

    #[Test]
    public function respondToUnauthorizedRequest_method_exists(): void
    {
        $ref = new \ReflectionClass($this->middleware);
        $this->assertTrue($ref->hasMethod('handle'));
        $this->assertTrue($ref->hasMethod('respondToUnauthorizedRequest'));
        $priv = $ref->getMethod('respondToUnauthorizedRequest');
        $this->assertTrue($priv->isPrivate());
    }
}
