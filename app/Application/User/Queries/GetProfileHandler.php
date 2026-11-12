<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\Exceptions\UserNotFoundException;

final class GetProfileHandler
{
    public function __construct(
        private readonly UserReadRepositoryInterface $users,
    ) {}

    /**
     * @throws UserNotFoundException
     */
    public function handle(GetProfile $query, ReadScope $scope): UserDTO
    {
        return $this->users->findAccount($scope)
            ?? throw new UserNotFoundException((string) $scope->owner->toInt());
    }
}
