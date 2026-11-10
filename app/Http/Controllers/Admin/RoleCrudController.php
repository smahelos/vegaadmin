<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use Backpack\PermissionManager\app\Http\Controllers\RoleCrudController as PackageRoleCrudController;

/**
 * PermissionManager's screen limited only by its configuration, gated here by operation. Permissions show their guard,
 * because each guard holds its own permission of the same name.
 */
final class RoleCrudController extends PackageRoleCrudController
{
    use GatesCrudOperations;

    public function setup(): void
    {
        parent::setup();

        $this->denyOperationsWithout(
            [
                'list' => Permission::ROLE_VIEW,
                'create' => Permission::ROLE_CREATE,
                'update' => Permission::ROLE_UPDATE,
                'delete' => Permission::ROLE_DELETE,
            ],
        );
    }

    public function setupListOperation(): void
    {
        parent::setupListOperation();
        $this->crud->modifyColumn(
            'permissions',
            ['attribute' => 'name_with_guard'],
        );
    }

    public function setupCreateOperation(): void
    {
        parent::setupCreateOperation();
        $this->showPermissionGuards();
    }

    public function setupUpdateOperation(): void
    {
        parent::setupUpdateOperation();
        $this->showPermissionGuards();
    }

    private function showPermissionGuards(): void
    {
        $this->crud->modifyField(
            'permissions',
            ['attribute' => 'name_with_guard'],
        );
    }
}
