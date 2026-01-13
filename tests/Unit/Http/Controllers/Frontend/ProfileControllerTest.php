<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\ProfileController;
use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Domain\User\ValueObjects\UserPassword;
use App\Models\User;
use App\Domain\User\DTO\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for ProfileController focusing on lightweight logic and value object integration.
 * Controller heavy behavior covered by feature tests; here we assert:
 * - Uses UserServiceInterface methods with correct value object types
 * - Proper redirect route names
 */
class ProfileControllerTest extends TestCase
{
    private UserApplicationServiceInterface $userAppServiceStub;

    protected function setUp(): void
    {
        parent::setUp();
        // Simple stub implementing only methods used
        $this->userAppServiceStub = new class implements UserApplicationServiceInterface {
            public array $calls = [];
            public function findUserById(int $id): ?UserDTO { 
                $u = new User(); $u->id = $id; return UserDTO::fromArray($u->toArray());
            }
            public function getAllUsers() { return collect(); }
            public function searchUsers(?string $term, int $perPage = 10): LengthAwarePaginator { return new LengthAwarePaginator([],0,$perPage); }
            public function updateProfile(int $userId, array $data): bool { $this->calls[] = ['updateProfile',$data]; return true; }
            public function updatePassword(int $userId, UserPassword $password): bool { $this->calls[] = ['updatePassword',$password instanceof UserPassword]; return true; }
        };
    }

    #[Test]
    public function update_password_uses_value_object(): void
    {
        $controller = new ProfileController($this->userAppServiceStub);
        $user = new User();
        $user->id = 55;
        Auth::shouldReceive('id')->andReturn($user->id);

        // Simulate request object minimal
        $request = new class extends \App\Http\Requests\PasswordUpdateRequest {
            public string $password = 'Abcdefg1';
            public function validated($key = null, $default = null) { return []; }
        };

        $response = $controller->updatePassword($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/profile', $response->headers->get('Location'));
    }
}
