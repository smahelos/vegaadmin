<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
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
     * Locks the user's row until the surrounding transaction ends, so that checks of the user's capacity and the
     * writes they allow run one request at a time.
     */
    public function lockForUpdate(UserId $id): void;

    public function assignRole(UserId $id, string $role): void;
}
