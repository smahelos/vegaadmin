<?php

namespace App\Contracts;

use App\Models\User;

interface UserServiceInterface
{
    /**
     * Update user profile
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Update user password
     *
     * @param User $user
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function updatePassword(User $user, string $password): bool;

    /**
     * Get user by ID
     *
     * @param int $id
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findUserById(int $id): User;
}
