<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\PermissionRequest;
use Backpack\PermissionManager\app\Http\Controllers\PermissionCrudController as PackagePermissionCrudController;

/**
 * PermissionManager's screen limited only by its configuration, gated here by operation and validated by our own
 * request, which keeps a name unique within its guard.
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

    public function setupCreateOperation(): void
    {
        parent::setupCreateOperation();
        $this->crud->setValidation(PermissionRequest::class);
    }

    public function setupUpdateOperation(): void
    {
        parent::setupUpdateOperation();
        $this->crud->setValidation(PermissionRequest::class);
    }
}
