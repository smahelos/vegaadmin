<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticateFeatureTest extends TestCase
{
    #[Test]
    public function admin_guest_is_redirected_to_backpack_login(): void
    {
        $request = Request::create('/admin/dashboard');
        $middleware = (new \ReflectionClass(Authenticate::class))->newInstanceWithoutConstructor();
        $method = (new \ReflectionClass($middleware))->getMethod('redirectTo');
        $method->setAccessible(true);
        $path = $method->invoke($middleware, $request);
        $this->assertStringContainsString('admin', $path);
        $this->assertStringContainsString('login', $path);
    }

    #[Test]
    public function frontend_guest_is_redirected_to_frontend_login(): void
    {
        $request = Request::create('/cs/dashboard');
        $middleware = (new \ReflectionClass(Authenticate::class))->newInstanceWithoutConstructor();
        $method = (new \ReflectionClass($middleware))->getMethod('redirectTo');
        $method->setAccessible(true);
        $path = $method->invoke($middleware, $request);
        $this->assertStringContainsString('/cs/login', $path);
    }

    #[Test]
    public function json_request_returns_null_path(): void
    {
        $request = Request::create('/admin/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $middleware = (new \ReflectionClass(Authenticate::class))->newInstanceWithoutConstructor();
        $method = (new \ReflectionClass($middleware))->getMethod('redirectTo');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($middleware, $request));
    }
}
