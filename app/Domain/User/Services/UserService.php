<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\UserServiceInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\User\ValueObjects\UserPassword;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

class UserService implements UserServiceInterface
{
    /**
     * User read repository (DTO) instance
     */
    private readonly UserReadRepositoryInterface $userReadRepository;

    /**
     * User write repository (DTO) instance
     */
    private readonly UserWriteRepositoryInterface $userWriteRepository;

    /**
     * Transaction boundary instance
     */
    private readonly TransactionBoundaryInterface $transactionBoundary;

    /**
     * Constructor
     */
    public function __construct(
        UserReadRepositoryInterface $userReadRepository,
        UserWriteRepositoryInterface $userWriteRepository,
        TransactionBoundaryInterface $transactionBoundary,
        // ProductCreationValidator $validator
    ) {
        $this->userReadRepository = $userReadRepository;
        $this->userWriteRepository = $userWriteRepository;
        $this->transactionBoundary = $transactionBoundary;
        // $this->validator = $validator;
    }
    

    public function findUserById(int $id): ?UserDTO
    {
        return $this->userReadRepository->findUserById(UserId::fromInt($id));
    }

    public function findUserByEmail(string $email): ?UserDTO
    {
        return $this->userReadRepository->findUserByEmail($email);
    }

    public function findBySupplierId(int $partyId): ?UserDTO
    {
        return $this->userReadRepository->findBySupplierId(PartyId::fromInt($partyId));
    }

    public function findByClientId(int $partyId): ?UserDTO
    {
        return $this->userReadRepository->findByClientId(PartyId::fromInt($partyId));
    }

    public function create(array $data): UserDTO
    {
        return $this->transactionBoundary->transaction(fn() =>
            $this->userWriteRepository->create(UserWriteData::fromArray($data))
        );
    }

    public function updateById(int $id, array $data): bool
    {
        return $this->transactionBoundary->transaction(fn() =>
            $this->userWriteRepository->updateById(UserId::fromInt($id), UserWriteData::fromArray($data))
        );
    }

    public function updateProfile(int $userId, array $data): bool
    {
        return $this->userWriteRepository->updateProfile(UserId::fromInt($userId), UserWriteData::fromArray($data));
    }

    public function updatePassword(int $userId, string $password): bool
    {
        return $this->userWriteRepository->updatePassword(UserId::fromInt($userId), UserPassword::fromPlainText($password));
    }

    /**
     * Soft delete user
     *
     * @param int $userId
     * @return bool
     */
    public function softDeleteUser(int $userId): bool
    {
        return $this->userWriteRepository->softDeleteUser(UserId::fromInt($userId));
    }

    /**
     * Restore soft deleted user
     *
     * @param int $userId
     * @return bool
     */
    public function restoreUser(int $userId): bool
    {
        return $this->userWriteRepository->restoreUser(UserId::fromInt($userId));
    }

    /**
     * Change user email
     *
     * @param int $userId
     * @param string $newEmail
     * @return bool
     */
    public function changeEmail(int $userId, string $newEmail): bool
    {
        return $this->userWriteRepository->changeEmail(UserId::fromInt($userId), UserEmail::fromString($newEmail));
    }

    /**
     * Get user activity summary
     *
     * @param int $userId
     * @return array
     */
    public function getActivitySummary(int $userId): array
    {
        return $this->userReadRepository->getActivitySummary(UserId::fromInt($userId));
    }

    /** @inheritDoc */
    public function getAllUsers(): array
    {
        return $this->userReadRepository->getAllUsers();
    }

    /** @inheritDoc */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult
    {
        return $this->userReadRepository->searchUsers($term, $page);
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
        return $this->userReadRepository->isEmailUnique($email, $excludeUserId);
    }
}
