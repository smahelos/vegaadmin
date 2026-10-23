<?php

namespace App\Domain\User\Contracts;

use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\User\ValueObjects\UserId;

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
     */
    public function findUserById(UserId $id): ?UserDTO;

    /**
     * Find user by email.
     */
    public function findUserByEmail(string $email): ?UserDTO;

    /**
     * Find user by associated supplier ID.
     */
    public function findBySupplierId(PartyId $partyId): ?UserDTO;

    /**
     * Find user by associated client ID.
     */
    public function findByClientId(PartyId $partyId): ?UserDTO;

    /**
     * Get user activity summary
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
     * @param  int  $perPage
     * @return PaginatedResult<UserDTO>
     */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult;

    /**
     * Check if email is unique
     */
    public function isEmailUnique(UserEmail $email, ?int $excludeUserId = null): bool;
}
