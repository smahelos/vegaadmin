<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;
use App\Infrastructure\Persistence\Eloquent\User\Mappers\EloquentUserMapper as Mapper;
use App\Models\User;
use DateTimeImmutable;

class EloquentUserDtoRepository implements UserReadRepositoryInterface, UserWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentUserRepository $userRepository,
        private readonly Mapper $mapper,
    ) {}

    public function findUserById(UserId $id): ?UserDTO
    {
        $user = $this->userRepository->findUserById($id->toInt());

        return $user ? $this->mapper->toDto($user) : null;
    }

    public function create(UserWriteData $data): UserDTO
    {
        $model = $this->userRepository->create($data->toArray());

        return $this->mapper->toDto($model);
    }

    public function updateProfile(UserId $id, UserWriteData $data): bool
    {
        return $this->userRepository->updateById($id->toInt(), $data->toArray());
    }

    /**
     * Update user password
     *
     * @throws \Exception
     */
    public function updatePassword(UserId $userId, UserPassword $password): bool
    {
        return $this->userRepository->updatePassword($userId->toInt(), $password->__toString());
    }

    public function getAllUsers(): array
    {
        $users = $this->userRepository->getAllUsers();

        return $users->toArray();
    }

    public function searchUsers(?string $term, PageRequest $page): PaginatedResult
    {
        $paginator = $this->userRepository->searchUsers(
            $term,
            $page->perPage(),
            $page->page(),
        );
        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->mapper->toDto($model);
        }

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function lockForUpdate(UserId $id): void
    {
        $query = User::query();
        $query->whereKey($id->toInt());
        $query->lockForUpdate();
        $query->firstOrFail();
    }

    public function planRoleAssignedAt(UserId $id): ?DateTimeImmutable
    {
        $query = User::query();
        $user = $query->findOrFail($id->toInt());

        return $user->getAttribute('plan_role_assigned_at');
    }

    public function assignRole(UserId $id, string $role): void
    {
        $query = User::query();
        $user = $query->findOrFail($id->toInt());
        $user->assignRole($role);
    }
}
