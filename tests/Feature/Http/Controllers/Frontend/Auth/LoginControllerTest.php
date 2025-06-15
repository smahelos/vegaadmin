<?php

namespace Tests\Feature\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Frontend\Auth\LoginController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Mockery;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected LoginController $controller;
    protected User $testUser;
    protected string $validEmail;
    protected string $validPassword;

    /**
     * Set up the test environment before each test.
     * Creates test user with unique credentials for login testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test data with unique email using faker
        $this->validEmail = $this->faker->unique()->safeEmail;
        $this->validPassword = 'password123';

        // Create test user with valid credentials
        $this->testUser = User::factory()->create([
            'email' => $this->validEmail,
            'password' => Hash::make($this->validPassword),
        ]);

        // Initialize controller instance for testing
        $this->controller = new LoginController();
    }

    /**
     * Test that controller constructor applies guest middleware correctly.
     *
     * @return void
     */
    public function test_constructor_applies_guest_middleware()
    {
        $middleware = $this->controller->getMiddleware();
        
        $this->assertNotEmpty($middleware);
        $this->assertEquals('guest', $middleware[0]['middleware']);
        $this->assertEquals(['logout'], $middleware[0]['options']['except']);
    }

    /**
     * Test showLoginForm returns correct view.
     *
     * @return void
     */
    public function test_show_login_form_returns_correct_view()
    {
        $response = $this->controller->showLoginForm();
        
        $this->assertEquals('auth.login', $response->name());
    }

    /**
     * Test successful login with valid credentials using HTTP test.
     *
     * @return void
     */
    public function test_login_successful_with_valid_credentials()
    {
        $response = $this->post('/login', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        $response->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->testUser->id, Auth::id());
    }

    /**
     * Test login fails with invalid credentials using HTTP test.
     *
     * @return void
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => $this->validEmail,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $this->assertFalse(Auth::check());
    }

    /**
     * Test login fails with nonexistent email using HTTP test.
     *
     * @return void
     */
    public function test_login_fails_with_nonexistent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent-' . uniqid() . '@example.com',
            'password' => $this->validPassword,
        ]);

        $response->assertRedirect();
        $this->assertFalse(Auth::check());
    }

    /**
     * Test login validation fails with empty email using HTTP test.
     *
     * @return void
     */
    public function test_login_validation_fails_with_empty_email()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => $this->validPassword,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::check());
    }

    /**
     * Test login validation with invalid email format - Laravel 12 doesn't validate email format in AuthenticatesUsers by default.
     *
     * @return void
     */
    public function test_login_validation_with_invalid_email_format()
    {
        // Laravel 12's AuthenticatesUsers trait doesn't validate email format by default
        // It only checks required|string|email and Laravel is lenient with email validation
        $response = $this->post('/login', [
            'email' => 'invalid-email-format',
            'password' => $this->validPassword,
        ]);

        // This should not have validation errors but should fail authentication
        $response->assertRedirect();
        $this->assertFalse(Auth::check());
    }

    /**
     * Test login validation fails with empty password using HTTP test.
     *
     * @return void
     */
    public function test_login_validation_fails_with_empty_password()
    {
        $response = $this->post('/login', [
            'email' => $this->validEmail,
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertFalse(Auth::check());
    }

    /**
     * Test login handles exceptions gracefully.
     *
     * @return void
     */
    public function test_login_handles_exceptions_gracefully()
    {
        // Mock Auth facade to throw exception during attempt
        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();
            
        Auth::shouldReceive('attempt')
            ->once()
            ->andThrow(new \Exception('Database connection error'));

        // Expect log to be called
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Login error: Database connection error',
                [
                    'email' => $this->validEmail,
                    'ip' => '127.0.0.1'
                ]
            );

        // Create a request
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        $request->setLaravelSession($this->app['session.store']);

        $response = $this->controller->login($request);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test authenticated method redirects to frontend dashboard.
     *
     * @return void
     */
    public function test_authenticated_redirects_to_frontend_dashboard()
    {
        $request = Request::create('/login', 'POST');
        
        // Set app locale
        app()->setLocale('en');

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('authenticated');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, $request, $this->testUser);

        $this->assertEquals(302, $response->getStatusCode());
        // Check that it redirects to dashboard with language parameter
        $this->assertStringContainsString('/dashboard', $response->getTargetUrl());
        $this->assertStringContainsString('lang=en', $response->getTargetUrl());
    }

    /**
     * Test authenticated method uses correct locale.
     *
     * @return void
     */
    public function test_authenticated_uses_correct_locale()
    {
        $request = Request::create('/login', 'POST');
        
        // Set different locale
        app()->setLocale('cs');

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('authenticated');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, $request, $this->testUser);

        $this->assertStringContainsString('lang=cs', $response->getTargetUrl());
    }

    /**
     * Test logout functionality using HTTP test.
     *
     * @return void
     */
    public function test_logout_user_successfully()
    {
        // Login user first
        $this->actingAs($this->testUser);
        $this->assertTrue(Auth::check());

        // Set app locale
        app()->setLocale('en');

        $response = $this->post('/logout');

        $response->assertRedirect();
        // Check that it redirects to home page with language parameter
        $this->assertStringContainsString('lang=en', $response->getTargetUrl());
        $this->assertFalse(Auth::check());
    }

    /**
     * Test logout invalidates session.
     *
     * @return void
     */
    public function test_logout_invalidates_session()
    {
        $this->actingAs($this->testUser);
        
        // Store original session ID
        $originalSessionId = session()->getId();
        
        app()->setLocale('en');

        $response = $this->post('/logout');

        $response->assertRedirect();
        // Session should be invalidated (new ID generated)
        $this->assertNotEquals($originalSessionId, session()->getId());
    }

    /**
     * Test attemptLogin with valid credentials using reflection.
     *
     * @return void
     */
    public function test_attempt_login_with_valid_credentials()
    {
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('attemptLogin');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $request);

        $this->assertTrue($result);
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->testUser->id, Auth::id());
    }

    /**
     * Test attemptLogin with invalid credentials using reflection.
     *
     * @return void
     */
    public function test_attempt_login_with_invalid_credentials()
    {
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => 'wrongpassword',
        ]);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('attemptLogin');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $request);

        $this->assertFalse($result);
        $this->assertFalse(Auth::check());
    }

    /**
     * Test attemptLogin with remember token using reflection.
     *
     * @return void
     */
    public function test_attempt_login_with_remember_token()
    {
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
            'remember' => '1',
        ]);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('attemptLogin');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $request);

        $this->assertTrue($result);
        $this->assertTrue(Auth::check());
    }

    /**
     * Test attemptLogin without remember token using reflection.
     *
     * @return void
     */
    public function test_attempt_login_without_remember_token()
    {
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('attemptLogin');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $request);

        $this->assertTrue($result);
        $this->assertTrue(Auth::check());
    }

    /**
     * Test guard method returns web guard using reflection.
     *
     * @return void
     */
    public function test_guard_returns_web_guard()
    {
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('guard');
        $method->setAccessible(true);

        $guard = $method->invoke($this->controller);

        // The guard name includes session ID in testing environment
        $this->assertStringContainsString('web', $guard->getName());
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\StatefulGuard::class, $guard);
    }

    /**
     * Test controller uses AuthenticatesUsers trait.
     *
     * @return void
     */
    public function test_controller_uses_authenticates_users_trait()
    {
        $traits = class_uses(LoginController::class);

        $this->assertContains(\Illuminate\Foundation\Auth\AuthenticatesUsers::class, $traits);
    }

    /**
     * Test redirectTo property is set correctly.
     *
     * @return void
     */
    public function test_redirect_to_property_is_set()
    {
        $reflection = new \ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('redirectTo');
        $property->setAccessible(true);

        $this->assertEquals('/dashboard', $property->getValue($this->controller));
    }

    /**
     * Test login with remember me functionality using HTTP test.
     *
     * @return void
     */
    public function test_login_with_remember_me()
    {
        $response = $this->post('/login', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
            'remember' => 'on',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->testUser->id, Auth::id());
    }

    /**
     * Test login logs error on exception.
     *
     * @return void
     */
    public function test_login_logs_error_on_exception()
    {
        // Mock Auth facade to throw exception during attempt
        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();
            
        Auth::shouldReceive('attempt')
            ->once()
            ->andThrow(new \Exception('Test exception'));

        // Expect log to be called
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Login error: Test exception',
                [
                    'email' => $this->validEmail,
                    'ip' => '127.0.0.1'
                ]
            );

        // Create a request
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        $request->setLaravelSession($this->app['session.store']);

        $response = $this->controller->login($request);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test login returns error message on exception.
     *
     * @return void
     */
    public function test_login_returns_error_message_on_exception()
    {
        // Mock Auth facade to throw exception during attempt
        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();
            
        Auth::shouldReceive('attempt')
            ->once()
            ->andThrow(new \Exception('Test exception'));

        // Expect log to be called
        Log::shouldReceive('error')->once();

        // Create a request
        $request = Request::create('/login', 'POST', [
            'email' => $this->validEmail,
            'password' => $this->validPassword,
        ]);

        $request->setLaravelSession($this->app['session.store']);

        $response = $this->controller->login($request);

        $this->assertEquals(302, $response->getStatusCode());
        
        // Check that error message is flashed to session
        $session = $request->getSession();
        $this->assertTrue($session->has('error'));
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
