<?php

namespace App\Application\User\Contracts;

use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserPassword;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserApplicationServiceInterface
{
    /**
     * @throws ModelNotFoundException
     */
    public function findUserById(int $id): ?UserDTO;

    /**
     * @return Collection<int,User>
     */
    public function getAllUsers();

    public function searchUsers(
        ?string $term,
        int $perPage = 10,
        int $page = 1,
    ): LengthAwarePaginator;

    /**
     * Update user profile
     *
     * @param  User  $user
     *
     * @throws \Exception
     */
    public function updateProfile(int $userId, array $data): bool;

    /**
     * Update user password
     *
     * @param  User  $user
     *
     * @throws \Exception
     */
    public function updatePassword(int $userId, UserPassword $password): bool;
}
