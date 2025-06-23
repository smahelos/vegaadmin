<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManageUserPermissionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create some test permissions and roles
        Permission::firstOrCreate(['name' => 'test_permission', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'test_role', 'guard_name' => 'web']);
    }

    #[Test]
    public function command_lists_roles(): void
    {
        $exitCode = Artisan::call('user:permissions', [
            'action' => 'list-roles'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('test_role', $output);
    }

    #[Test]
    public function command_lists_permissions(): void
    {
        $exitCode = Artisan::call('user:permissions', [
            'action' => 'list-permissions'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('test_permission', $output);
    }

    #[Test]
    public function command_shows_user_permissions(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $exitCode = Artisan::call('user:permissions', [
            'action' => 'show-user',
            '--user' => $user->email
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString($user->email, $output);
    }

    #[Test]
    public function command_assigns_role_to_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $exitCode = Artisan::call('user:permissions', [
            'action' => 'assign-role',
            '--user' => $user->email,
            '--role' => 'test_role'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        // Verify role was assigned
        $user->refresh();
        $this->assertTrue($user->hasRole('test_role'));
    }

    #[Test]
    public function command_removes_role_from_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        // First assign the role
        $user->assignRole('test_role');

        $exitCode = Artisan::call('user:permissions', [
            'action' => 'remove-role',
            '--user' => $user->email,
            '--role' => 'test_role'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        // Verify role was removed
        $user->refresh();
        $this->assertFalse($user->hasRole('test_role'));
    }

    #[Test]
    public function command_handles_user_by_id(): void
    {
        $user = User::factory()->create();

        $exitCode = Artisan::call('user:permissions', [
            'action' => 'show-user',
            '--user' => $user->id
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_handles_invalid_user(): void
    {
        $exitCode = Artisan::call('user:permissions', [
            'action' => 'show-user',
            '--user' => 'nonexistent@example.com'
        ]);
        
        // Command should return error exit code when user is not found
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('User not found', $output);
    }

    #[Test]
    public function command_handles_invalid_role(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $exitCode = Artisan::call('user:permissions', [
            'action' => 'assign-role',
            '--user' => $user->email,
            '--role' => 'nonexistent_role'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_handles_invalid_action(): void
    {
        $exitCode = Artisan::call('user:permissions', [
            'action' => 'invalid-action'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('user:permissions', [
            'action' => 'list-roles'
        ]);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_validates_required_options(): void
    {
        // Test assign-role without required options
        $exitCode = Artisan::call('user:permissions', [
            'action' => 'assign-role'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }
}
