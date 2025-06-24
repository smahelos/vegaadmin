<?php

namespace App\Services;

use App\Contracts\UserServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    /**
     * Update user profile
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateProfile(User $user, array $data): User
    {
        try {
            $user->update($data);
            return $user->fresh();
        } catch (\Exception $e) {
            throw new \Exception('Error updating profile: ' . $e->getMessage());
        }
    }

    /**
     * Update user password
     *
     * @param User $user
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function updatePassword(User $user, string $password): bool
    {
        try {
            $user->password = Hash::make($password);
            return $user->save();
        } catch (\Exception $e) {
            throw new \Exception('Error updating password: ' . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findUserById(int $id): User
    {
        return User::findOrFail($id);
    }
}
