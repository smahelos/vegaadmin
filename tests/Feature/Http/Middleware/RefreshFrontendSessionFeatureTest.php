<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RefreshFrontendSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshFrontendSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RefreshFrontendSession $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshFrontendSession();
    }

    #[Test]
    public function middleware_sets_session_flag(): void
    {
        $this->assertFalse(session()->has('frontend_session_state'));
        $request = Request::create('/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertTrue(session()->has('frontend_session_state'));
    }

    #[Test]
    public function middleware_does_not_remove_existing_session_keys(): void
    {
        session(['_custom_key' => 'stay']);
        $request = Request::create('/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('stay', session('_custom_key'));
        $this->assertTrue(session()->has('frontend_session_state'));
    }

    #[Test]
    public function session_id_is_not_regenerated_within_refresh_interval(): void
    {
        // First request triggers regeneration
        $request1 = Request::create('/dashboard');
        $idBeforeFirst = session()->getId();
        $this->middleware->handle($request1, fn() => response('OK'));
        $idAfterFirst = session()->getId();
        $this->assertNotEquals($idBeforeFirst, $idAfterFirst, 'First pass should regenerate session id');

        // Second request immediately after should NOT regenerate (interval 15 min)
        $request2 = Request::create('/dashboard');
        $this->middleware->handle($request2, fn() => response('OK'));
        $idAfterSecond = session()->getId();
        $this->assertEquals($idAfterFirst, $idAfterSecond, 'Second pass within interval must keep same session id');
    }

    #[Test]
    public function session_id_is_not_regenerated_within_interval(): void
    {
        $request = Request::create('/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $firstId = session()->getId();

        // Druhý průchod okamžitě – nemělo by dojít k migraci
        $this->middleware->handle($request, fn() => response('OK'));
        $secondId = session()->getId();
        $this->assertSame($firstId, $secondId, 'Session ID should remain the same within regen interval');
    }

    #[Test]
    public function session_id_is_regenerated_after_interval_passes(): void
    {
        $request = Request::create('/dashboard');
        // First pass - causes regeneration and sets timestamp
        $this->middleware->handle($request, fn() => response('OK'));
        $idAfterFirst = session()->getId();
        $this->assertTrue(session()->has('frontend_session_last_regenerated'));
        $originalTimestamp = session('frontend_session_last_regenerated');

        // Simulate passage of 16 minutes ( > 900s )
        session(['frontend_session_last_regenerated' => $originalTimestamp - 901]);

        // Second pass should now regenerate
        $this->middleware->handle($request, fn() => response('OK'));
        $idAfterSecond = session()->getId();
        $this->assertNotEquals($idAfterFirst, $idAfterSecond, 'Session ID should regenerate after interval passes');
        $this->assertGreaterThanOrEqual($originalTimestamp, session('frontend_session_last_regenerated'));
    }

    #[Test]
    public function session_id_remains_same_across_multiple_requests_within_interval(): void
    {
        $request = Request::create('/dashboard');
        // First call (regenerates)
        $this->middleware->handle($request, fn() => response('OK'));
        $baselineId = session()->getId();
        // Multiple rapid calls should not regenerate
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request, fn() => response('OK'));
            $this->assertSame($baselineId, session()->getId(), 'Session ID changed unexpectedly within interval on iteration ' . $i);
        }
    }
}
