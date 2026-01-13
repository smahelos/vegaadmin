<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

/**
 * Service contract for user operations.
 * 
 * This interface allows Domain services to get user
 * without depending on Eloquent models or Infrastructure details.
 * Implementation will be in Infrastructure layer.
 */
interface UserReadRepositoryInterface
{
    /**
     * Find user by ID.
     * @param UserId $id
     * @return UserDTO|null
     */
    public function findUserById(UserId $id): ?UserDTO;

    /**
     * Find user by email.
     * @param string $email
     * @return UserDTO|null
     */
    public function findUserByEmail(string $email): ?UserDTO;

    /**
     * Find user by associated supplier ID.
     * @param PartyId $partyId
     * @return UserDTO|null
     */
    public function findBySupplierId(PartyId $partyId): ?UserDTO;

    /**
     * Find user by associated client ID.
     * @param PartyId $partyId
     * @return UserDTO|null
     */
    public function findByClientId(PartyId $partyId): ?UserDTO;

    /**
     * Get user activity summary
     *
     * @param UserId $userId
     * @return array
     */
    public function getActivitySummary(UserId $userId): array;

    /**
     * Get all users (admin scope) – controller should use this instead of direct static model call.
     *
     * @return array<int,UserDTO>
     */
    public function getAllUsers(): array;

    /**
     * Search users by name (admin scope).
     *
     * @param string|null $term
     * @param int $perPage
     * @return PaginatedResult<UserDTO>
     */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult;

    /**
     * Check if email is unique
     *
     * @param UserEmail $email
     * @param int|null $excludeUserId
     * @return bool
     */
    public function isEmailUnique(UserEmail $email, ?int $excludeUserId = null): bool;
}
