<?php

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;

interface UserServiceInterface
{
    /**
     * Find user by ID.
     */
    public function findUserById(int $id): ?UserDTO;

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
     */
    public function searchUsers(?string $term, PageRequest $page): PaginatedResult;

    /**
     * Create new user from write data.
     */
    public function create(array $data): UserDTO;

    /**
     * Update profile with write data.
     */
    public function updateProfile(int $id, array $data): bool;

    /**
     * Update user password
     *
     * @throws \Exception
     */
    public function updatePassword(int $userId, string $password): bool;
}
