<?php

namespace App\Http\Controllers\Admin;

use App\Application\User\Services\EntityUsageApplicationService;
use App\Application\User\Services\PlanRoleApplicationService;
use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\ValueObjects\UserId;
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
use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest as StoreRequest;
use Backpack\PermissionManager\app\Http\Requests\UserUpdateCrudRequest as UpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * User management controller
 *
 * @property-read CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use CreateOperation {
        store as traitStore;
    }
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;
    use UpdateOperation {
        update as traitUpdate;
    }
    use UserFormFields;

    public function __construct(
        private readonly EntityUsageApplicationService $entityUsages,
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
        CRUD::setValidation(StoreRequest::class);
    }

    /**
     * Setup update form fields
     */
    protected function setupUpdateOperation(): void
    {
        $this->addUserFields();
        CRUD::setValidation(UpdateRequest::class);
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
        try {
            return $this->entityUsages->usages(
                UserId::fromInt($userId),
            );
        } catch (AmbiguousPlanRoleException) {
            return null;
        }
    }

    /**
     * Custom store method for password hashing
     */
    public function store(PlanRoleApplicationService $planRoles): RedirectResponse
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest(
            $this->handlePasswordInput($this->crud->getRequest()),
        );
        $this->crud->unsetValidation();
        $response = $this->traitStore();
        $planRoles->recordIfChanged(
            UserId::fromInt((int) $this->crud->entry->id),
            [],
        );

        return $response;
    }

    /**
     * Custom update method for password hashing
     */
    public function update(PlanRoleApplicationService $planRoles): RedirectResponse
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest(
            $this->handlePasswordInput($this->crud->getRequest()),
        );
        $this->crud->unsetValidation();
        $userId = UserId::fromInt((int) $this->crud->getCurrentEntryId());
        $before = $planRoles->planRoles($userId);
        $response = $this->traitUpdate();
        $planRoles->recordIfChanged($userId, $before);

        return $response;
    }

    /**
     * Process password field - hash if provided
     */
    protected function handlePasswordInput(Request $request): Request
    {
        // Remove password if empty
        if ($request->has('password') && $request->input('password') === null) {
            $request->request->remove('password');
            $request->request->remove('password_confirmation');
        }

        // Hash password if provided
        if ($request->has('password')) {
            $request->request->set(
                'password',
                Hash::make(
                    $request->input('password'),
                ),
            );
        }

        return $request;
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
