<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Authenticate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticateTest extends TestCase
{
    private Authenticate $middleware;

    protected function setUp(): void
    {
        parent::setUp();
    $ref = new \ReflectionClass(Authenticate::class);
    $this->middleware = $ref->newInstanceWithoutConstructor();
    }

    #[Test]
    public function redirectTo_method_exists_and_is_protected(): void
    {
        $ref = new \ReflectionClass($this->middleware);
        $this->assertTrue($ref->hasMethod('redirectTo'));
        $method = $ref->getMethod('redirectTo');
        $this->assertTrue($method->isProtected());
    }
}
