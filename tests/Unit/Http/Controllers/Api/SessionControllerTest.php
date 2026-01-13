<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session as SessionFacade;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    private SessionController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SessionController();
    }

    #[Test]
    public function check_auth_authenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(true);
        $resp = $this->controller->checkAuth(new Request());
        $this->assertEquals(200, $resp->status());
        $this->assertEquals('authenticated', $resp->getData(true)['status']);
    }

    #[Test]
    public function check_auth_unauthenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(false);
        $resp = $this->controller->checkAuth(new Request());
        $this->assertEquals(401, $resp->status());
        $this->assertEquals('unauthenticated', $resp->getData(true)['status']);
    }

    #[Test]
    public function refresh_session_migrates(): void
    {
        SessionFacade::shouldReceive('migrate')->once()->with(true);
        $resp = $this->controller->refreshSession(new Request());
        $this->assertEquals(200, $resp->status());
        $this->assertEquals('session_refreshed', $resp->getData(true)['status']);
    }
}
