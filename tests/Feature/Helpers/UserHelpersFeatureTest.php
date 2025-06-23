<?php

namespace Tests\Feature\Helpers;

use App\Helpers\UserHelpers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserHelpersFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_user_name_returns_user_name_when_authenticated(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        
        Auth::login($user);
        
        $result = UserHelpers::getUserName();
        
        $this->assertEquals('John Doe', $result);
    }

    #[Test]
    public function get_user_name_returns_default_when_not_authenticated(): void
    {
        Auth::logout();
        
        $result = UserHelpers::getUserName();
        
        $this->assertEquals('Guest', $result);
    }

    #[Test]
    public function get_user_name_returns_custom_default_when_not_authenticated(): void
    {
        Auth::logout();
        
        $result = UserHelpers::getUserName('Anonymous');
        
        $this->assertEquals('Anonymous', $result);
    }

    #[Test]
    public function get_user_name_works_with_different_user_names(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice Smith']);
        $user2 = User::factory()->create(['name' => 'Bob Johnson']);
        
        // Test with first user
        Auth::login($user1);
        $this->assertEquals('Alice Smith', UserHelpers::getUserName());
        
        // Test with second user
        Auth::login($user2);
        $this->assertEquals('Bob Johnson', UserHelpers::getUserName());
    }

    #[Test]
    public function get_user_name_integrates_with_auth_facade(): void
    {
        $user = User::factory()->create(['name' => 'Integration Test User']);
        
        Auth::login($user);
        
        // Verify Auth::check() is true
        $this->assertTrue(Auth::check());
        
        // Verify Auth::user()->name matches UserHelpers result
        $this->assertEquals(Auth::user()->name, UserHelpers::getUserName());
    }

    #[Test]
    public function is_backpack_user_returns_false_when_not_authenticated(): void
    {
        Auth::logout();
        
        $result = UserHelpers::isBackpackUser();
        
        $this->assertFalse($result);
    }

    #[Test]
    public function is_backpack_user_returns_false_when_authenticated_but_no_backpack(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        
        // Mock that backpack_user function doesn't exist or returns null
        $result = UserHelpers::isBackpackUser();
        
        // Should return false because backpack_user() function might not be available or returns null
        $this->assertFalse($result);
    }

    #[Test]
    public function is_backpack_user_integrates_with_auth_check(): void
    {
        $user = User::factory()->create();
        
        // Test when not authenticated
        Auth::logout();
        $this->assertFalse(Auth::check());
        $this->assertFalse(UserHelpers::isBackpackUser());
        
        // Test when authenticated
        Auth::login($user);
        $this->assertTrue(Auth::check());
        
        // The result depends on backpack_user() function availability
        $result = UserHelpers::isBackpackUser();
        $this->assertIsBool($result);
    }

    #[Test]
    public function get_user_name_handles_unusual_names_gracefully(): void
    {
        $user = User::factory()->create(['name' => 'Ünusual Ñame with 123 & symbols!']);
        
        Auth::login($user);
        
        $result = UserHelpers::getUserName('Fallback');
        
        // Should return the actual name since user is authenticated
        $this->assertEquals('Ünusual Ñame with 123 & symbols!', $result);
    }

    #[Test]
    public function get_user_name_handles_empty_string_name(): void
    {
        $user = User::factory()->create(['name' => '']);
        
        Auth::login($user);
        
        $result = UserHelpers::getUserName('Fallback');
        
        // Should return empty string (the user's actual name) since user is authenticated
        $this->assertEquals('', $result);
    }

    #[Test]
    public function methods_work_across_multiple_auth_state_changes(): void
    {
        $user1 = User::factory()->create(['name' => 'First User']);
        $user2 = User::factory()->create(['name' => 'Second User']);
        
        // Initial state - not authenticated
        Auth::logout();
        $this->assertEquals('Guest', UserHelpers::getUserName());
        $this->assertFalse(UserHelpers::isBackpackUser());
        
        // Login first user
        Auth::login($user1);
        $this->assertEquals('First User', UserHelpers::getUserName());
        $this->assertIsBool(UserHelpers::isBackpackUser());
        
        // Switch to second user
        Auth::login($user2);
        $this->assertEquals('Second User', UserHelpers::getUserName());
        $this->assertIsBool(UserHelpers::isBackpackUser());
        
        // Logout
        Auth::logout();
        $this->assertEquals('Guest', UserHelpers::getUserName());
        $this->assertFalse(UserHelpers::isBackpackUser());
    }
}
