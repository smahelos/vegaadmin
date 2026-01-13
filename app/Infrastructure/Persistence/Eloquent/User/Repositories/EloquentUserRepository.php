<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Models\User;
use App\Domain\User\ValueObjects\UserEmail;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository
{
    public function findUserById(int $id): ?User
    {
        return User::find($id);
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findBySupplierId(int $supplierId): ?User
    {
        return User::where('supplier_id', $supplierId)->first();
    }

    public function findByClientId(int $clientId): ?User
    {
        return User::where('client_id', $clientId)->first();
    }
    
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function updateById(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) { return false; }
        return (bool)$user->update($data);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) { return false; }
        return (bool)$user->update($data);
    }

    public function getActivitySummary(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) { return []; }
        // Dummy implementation, replace with actual logic
        return [
            'last_login' => $user->last_login_at ?? null,
            'total_logins' => $user->login_count ?? 0,
            'account_created' => $user->created_at,
            'last_password_change' => $user->password_changed_at ?? null,
            'total_invoices' => $user->invoices()->count(),
            'is_active' => !$user->trashed(),
        ];
    }

    public function getAllUsers()
    {
        return User::all();
    }

    public function searchUsers(?string $term, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = User::query();
        if ($term) {
            $query->where('name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%');
        }
        return $query->paginate(perPage: $perPage, page: max(1, $page));
    }

    public function updatePassword(int $userId, string $password): bool
    {
        $user = User::find($userId);
        if (!$user) { return false; }
        $user->password = bcrypt($password);
        return (bool)$user->save();
    }

    public function softDeleteUser(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) { return false; }
        return $user->delete();
    }

    public function restoreUser(int $userId): bool
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) { return false; }
        return $user->restore();
    }

    public function changeEmail(int $userId, string $newEmail): bool
    {
        $user = User::find($userId);
        if (!$user) { return false; }
        $user->email = $newEmail;
        return (bool)$user->save();
    }

    /**
     * Check if email is unique
     *
     * @param UserEmail $email
     * @param int|null $excludeUserId
     * @return bool
     */
    public function isEmailUnique(UserEmail $email, ?int $excludeUserId = null): bool
    {
        $query = User::where('email', $email->getEmail());
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        return !$query->exists();
    }
}
