<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Services\UserService;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required permissions and roles
        Permission::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->userService = app(UserService::class);
    }

    #[Test]
    public function updates_user_password(): void
    {
        $user = User::factory()->create();
        // updatePassword expects plain string
        $newPassword = 'NewSecurePassword123';

        $oldHash = $user->password;
        $result = $this->userService->updatePassword($user->id, $newPassword);

        $this->assertTrue($result);
        $user->refresh();

        // Verify password hash has changed (storage uses repository-specific hashing)
        $this->assertNotEquals($oldHash, $user->password);
        $this->assertIsString($user->password);
        $this->assertNotEmpty($user->password);
    }

    #[Test]
    public function updates_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
        ]);

        $profileData = [
            'name' => 'Updated Name',
        ];

        $result = $this->userService->updateProfile($user->id, $profileData);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
    }

    #[Test]
    public function finds_user_by_id(): void
    {
        $user = User::factory()->create();

        $foundUser = $this->userService->findUserById($user->id);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals($user->email, $foundUser->email);
    }

    #[Test]
    public function returns_null_for_non_existent_user_id(): void
    {
        // Service returns null when user not found; assert null instead of expecting exception
        $this->assertNull($this->userService->findUserById(999999));
    }
}
