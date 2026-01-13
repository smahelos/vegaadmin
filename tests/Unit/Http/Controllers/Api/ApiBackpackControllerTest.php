<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBackpackController;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ReflectionClass;

class ApiBackpackControllerTest extends TestCase
{
    private ApiBackpackController $controller;
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ApiBackpackController::class);
        // Use simple value object instead of real Eloquent model to keep unit test isolated from framework boot logic
        $this->user = new class {
            public int $id = 99;
            public string $name = 'Admin';
            public function hasRole(string $role): bool { return false; }
        };
    }

    #[Test]
    public function get_authenticated_user_prefers_backpack_guard(): void
    {
    $backpackGuard = \Mockery::mock();
        $backpackGuard->shouldReceive('check')->andReturn(true);
        $backpackGuard->shouldReceive('user')->andReturn($this->user);
        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->andReturn(false);
        $sanctumGuard = \Mockery::mock();
        $sanctumGuard->shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('guard')->with('backpack')->andReturn($backpackGuard);
        Auth::shouldReceive('guard')->with('web')->andReturn($webGuard);
        Auth::shouldReceive('guard')->with('sanctum')->andReturn($sanctumGuard);
        $u = $this->invokeProtected('getAuthenticatedUser');
        $this->assertEquals(99, $u->id);
    }

    #[Test]
    public function get_authenticated_user_falls_back_to_web(): void
    {
        // Create dedicated guard mocks (backpack false, web true)
        $backpackGuard = \Mockery::mock();
        $backpackGuard->shouldReceive('check')->andReturn(false);
        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->andReturn(true);
        $webGuard->shouldReceive('user')->andReturn($this->user);
        $sanctumGuard = \Mockery::mock();
        $sanctumGuard->shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('guard')->with('backpack')->andReturn($backpackGuard);
        Auth::shouldReceive('guard')->with('web')->andReturn($webGuard);
        Auth::shouldReceive('guard')->with('sanctum')->andReturn($sanctumGuard);
        $u = $this->invokeProtected('getAuthenticatedUser');
        $this->assertEquals(99, $u->id);
    }

    #[Test]
    public function get_log_context_contains_expected_keys(): void
    {
        $backpackGuard = \Mockery::mock();
        $backpackGuard->shouldReceive('check')->andReturn(false);
        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->andReturn(false);
        $sanctumGuard = \Mockery::mock();
        $sanctumGuard->shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('guard')->with('backpack')->andReturn($backpackGuard);
        Auth::shouldReceive('guard')->with('web')->andReturn($webGuard);
        Auth::shouldReceive('guard')->with('sanctum')->andReturn($sanctumGuard);
        $ctx = $this->invokeProtected('getLogContext', [['extra' => 'value']]);
        $this->assertArrayHasKey('user_id', $ctx);
        $this->assertArrayHasKey('guards', $ctx);
        $this->assertEquals('value', $ctx['extra']);
    }

    private function invokeProtected(string $method, array $args = [])
    {
        $ref = new ReflectionClass($this->controller);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->controller, $args);
    }
}
