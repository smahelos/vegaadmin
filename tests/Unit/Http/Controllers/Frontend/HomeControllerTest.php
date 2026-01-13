<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    #[Test]
    public function index_method_has_expected_signature(): void
    {
        $controller = new HomeController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('index');
        $this->assertEquals(1, $method->getNumberOfParameters());
        $paramType = $method->getParameters()[0]->getType();
        $this->assertTrue($paramType instanceof \ReflectionNamedType);
        /** @var \ReflectionNamedType $paramType */
        $this->assertEquals('Illuminate\\Http\\Request', $paramType->getName());
    }

    #[Test]
    public function authenticated_user_redirects_to_dashboard(): void
    {
        // Register minimal dashboard route to satisfy route() call
        Route::get('/{locale}/dashboard', fn() => 'ok')->name('frontend.dashboard');

        $controller = new HomeController();
        $user = User::factory()->make();
        $this->be($user, 'web');
        $request = Request::create('/');
        $response = $controller->index($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
        $this->assertStringContainsString('/'.config('app.locale').'/dashboard', $response->headers->get('Location'));
    }
}
