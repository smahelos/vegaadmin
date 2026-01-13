<?php

namespace Tests\Unit\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Frontend\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    #[Test]
    public function authenticated_method_redirects_to_localized_dashboard(): void
    {
        // Prepare route for assertion
        Route::get('/{locale}/dashboard', fn() => 'ok')->name('frontend.dashboard');

        $controller = new LoginController();
        $request = Request::create('/login', 'POST');
        $user = new User();
        $redirect = (function() use ($controller, $request, $user) {
            $reflection = new \ReflectionClass($controller);
            $m = $reflection->getMethod('authenticated');
            $m->setAccessible(true);
            return $m->invoke($controller, $request, $user);
        })();
        $this->assertTrue($redirect->isRedirection());
        $this->assertStringContainsString('/'.config('app.locale').'/dashboard', $redirect->getTargetUrl());
    }

    #[Test]
    public function show_login_form_returns_view(): void
    {
        $controller = new LoginController();
        $view = $controller->showLoginForm();
        $this->assertEquals('auth.login', $view->name());
    }
}
