<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\RoleLimitReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\User\Mappers\EloquentRoleLimitMapper;
use App\Models\RoleLimit;
use App\Models\User;

final class EloquentRoleLimitRepository implements RoleLimitReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentRoleLimitMapper $mapper,
    ) {}

    public function listForUser(UserId $user): array
    {
        $morphClass = (new User)->getMorphClass();
        $query = RoleLimit::query();
        $query->select(['role_limits.*', 'roles.name as role_name']);
        $query->join(
            'roles',
            'roles.id',
            '=',
            'role_limits.role_id',
        );
        $query->join(
            'model_has_roles',
            'model_has_roles.role_id',
            '=',
            'roles.id',
        );
        $query->where('roles.guard_name', 'web');
        $query->where('model_has_roles.model_type', $morphClass);
        $query->where('model_has_roles.model_id', $user->toInt());
        $models = $query->get();

        $limits = [];
        foreach ($models as $model) {
            $limits[] = $this->mapper->toDTO($model);
        }

        return $limits;
    }
}
