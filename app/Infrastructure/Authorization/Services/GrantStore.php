<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization\Services;

use App\Domain\User\DTO\RoleLimitDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Authorization\Grants;
use App\Infrastructure\Persistence\Eloquent\User\Mappers\EloquentRoleLimitMapper;
use App\Models\RoleLimit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The grants read in this request, one per user and guard (D-049). Registered as scoped and emptied when a new
 * request is bound, so no request sees roles read before it started; the role writer forgets the user it changed.
 */
final class GrantStore
{
    /** @var array<int, array<string, Grants>> */
    private array $grants = [];

    /** @var array<int, array<string, list<RoleLimitDTO>>> */
    private array $limits = [];

    /** @var array<int, true> users whose role change is not committed yet */
    private array $uncommitted = [];

    public function __construct(
        private readonly EloquentRoleLimitMapper $limitMapper,
    ) {}

    public function for(UserId $user, string $guard): Grants
    {
        $id = $user->value;
        if (isset($this->uncommitted[$id])) {
            return $this->load($id, $guard);
        }

        return $this->grants[$id][$guard] ??= $this->load($id, $guard);
    }

    /**
     * The role_limits rows of the user's roles on the guard, read on the first creation or restore that asks.
     *
     * @return list<RoleLimitDTO>
     */
    public function limits(UserId $user, string $guard): array
    {
        $grants = $this->for($user, $guard);
        $id = $user->value;
        if (isset($this->uncommitted[$id])) {
            return $this->loadLimits($grants->roles);
        }

        return $this->limits[$id][$guard] ??= $this->loadLimits($grants->roles);
    }

    public function forget(UserId $user): void
    {
        $id = $user->value;
        unset($this->grants[$id], $this->limits[$id]);
        // Neither a rollback nor a failed commit reliably reports itself, so roles read before the commit are not
        // kept: until it commits, each question reads the user's roles again, which then are the stored ones
        $this->uncommitted[$id] = true;
        DB::afterCommit(
            function () use ($id): void {
                unset($this->uncommitted[$id]);
            },
        );
    }

    /**
     * Called when a new request is bound (SharedServiceProvider). The store is emptied rather than replaced in the
     * container, because a controller Laravel keeps on its route from an earlier request still holds this instance.
     */
    public function clear(): void
    {
        $this->grants = [];
        $this->limits = [];
        $this->uncommitted = [];
    }

    /**
     * The same set checkPermissionTo($name, $guard) accepts: held directly or through a role, only on that guard.
     */
    private function load(int $id, string $guard): Grants
    {
        $query = User::query();
        $user = $query->find($id);
        if (!$user instanceof User) {
            return new Grants([], []);
        }

        $permissions = [];
        foreach ($user->getAllPermissions() as $permission) {
            if ($permission->guard_name === $guard) {
                $permissions[$permission->name] = $permission->name;
            }
        }

        $roles = [];
        foreach ($user->roles as $role) {
            if ($role->guard_name === $guard) {
                $roles[] = $role->id;
            }
        }

        return new Grants(
            array_values($permissions),
            $roles,
        );
    }

    /**
     * @param  list<int>  $roles
     * @return list<RoleLimitDTO>
     */
    private function loadLimits(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        $query = RoleLimit::query();
        $query->select(['role_limits.*', 'roles.name as role_name']);
        $query->join(
            'roles',
            'roles.id',
            '=',
            'role_limits.role_id',
        );
        $query->whereIn('role_limits.role_id', $roles);
        $models = $query->get();

        $limits = [];
        foreach ($models as $model) {
            $limits[] = $this->limitMapper->toDTO($model);
        }

        return $limits;
    }
}
