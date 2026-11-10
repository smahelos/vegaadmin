<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
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
                'list' => Permission::PERMISSION_VIEW,
                'create' => Permission::PERMISSION_CREATE,
                'update' => Permission::PERMISSION_UPDATE,
                'delete' => Permission::PERMISSION_DELETE,
            ],
        );
    }
}
