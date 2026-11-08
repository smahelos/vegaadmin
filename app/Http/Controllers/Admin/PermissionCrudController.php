<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Backpack\PermissionManager\app\Http\Controllers\PermissionCrudController as PackagePermissionCrudController;

/**
 * PermissionManager's screen limited only by its configuration, gated here by operation.
 */
final class PermissionCrudController extends PackagePermissionCrudController
{
    use GatesCrudOperations;

    public function setup(): void
    {
        parent::setup();

        $this->denyOperationsWithout(
            [
                'list' => 'can_view_permission',
                'create' => 'can_create_permission',
                'update' => 'can_update_permission',
                'delete' => 'can_delete_permission',
            ],
        );
    }
}
