<?php

namespace App\Application\User\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;
use App\Domain\User\ValueObjects\UserPassword;
use App\Domain\User\DTO\UserDTO;

interface UserApplicationServiceInterface
{
    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findUserById(int $id): ?UserDTO;

    /**
     * @return \Illuminate\Support\Collection<int,\App\Models\User>
     */
    public function getAllUsers();

    public function searchUsers(?string $term, int $perPage = 10): LengthAwarePaginator;

    /**
     * Update user profile
     *
     * @param User $user
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateProfile(int $userId, array $data): bool;

    /**
     * Update user password
     *
     * @param User $user
     * @param UserPassword $password
     * @return bool
     * @throws \Exception
     */
    public function updatePassword(int $userId, UserPassword $password): bool;
}

