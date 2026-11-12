<?php

declare(strict_types=1);

namespace Tests\Feature\Application\User\Queries;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Queries\SearchUsers;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class SearchUsersTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    #[Test]
    public function an_admin_finds_any_account_by_name_or_email(): void
    {
        $admin = $this->backendUser(Permission::USER_VIEW);
        $marker = uniqid();
        $userFactory = User::factory();
        $byName = $userFactory->create(['name' => 'Owner ' . $marker]);
        $byEmail = $userFactory->create(['email' => $marker . '@billex.test']);
        $userFactory->create();

        $found = $this->search(
            Actor::admin(
                $this->idOf($admin),
            ),
            $marker,
        );

        $ids = array_map(
            fn (UserDTO $user): int => $user->id,
            $found->items(),
        );
        sort($ids);
        $this->assertSame([$byName->id, $byEmail->id], $ids);
    }

    #[Test]
    public function a_frontend_user_cannot_search_accounts(): void
    {
        // user.view exists only in backpack; a user reaches only their own account
        $user = $this->frontendUser();

        $this->expectException(PermissionDeniedException::class);

        $this->search(
            Actor::user(
                $this->idOf($user),
            ),
            null,
        );
    }

    #[Test]
    public function an_admin_without_user_view_cannot_search(): void
    {
        $admin = $this->backendUser(Permission::USER_CREATE);

        $this->expectException(PermissionDeniedException::class);

        $this->search(
            Actor::admin(
                $this->idOf($admin),
            ),
            null,
        );
    }

    /**
     * @return PaginatedResult<UserDTO>
     */
    private function search(Actor $actor, ?string $term): PaginatedResult
    {
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            $actor,
            new SearchUsers(
                $term,
                new PageRequest(1, 10),
            ),
        );
    }
}
