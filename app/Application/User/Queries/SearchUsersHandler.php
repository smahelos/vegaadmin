<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\DTO\UserDTO;

final class SearchUsersHandler
{
    public function __construct(
        private readonly UserReadRepositoryInterface $users,
    ) {}

    /**
     * @return PaginatedResult<UserDTO>
     */
    public function handle(SearchUsers $query, AllOwnersScope $scope): PaginatedResult
    {
        return $this->users->search(
            $scope,
            $query->term,
            $query->page,
        );
    }
}
