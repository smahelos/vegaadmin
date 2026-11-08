<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\User\Mappers;

use App\Domain\User\DTO\RoleLimitDTO;
use App\Models\RoleLimit;

final class EloquentRoleLimitMapper
{
    /**
     * Expects the role's name selected as role_name.
     */
    public function toDTO(RoleLimit $model): RoleLimitDTO
    {
        return new RoleLimitDTO(
            role: $model->getAttribute('role_name'),
            invoices: $model->getAttribute('invoices'),
            expenses: $model->getAttribute('expenses'),
            businesses: $model->getAttribute('businesses'),
            contacts: $model->getAttribute('contacts'),
            products: $model->getAttribute('products'),
        );
    }
}
