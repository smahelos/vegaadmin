<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\LimitMode;
use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\RoleLimitRequest;
use App\Infrastructure\Forms\User\RoleLimitFormFields;
use App\Models\RoleLimit;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * The limits of each web role (D-047); plain rows without domain rules, so Backpack stores them itself.
 */
final class RoleLimitCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use RoleLimitFormFields;
    use ShowOperation;
    use UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(RoleLimit::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/role-limit',
        );
        CRUD::setEntityNameStrings(
            __('role_limits.entity.singular'),
            __('role_limits.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::ROLE_LIMIT_VIEW,
                'show' => Permission::ROLE_LIMIT_VIEW,
                'create' => Permission::ROLE_LIMIT_CREATE,
                'update' => Permission::ROLE_LIMIT_UPDATE,
                'delete' => Permission::ROLE_LIMIT_DELETE,
            ],
        );
    }

    protected function setupListOperation(): void
    {
        $column = CRUD::column('role_id');
        $column->label(
            __('role_limits.fields.role'),
        );
        $column->type('closure');
        $column->function(
            fn (RoleLimit $entry): string => $entry->role->name,
        );

        foreach (LimitedEntity::cases() as $entity) {
            $column = CRUD::column($entity->value);
            $column->label(
                __('role_limits.fields.' . $entity->value),
            );
            $column->type('closure');
            $column->function(
                fn (RoleLimit $entry): string => $this->limitLabel(
                    $entry->limit($entity),
                ),
            );
        }
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(RoleLimitRequest::class);
        $this->crud->addFields(
            [
                $this->getRoleLimitRoleField(),
                ...$this->getRoleLimitFields(),
            ],
        );
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(RoleLimitRequest::class);
        $roleLimit = $this->currentEntry(RoleLimit::class);
        $this->crud->addFields(
            [
                $this->getRoleLimitRoleNameField($roleLimit),
                ...$this->getRoleLimitFields(),
            ],
        );
    }

    private function limitLabel(?int $limit): string
    {
        $mode = LimitMode::of($limit);
        if ($mode === LimitMode::LIMITED) {
            return (string) $limit;
        }

        return __('role_limits.statuses.' . $mode->value);
    }
}
