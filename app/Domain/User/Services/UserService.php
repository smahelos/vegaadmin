<?php

namespace App\Domain\User\Services;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserServiceInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;

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

    public function create(array $data): UserDTO
    {
        return $this->transactionBoundary->transaction(fn () => $this->userWriteRepository->create(UserWriteData::fromArray($data)),
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

    /** {@inheritDoc} */
    public function getAllUsers(): array
    {
        return $this->userReadRepository->getAllUsers();
    }

    /** {@inheritDoc} */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult
    {
        return $this->userReadRepository->searchUsers($term, $page);
    }
}
