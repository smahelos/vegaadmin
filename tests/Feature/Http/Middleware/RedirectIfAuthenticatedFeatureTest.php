<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RedirectIfAuthenticatedFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RedirectIfAuthenticated $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RedirectIfAuthenticated();
    }

    #[Test]
    public function guest_user_passes_through(): void
    {
        $request = Request::create('/login');
        $result = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('OK', $result->getContent());
    }

    #[Test]
    public function authenticated_frontend_user_redirects_to_frontend_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        $request = Request::create('/login');
        $response = $this->middleware->handle($request, fn() => response('OK'), 'web');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard', $response->headers->get('Location'));
    }

    #[Test]
    public function authenticated_backpack_user_redirects_to_backpack_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/login');
        $response = $this->middleware->handle($request, fn() => response('OK'), 'backpack');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('admin', $response->headers->get('Location'));
    }
}
