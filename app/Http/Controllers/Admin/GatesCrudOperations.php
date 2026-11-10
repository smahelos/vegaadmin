<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Gates each Backpack operation by its own permission.
 */
trait GatesCrudOperations
{
    /**
     * Denies every operation whose permission the signed-in admin lacks.
     *
     * @param  array<string, Permission>  $permissions  permission by Backpack operation
     */
    protected function denyOperationsWithout(array $permissions): void
    {
        $user = backpack_user();
        $denied = [];
        foreach ($permissions as $operation => $permission) {
            if ($user?->can($permission) !== true) {
                $denied[] = $operation;
            }
        }

        CRUD::denyAccess($denied);
    }
}
