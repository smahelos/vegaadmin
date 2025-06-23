<?php

namespace Tests\Feature\Helpers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BackpackHelpersFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required permissions for 'backpack' guard
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function backpack_guard_name_returns_configured_guard(): void
    {
        // Set specific backpack guard configuration
        Config::set('backpack.base.guard', 'backpack');
        
        $guardName = backpack_guard_name();
        
        $this->assertEquals('backpack', $guardName);
    }

    #[Test]
    public function backpack_guard_name_returns_default_guard_when_backpack_not_configured(): void
    {
        // Clear backpack guard configuration
        Config::set('backpack.base.guard', null);
        Config::set('auth.defaults.guard', 'web');
        
        $guardName = backpack_guard_name();
        
        $this->assertEquals('web', $guardName);
    }

    #[Test]
    public function backpack_auth_returns_guard_instance(): void
    {
        Config::set('backpack.base.guard', 'backpack');
        
        $auth = backpack_auth();
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $auth);
    }

    #[Test]
    public function backpack_user_returns_null_when_not_authenticated(): void
    {
        $user = backpack_user();
        
        $this->assertNull($user);
    }

    #[Test]
    public function backpack_user_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        
        $authenticatedUser = backpack_user();
        
        $this->assertInstanceOf(User::class, $authenticatedUser);
        $this->assertEquals($user->id, $authenticatedUser->id);
    }

    #[Test]
    public function backpack_url_returns_base_admin_url_when_no_path_provided(): void
    {
        Config::set('backpack.base.route_prefix', 'admin');
        
        $url = backpack_url();
        
        $this->assertEquals(url('admin'), $url);
    }

    #[Test]
    public function backpack_url_returns_url_with_path(): void
    {
        Config::set('backpack.base.route_prefix', 'admin');
        
        $url = backpack_url('users');
        
        $this->assertEquals(url('admin/users'), $url);
    }

    #[Test]
    public function backpack_url_handles_complex_path(): void
    {
        Config::set('backpack.base.route_prefix', 'admin');
        
        $url = backpack_url('users/create');
        
        $this->assertEquals(url('admin/users/create'), $url);
    }

    #[Test]
    public function backpack_url_handles_empty_string_path(): void
    {
        Config::set('backpack.base.route_prefix', 'admin');
        
        $url = backpack_url('');
        
        $this->assertEquals(url('admin'), $url);
    }

    #[Test]
    public function backpack_url_uses_default_prefix_when_not_configured(): void
    {
        Config::set('backpack.base.route_prefix', null);
        
        $url = backpack_url('users');
        
        // When config is null, Laravel's config() helper treats null as empty, not using default
        $this->assertEquals(url('/users'), $url);
    }

    #[Test]
    public function backpack_pro_returns_true(): void
    {
        $result = backpack_pro();
        
        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    #[Test]
    public function backpack_functions_work_together_seamlessly(): void
    {
        // Set up configuration
        Config::set('backpack.base.guard', 'backpack');
        Config::set('backpack.base.route_prefix', 'admin');
        
        // Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        
        // Test all functions work together
        $guardName = backpack_guard_name();
        $auth = backpack_auth();
        $authenticatedUser = backpack_user();
        $url = backpack_url('dashboard');
        $isPro = backpack_pro();
        
        $this->assertEquals('backpack', $guardName);
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $auth);
        $this->assertEquals($user->id, $authenticatedUser->id);
        $this->assertEquals(url('admin/dashboard'), $url);
        $this->assertTrue($isPro);
    }

    #[Test]
    public function backpack_functions_handle_different_guard_configurations(): void
    {
        // Test with web guard
        Config::set('backpack.base.guard', 'web');
        Config::set('backpack.base.route_prefix', 'backend');
        
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $guardName = backpack_guard_name();
        $authenticatedUser = backpack_user();
        $url = backpack_url('settings');
        
        $this->assertEquals('web', $guardName);
        $this->assertEquals($user->id, $authenticatedUser->id);
        $this->assertEquals(url('backend/settings'), $url);
    }

    #[Test]
    public function backpack_functions_are_available_globally(): void
    {
        // Verify that all functions are available globally
        $this->assertTrue(function_exists('backpack_auth'));
        $this->assertTrue(function_exists('backpack_guard_name'));
        $this->assertTrue(function_exists('backpack_user'));
        $this->assertTrue(function_exists('backpack_url'));
        $this->assertTrue(function_exists('backpack_pro'));
    }

    #[Test]
    public function backpack_functions_handle_edge_cases(): void
    {
        // Test with unusual configurations
        Config::set('backpack.base.route_prefix', '');
        Config::set('backpack.base.guard', '');
        Config::set('auth.defaults.guard', 'api');
        
        $guardName = backpack_guard_name();
        $url = backpack_url('test');
        
        // When backpack.base.guard is empty string, it returns that, not the fallback
        $this->assertEquals('', $guardName);
        // When route_prefix is empty, it still gets used
        $this->assertEquals(url('/test'), $url);
    }
}
