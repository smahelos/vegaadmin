<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\CheckSubscription;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CheckSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckSubscription();
    }

    #[Test]
    public function middleware_allows_guest_users(): void
    {
        $request = Request::create('/some-protected-route');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals('Success', $response->getContent());
    }

    #[Test]
    public function middleware_allows_user_with_active_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);
        
        $this->actingAs($user);
        
        $request = Request::create('/some-protected-route');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals('Success', $response->getContent());
    }

    #[Test]
    public function middleware_redirects_user_without_subscription_to_subscription_page(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $request = Request::create('/some-protected-route');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('subscriptions', $response->headers->get('Location'));
    }

    #[Test]
    public function middleware_allows_access_to_subscription_routes(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Test subscription route
        $request = Request::create('/subscriptions');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/subscriptions', []);
            $route->name('subscriptions.index');
            return $route;
        });
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals('Success', $response->getContent());
    }

    #[Test]
    public function middleware_allows_access_to_payment_routes(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Test payment route
        $request = Request::create('/payment/return');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/payment/return', []);
            $route->name('payment.return');
            return $route;
        });
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals('Success', $response->getContent());
    }

    #[Test]
    public function middleware_redirects_user_with_expired_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'expired'
        ]);
        
        $this->actingAs($user);
        
        $request = Request::create('/some-protected-route');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('subscriptions', $response->headers->get('Location'));
    }

    #[Test]
    public function middleware_redirects_user_with_cancelled_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled'
        ]);
        
        $this->actingAs($user);
        
        $request = Request::create('/some-protected-route');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Success');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('subscriptions', $response->headers->get('Location'));
    }

    #[Test]
    public function middleware_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('handle');
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Symfony\Component\HttpFoundation\Response', $returnType->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('next', $parameters[1]->getName());
    }
}
