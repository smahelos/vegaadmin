<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository
{
    public function findUserById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function updateById(int $id, array $data): bool
    {
        $user = User::find($id);
        if (! $user) {
            return false;
        }

        return (bool) $user->update($data);
    }

    public function getAllUsers(): Collection
    {
        return User::all();
    }

    public function searchUsers(
        ?string $term,
        int $perPage = 10,
        int $page = 1,
    ): LengthAwarePaginator {
        $query = User::query();
        if ($term) {
            $query->where(
                'name',
                'like',
                '%' . $term . '%',
            )
                ->orWhere(
                    'email',
                    'like',
                    '%' . $term . '%',
                );
        }

        return $query->paginate(perPage: $perPage, page: max(1, $page));
    }

    public function updatePassword(int $userId, string $password): bool
    {
        $user = User::find($userId);
        if (! $user) {
            return false;
        }
        $user->password = bcrypt($password);

        return (bool) $user->save();
    }
}
