<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

interface UserReadRepositoryInterface
{
    /**
     * The account the scope belongs to; null when it was deleted.
     */
    public function findAccount(ReadScope $scope): ?UserDTO;

    /**
     * Accounts whose name or email contains the term, every account without one.
     *
     * @return PaginatedResult<UserDTO>
     */
    public function search(
        AllOwnersScope $scope,
        ?string $term,
        PageRequest $page,
    ): PaginatedResult;

    public function findIdByEmail(AllOwnersScope $scope, string $email): ?UserId;

    /**
     * When the user's current plan role was assigned; null when no assignment was recorded.
     */
    public function planRoleAssignedAt(UserId $id): ?DateTimeImmutable;
}
