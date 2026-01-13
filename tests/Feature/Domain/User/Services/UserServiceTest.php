<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Services\UserService;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\User\ValueObjects\UserPassword;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class UserServiceTest extends TestCase
{
    use RefreshDatabaseWithData;

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
    public function creates_user_with_valid_data(): void
    {
        $uniqueEmail = 'test_' . uniqid() . '@example.com';
    $email = UserEmail::fromString($uniqueEmail);
    // UserService::create expects array values converted via UserWriteData; pass raw string password
    $password = 'SecurePassword123';
        $data = [
            'name' => 'Test User',
            'email' => (string) $email,
            'password' => $password
        ];
        $user = $this->userService->create($data);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertEquals($uniqueEmail, $user->email);
        $this->assertEquals('Test User', $user->name);
        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'name' => 'Test User'
        ]);
    }

    #[Test]
    public function finds_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'find@example.com'
        ]);

    $email = UserEmail::fromString('find@example.com');
    $foundUser = $this->userService->findUserByEmail((string) $email);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    #[Test]
    public function returns_null_for_non_existent_email(): void
    {
    $email = UserEmail::fromString('nonexistent@example.com');
    $foundUser = $this->userService->findUserByEmail((string) $email);

        $this->assertNull($foundUser);
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
    public function checks_email_uniqueness(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

    $existingEmail = UserEmail::fromString('existing@example.com');
    $newEmail = UserEmail::fromString('new@example.com');

    $this->assertFalse($this->userService->isEmailUnique($existingEmail));
    $this->assertTrue($this->userService->isEmailUnique($newEmail));
    }
    
    #[Test]
    public function soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $result = $this->userService->softDeleteUser($user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function restores_soft_deleted_user(): void
    {
        $user = User::factory()->create();
        $user->delete(); // Soft delete

        $result = $this->userService->restoreUser($user->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);
    }

    #[Test]
    public function updates_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name'
        ]);

        $profileData = [
            'name' => 'Updated Name'
        ];

        $result = $this->userService->updateProfile($user->id, $profileData);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
    }

    #[Test]
    public function changes_user_email_with_verification(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
    $newEmail = UserEmail::fromString('new@example.com');

    $result = $this->userService->changeEmail($user->id, (string) $newEmail);

        $this->assertTrue($result);
        $user->refresh();
        // In basic implementation, email should be updated directly
        // In production, this might involve email verification process
        $this->assertEquals('new@example.com', $user->email);
    }

    #[Test]
    public function gets_user_activity_summary(): void
    {
        $user = User::factory()->create();

        $summary = $this->userService->getActivitySummary($user->id);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('last_login', $summary);
        $this->assertArrayHasKey('total_logins', $summary);
        $this->assertArrayHasKey('account_created', $summary);
        
        // Verify structure of activity summary
        $this->assertNull($summary['last_login']); // New user has no login yet
        $this->assertEquals(0, $summary['total_logins']); // New user has 0 logins
        $this->assertNotNull($summary['account_created']); // Account creation date should exist
    }

    #[Test]
    public function user_creation_validates_email_uniqueness(): void
    {
        // Create first user
        User::factory()->create(['email' => 'duplicate@example.com']);

    $email = UserEmail::fromString('duplicate@example.com');
    $password = 'SecurePassword123';

        // Expect database unique constraint exception (Model layer handles validation)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $data = [
            'name' => 'Duplicate User',
            'email' => (string) $email,
            'password' => $password
        ];
        $this->userService->create($data);
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
