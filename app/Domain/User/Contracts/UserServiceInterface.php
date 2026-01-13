<?php

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;

interface UserServiceInterface
{
    /**
     * Find user by ID.
     * @param int $id
     * @return UserDTO|null
     */
    public function findUserById(int $id): ?UserDTO;

    /**
     * Find user by email.
     * @param string $email
     * @return UserDTO|null
     */
    public function findUserByEmail(string $email): ?UserDTO;

    /**
     * Find user by associated supplier ID.
     * @param int $partyId
     * @return UserDTO|null
     */
    public function findBySupplierId(int $partyId): ?UserDTO;

    /**
     * Find user by associated client ID.
     * @param int $partyId
     * @return UserDTO|null
     */
    public function findByClientId(int $partyId): ?UserDTO;

    /**
     * Get user activity summary
     *
     * @param int $userId
     * @return array
     */
    public function getActivitySummary(int $userId): array;

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
     * @return PaginatedResult
     */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult;
    /**
     * Create new user from write data.
     * @param array $data
     * @return UserDTO
     */
    public function create(array $data): UserDTO;

    /**
     * Update user by ID with write data.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateById(int $id, array $data): bool;

    /**
     * Update profile with write data.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProfile(int $id, array $data): bool;

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function updatePassword(int $userId, string $password): bool;

    /**
     * Soft delete user
     *
     * @param int $userId
     * @return bool
     */
    public function softDeleteUser(int $userId): bool;

    /**
     * Restore soft deleted user
     *
     * @param int $userId
     * @return bool
     */
    public function restoreUser(int $userId): bool;

    /**
     * Change user email
     *
     * @param int $userId
     * @param string $newEmail
     * @return bool
     */
    public function changeEmail(int $userId, string $newEmail): bool;
}
