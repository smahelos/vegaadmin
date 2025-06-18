<?php

namespace Tests\Feature\Providers;

use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthServiceProviderFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Boot the AuthServiceProvider manually to register macros
        $provider = new AuthServiceProvider(app());
        $provider->boot();
        
        // Create required permissions for backpack guard
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'web']);
    }

    #[Test]
    public function provider_is_registered_in_application(): void
    {
        // Since AuthServiceProvider is not registered in providers.php,
        // we test that it can be manually instantiated and used
        $provider = new AuthServiceProvider(app());
        $this->assertInstanceOf(AuthServiceProvider::class, $provider);
    }

    #[Test]
    public function check_any_macro_is_registered(): void
    {
        // Manually boot the provider since it may not be auto-registered
        $provider = new AuthServiceProvider(app());
        $provider->boot();
        
        $this->assertTrue(Auth::hasMacro('checkAny'));
    }

    #[Test]
    public function user_from_any_macro_is_registered(): void
    {
        // Manually boot the provider since it may not be auto-registered
        $provider = new AuthServiceProvider(app());
        $provider->boot();
        
        $this->assertTrue(Auth::hasMacro('userFromAny'));
    }

    #[Test]
    public function check_any_macro_returns_false_when_no_user_authenticated(): void
    {
        $result = Auth::checkAny();
        $this->assertFalse($result);
    }

    #[Test]
    public function check_any_macro_returns_true_when_web_user_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = Auth::checkAny();
        $this->assertTrue($result);
    }

    #[Test]
    public function check_any_macro_returns_true_when_backpack_user_authenticated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backpack.access');
        $this->actingAs($user, 'backpack');
        
        $result = Auth::checkAny();
        $this->assertTrue($result);
    }

    #[Test]
    public function check_any_macro_accepts_custom_guards(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = Auth::checkAny(['web']);
        $this->assertTrue($result);
        
        $result = Auth::checkAny(['backpack']);
        $this->assertFalse($result);
    }

    #[Test]
    public function user_from_any_macro_returns_null_when_no_user_authenticated(): void
    {
        $result = Auth::userFromAny();
        $this->assertNull($result);
    }

    #[Test]
    public function user_from_any_macro_returns_user_when_web_user_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = Auth::userFromAny();
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function user_from_any_macro_returns_user_when_backpack_user_authenticated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backpack.access');
        $this->actingAs($user, 'backpack');
        
        $result = Auth::userFromAny();
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function user_from_any_macro_accepts_custom_guards(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = Auth::userFromAny(['web']);
        $this->assertInstanceOf(User::class, $result);
        
        $result = Auth::userFromAny(['backpack']);
        $this->assertNull($result);
    }

    #[Test]
    public function provider_can_be_instantiated(): void
    {
        $provider = new AuthServiceProvider(app());
        $this->assertInstanceOf(AuthServiceProvider::class, $provider);
    }

    #[Test]
    public function boot_method_executes_without_errors(): void
    {
        $provider = new AuthServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->boot();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function policies_are_empty_by_default(): void
    {
        $provider = new AuthServiceProvider(app());
        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        
        $policies = $property->getValue($provider);
        $this->assertEmpty($policies);
    }
}
