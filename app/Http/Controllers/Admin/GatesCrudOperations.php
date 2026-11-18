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
    use ActsAsAdmin;

    /**
     * Denies every operation, then allows those whose permission the signed-in admin holds, so an operation
     * missing from the map stays denied.
     *
     * @param  array<string, Permission>  $permissions  permission by Backpack operation
     */
    protected function allowHeldOperations(array $permissions): void
    {
        CRUD::denyAllAccess();
        foreach ($permissions as $operation => $permission) {
            if ($this->adminHolds($permission)) {
                CRUD::allowAccess($operation);
            }
        }
    }
}
