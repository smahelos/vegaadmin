<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\CreateUser;
use App\Application\User\Commands\UpdateUser;
use App\Application\User\Queries\GetEntityUsage;
use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\UserRequest;
use App\Infrastructure\Forms\User\UserFormFields;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * User management controller
 *
 * @property-read CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use ActsAsAdmin;
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;
    use UpdateOperation;
    use UserFormFields;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {
        parent::__construct();
    }

    /**
     * Configure the CrudPanel object and check user permissions
     */
    public function setup(): void
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/user',
        );
        CRUD::setEntityNameStrings(
            __('users.entity.singular'),
            __('users.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::USER_VIEW,
                'show' => Permission::USER_VIEW,
                'create' => Permission::USER_CREATE,
                'update' => Permission::USER_UPDATE,
                'delete' => Permission::USER_DELETE,
            ],
        );
    }

    /**
     * Setup list view columns
     */
    public function setupListOperation(): void
    {
        $this->crud->addColumns(
            [
                [
                    'name' => 'name',
                    'label' => __('users.fields.name'),
                    'type' => 'text',
                ],
                [
                    'name' => 'email',
                    'label' => __('users.fields.email'),
                    'type' => 'email',
                ],
                [ // n-n relationship (with pivot table)
                    'label' => __('users.fields.roles'),
                    'type' => 'select_multiple',
                    'name' => 'roles',
                    'entity' => 'roles',
                    'attribute' => 'name',
                    'model' => config('permission.models.role'),
                ],
            ],
        );

        if (backpack_pro()) {
            // Role Filter
            $roleClass = config('permission.models.role');
            $roles = $roleClass::all();
            $roleNames = $roles->pluck('name', 'id');
            $this->crud->addFilter(
                [
                    'name' => 'role',
                    'type' => 'select2',
                    'label' => __('users.filters.role'),
                ],
                $roleNames->toArray(),
                function ($value): void {
                    $this->crud->addClause(
                        'whereHas',
                        'roles',
                        function ($query) use ($value): void {
                            $query->where(
                                'role_id',
                                '=',
                                $value,
                            );
                        },
                    );
                },
            );

            // Extra Permission Filter
            $permissionClass = config('permission.models.permission');
            $permissions = $permissionClass::all();
            $permissionNames = $permissions->pluck('name_with_guard', 'id');
            $this->crud->addFilter(
                [
                    'name' => 'permissions',
                    'type' => 'select2',
                    'label' => __('users.filters.permission'),
                ],
                $permissionNames->toArray(),
                function ($value): void {
                    $this->crud->addClause(
                        function ($query) use ($value): void {
                            $query->whereHas(
                                'permissions',
                                function ($q) use ($value): void {
                                    $q->where('id', $value);
                                },
                            );
                            $query->orWhereHas(
                                'roles.permissions',
                                function ($q) use ($value): void {
                                    $q->where('id', $value);
                                },
                            );
                        },
                    );
                },
            );
        }
    }

    /**
     * Setup create form fields
     */
    protected function setupCreateOperation(): void
    {
        $this->addUserFields();
        CRUD::setValidation(UserRequest::class);
    }

    /**
     * Setup update form fields
     */
    protected function setupUpdateOperation(): void
    {
        $this->addUserFields();
        CRUD::setValidation(UserRequest::class);
    }

    /**
     * Setup show operation
     */
    public function setupShowOperation(): void
    {
        $column = $this->crud->column('name');
        $column->label(
            __('users.fields.name'),
        );
        $column = $this->crud->column('email');
        $column->label(
            __('users.fields.email'),
        );
        $this->crud->column(
            [
                'label' => __('users.fields.roles_permissions'),
                'field_unique_name' => 'user_role_permission',
                'type' => 'checklist_dependency',
                'name' => 'roles_permissions',
                'subfields' => [
                    'primary' => [
                        'label' => __('users.fields.roles'),
                        'name' => 'roles',
                        'entity' => 'roles',
                        'entity_secondary' => 'permissions',
                        'attribute' => 'name',
                        'model' => config('permission.models.role'),
                    ],
                    'secondary' => [
                        'label' => __('users.fields.permissions'),
                        'name' => 'permissions',
                        'entity' => 'permissions',
                        'entity_primary' => 'roles',
                        'attribute' => 'name_with_guard',
                        'model' => config('permission.models.permission'),
                    ],
                ],
            ],
        );
        $entry = $this->currentEntry(User::class);
        $column = $this->crud->column('entity_usage');
        $column->type('entity_usage');
        $column->label(
            __('role_limits.usage.title'),
        );
        $column->value(
            $this->usages($entry->id),
        );
        $column = $this->crud->column('created_at');
        $column->label(
            __('users.fields.created_at'),
        );
        $column = $this->crud->column('updated_at');
        $column->label(
            __('users.fields.updated_at'),
        );
    }

    /**
     * The user's usage of every plan limit; null when they hold several plan roles, so the admin can fix them.
     *
     * @return list<EntityUsageDTO>|null
     */
    private function usages(int $userId): ?array
    {
        $usages = [];
        try {
            foreach (LimitedEntity::cases() as $entity) {
                $usages[] = $this->useCases->run(
                    $this->admin(),
                    new GetEntityUsage(
                        UserId::fromInt($userId),
                        $entity,
                    ),
                );
            }
        } catch (AmbiguousPlanRoleException) {
            return null;
        }

        return $usages;
    }

    public function store(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest(UserRequest::class);
        $user = $this->useCases->run(
            $this->admin(),
            new CreateUser(
                $request->writeData(),
                $request->roleIds(),
                $request->permissionIds(),
            ),
        );

        $alerts = Alert::success(
            __('backpack::crud.insert_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($user->id);
    }

    public function update(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest(UserRequest::class);
        $entry = $this->currentEntry(User::class);
        $this->useCases->run(
            $this->admin(),
            new UpdateUser(
                UserId::fromInt($entry->id),
                $request->writeData(),
                $request->roleIds(),
                $request->permissionIds(),
            ),
        );

        $alerts = Alert::success(
            __('backpack::crud.update_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    /**
     * Add user field definitions to the form
     */
    protected function addUserFields(): void
    {
        $this->crud->addFields(
            [
                [
                    'name' => 'name',
                    'label' => __('users.fields.name'),
                    'type' => 'text',
                ],
                [
                    'name' => 'email',
                    'label' => __('users.fields.email'),
                    'type' => 'email',
                ],
                [
                    'name' => 'password',
                    'label' => __('users.fields.password'),
                    'type' => 'password',
                ],
                [
                    'name' => 'password_confirmation',
                    'label' => __('users.fields.password_confirmation'),
                    'type' => 'password',
                ],
                [
                    'label' => __('users.fields.roles_permissions'),
                    'field_unique_name' => 'user_role_permission',
                    'type' => 'checklist_dependency',
                    'name' => 'roles,permissions',
                    'subfields' => [
                        'primary' => [
                            'label' => __('users.fields.roles'),
                            'name' => 'roles',
                            'entity' => 'roles',
                            'entity_secondary' => 'permissions',
                            'attribute' => 'name',
                            'model' => config('permission.models.role'),
                            'pivot' => true,
                            'number_columns' => 3,
                        ],
                        'secondary' => [
                            'label' => __('users.fields.permissions'),
                            'name' => 'permissions',
                            'entity' => 'permissions',
                            'entity_primary' => 'roles',
                            'attribute' => 'name_with_guard',
                            'model' => config('permission.models.permission'),
                            'pivot' => true,
                            'number_columns' => 3,
                        ],
                    ],
                ],
            ],
        );
    }
}
