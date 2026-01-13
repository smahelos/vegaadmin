<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\User\Mappers\EloquentUserMapper as Mapper;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserPassword;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

class EloquentUserDtoRepository implements UserReadRepositoryInterface, UserWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentUserRepository $userRepository,
        private readonly Mapper $mapper
    ) {
    }

    public function findUserById(UserId $id): ?UserDTO
    {
        $user = $this->userRepository->findUserById($id->toInt());

        return $user ? $this->mapper->toDto($user) : null;
    }

    public function findUserByEmail(string $email): ?UserDTO
    {
        $user = $this->userRepository->findUserByEmail($email);

        return $user ? $this->mapper->toDto($user) : null;
    }

    public function findBySupplierId(PartyId $partyId): ?UserDTO
    {
        $user = $this->userRepository->findBySupplierId($partyId->toInt());

        return $user ? $this->mapper->toDto($user) : null;
    }

    public function findByClientId(PartyId $partyId): ?UserDTO
    {
        $user = $this->userRepository->findByClientId($partyId->toInt());

        return $user ? $this->mapper->toDto($user) : null;
    }

    public function create(UserWriteData $data): UserDTO
    {
        $model = $this->userRepository->create($data->toArray());
        return $this->mapper->toDto($model);
    }

    public function updateById(UserId $id, UserWriteData $data): bool
    {
        return $this->userRepository->updateById($id->toInt(), $data->toArray());
    }

    public function updateProfile(UserId $id, UserWriteData $data): bool
    {
        return $this->userRepository->updateById($id->toInt(), $data->toArray());
    }

    /**
     * Update user password
     *
     * @param UserId $userId
     * @param UserPassword $password
     * @return bool
     * @throws \Exception
     */
    public function updatePassword(UserId $userId, UserPassword $password): bool
    {
        return $this->userRepository->updatePassword($userId->toInt(), $password->__toString());
    }

    /**
     * Soft delete user
     *
     * @param UserId $userId
     * @return bool
     */
    public function softDeleteUser(UserId $userId): bool
    {
        return $this->userRepository->softDeleteUser($userId->toInt());
    }

    /**
     * Restore soft deleted user
     *
     * @param UserId $userId
     * @return bool
     */
    public function restoreUser(UserId $userId): bool
    {
        return $this->userRepository->restoreUser($userId->toInt());
    }

    /**
     * Change user email
     *
     * @param UserId $userId
     * @param UserEmail $newEmail
     * @return bool
     */
    public function changeEmail(UserId $userId, UserEmail $newEmail): bool
    {
        return $this->userRepository->changeEmail($userId->toInt(), $newEmail->__toString());
    }

    public function getActivitySummary(UserId $userId): array
    {
        return $this->userRepository->getActivitySummary($userId->toInt());
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->getAllUsers()->toArray();
    }

    public function searchUsers(?string $term, PageRequest $page): PaginatedResult
    {
        $paginator = $this->userRepository->searchUsers($term, $page->perPage(), $page->page());
        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->mapper->toDto($model);
        }

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            hasMore: $paginator->currentPage() < $paginator->lastPage()
        );
    }

    /**
     * Check if email is unique
     *
     * @param UserEmail $email
     * @param int|null $excludeUserId
     * @return bool
     */
    public function isEmailUnique(UserEmail $email, ?int $excludeUserId = null): bool
    {
        return $this->userRepository->isEmailUnique($email, $excludeUserId);
    }
}
