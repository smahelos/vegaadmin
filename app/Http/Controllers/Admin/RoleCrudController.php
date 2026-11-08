<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Backpack\PermissionManager\app\Http\Controllers\RoleCrudController as PackageRoleCrudController;

/**
 * PermissionManager's screen limited only by its configuration, gated here by operation.
 */
final class RoleCrudController extends PackageRoleCrudController
{
    use GatesCrudOperations;

    public function setup(): void
    {
        parent::setup();

        $this->denyOperationsWithout(
            [
                'list' => 'can_view_role',
                'create' => 'can_create_role',
                'update' => 'can_update_role',
                'delete' => 'can_delete_role',
            ],
        );
    }
}
