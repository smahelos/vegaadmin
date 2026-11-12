<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;
use App\Infrastructure\Persistence\Eloquent\User\Mappers\EloquentUserMapper;
use App\Models\Role;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentUserRepository implements UserReadRepositoryInterface, UserWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentUserMapper $mapper,
    ) {}

    public function findAccount(ReadScope $scope): ?UserDTO
    {
        return $this->findUserById($scope->owner);
    }

    public function findUserById(UserId $id): ?UserDTO
    {
        $query = User::query();
        $user = $query->find($id->toInt());

        return $user === null ? null : $this->mapper->toDto($user);
    }

    public function search(
        AllOwnersScope $scope,
        ?string $term,
        PageRequest $page,
    ): PaginatedResult {
        $query = User::query();
        if ($term !== null && $term !== '') {
            $query->where(
                function (Builder $matches) use ($term): void {
                    $matches->where(
                        'name',
                        'like',
                        '%' . $term . '%',
                    );
                    $matches->orWhere(
                        'email',
                        'like',
                        '%' . $term . '%',
                    );
                },
            );
        }
        $query->orderBy('name');
        $found = $query->paginate(
            perPage: $page->perPage(),
            page: $page->page(),
        );
        $items = [];
        foreach ($found->items() as $user) {
            $items[] = $this->mapper->toDto($user);
        }

        return new PaginatedResult(
            $items,
            $found->total(),
            $found->perPage(),
            $found->currentPage(),
        );
    }

    public function findIdByEmail(AllOwnersScope $scope, string $email): ?UserId
    {
        $query = User::query();
        $query->where('email', $email);
        $id = $query->value('id');

        return $id === null ? null : UserId::fromInt((int) $id);
    }

    public function newAccount(UserWriteData $data): UserDTO
    {
        $query = User::query();
        $user = $query->create(
            $this->mapper->toAttributes($data),
        );

        return $this->mapper->toDto($user);
    }

    public function updateAccount(WriteScope $scope, UserWriteData $data): void
    {
        $query = User::query();
        $user = $query->findOrFail($scope->owner->toInt());
        $user->update(
            $this->mapper->toAttributes($data),
        );
    }

    public function changePassword(WriteScope $scope, UserPassword $password): void
    {
        $query = User::query();
        $query->whereKey($scope->owner->toInt());
        // Already hashed by UserPassword; the model's hashed cast stores a hash as it is
        $query->update(['password' => (string) $password]);
    }

    public function lockForUpdate(UserId $id): void
    {
        $query = User::query();
        $query->whereKey($id->toInt());
        $query->lockForUpdate();
        $query->firstOrFail();
    }

    public function planRoleAssignedAt(UserId $id): ?DateTimeImmutable
    {
        $query = User::query();
        $user = $query->findOrFail($id->toInt());

        return $user->getAttribute('plan_role_assigned_at');
    }

    public function assignRole(UserId $id, string $role): void
    {
        $query = User::query();
        $user = $query->findOrFail($id->toInt());
        // By the role itself, not its name: a name alone resolves in the user's default guard only
        $roleQuery = Role::query();
        $roleQuery->where('name', $role);
        $user->assignRole(
            $roleQuery->sole(),
        );
    }

    public function syncRolesAndPermissions(
        UserId $id,
        array $roleIds,
        array $permissionIds,
    ): void {
        $query = User::query();
        $user = $query->findOrFail($id->toInt());
        $roles = $user->roles();
        $roles->sync($roleIds);
        $permissions = $user->permissions();
        $permissions->sync($permissionIds);
    }

    public function recordPlanRoleAssignment(UserId $id, DateTimeImmutable $assigned): void
    {
        $query = User::query();
        $query->whereKey($id->toInt());
        $query->update(['plan_role_assigned_at' => $assigned]);
    }
}
