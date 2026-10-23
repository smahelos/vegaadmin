<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserEmail;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;

/**
 * Service contract for user permission operations.
 *
 * This interface allows Domain services to check user permissions
 * without depending on Eloquent models or Infrastructure details.
 * Implementation will be in Infrastructure layer.
 */
interface UserWriteRepositoryInterface
{
    /**
     * Create new user from write data.
     */
    public function create(UserWriteData $data): UserDTO;

    /**
     * Update user by ID with write data.
     */
    public function updateById(UserId $id, UserWriteData $data): bool;

    /**
     * Update profile with write data.
     */
    public function updateProfile(UserId $id, UserWriteData $data): bool;

    /**
     * Update user password
     *
     * @throws \Exception
     */
    public function updatePassword(UserId $userId, UserPassword $password): bool;

    /**
     * Soft delete user
     */
    public function softDeleteUser(UserId $userId): bool;

    /**
     * Restore soft deleted user
     */
    public function restoreUser(UserId $userId): bool;

    /**
     * Change user email
     */
    public function changeEmail(UserId $userId, UserEmail $newEmail): bool;
}
