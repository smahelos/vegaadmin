<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RefreshBackpackSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshBackpackSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RefreshBackpackSession $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshBackpackSession();
    }

    #[Test]
    public function middleware_sets_session_flag(): void
    {
        $this->assertFalse(session()->has('backpack_session_state'));
        $request = Request::create('/admin/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertTrue(session()->has('backpack_session_state'));
    }

    #[Test]
    public function middleware_does_not_remove_existing_session_keys(): void
    {
        session(['_custom_key' => 'preserved']);
        $this->assertEquals('preserved', session('_custom_key'));
        $request = Request::create('/admin/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('preserved', session('_custom_key'));
        $this->assertTrue(session()->has('backpack_session_state'));
    }

    #[Test]
    public function session_id_is_not_regenerated_within_refresh_interval(): void
    {
        // First request triggers regeneration
        $request1 = Request::create('/admin/dashboard');
        $idBeforeFirst = session()->getId();
        $this->middleware->handle($request1, fn() => response('OK'));
        $idAfterFirst = session()->getId();
        $this->assertNotEquals($idBeforeFirst, $idAfterFirst, 'First pass should regenerate session id');

        // Second immediate request should keep same id
        $request2 = Request::create('/admin/dashboard');
        $this->middleware->handle($request2, fn() => response('OK'));
        $idAfterSecond = session()->getId();
        $this->assertEquals($idAfterFirst, $idAfterSecond, 'Second pass within interval must keep same session id');
    }

    #[Test]
    public function session_id_is_regenerated_after_interval_passes(): void
    {
        $request = Request::create('/admin/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $idAfterFirst = session()->getId();
        $this->assertTrue(session()->has('backpack_session_last_regenerated'));
        $originalTimestamp = session('backpack_session_last_regenerated');

        // Simulate passage > 15 minutes
        session(['backpack_session_last_regenerated' => $originalTimestamp - 901]);

        $this->middleware->handle($request, fn() => response('OK'));
        $idAfterSecond = session()->getId();
        $this->assertNotEquals($idAfterFirst, $idAfterSecond, 'Session ID should regenerate after interval passes');
        $this->assertGreaterThanOrEqual($originalTimestamp, session('backpack_session_last_regenerated'));
    }

    #[Test]
    public function session_id_remains_same_across_multiple_requests_within_interval(): void
    {
        $request = Request::create('/admin/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $baselineId = session()->getId();
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request, fn() => response('OK'));
            $this->assertSame($baselineId, session()->getId(), 'Session ID changed unexpectedly within interval on iteration ' . $i);
        }
    }
}
