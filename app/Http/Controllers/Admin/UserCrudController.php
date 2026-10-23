<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Application\User\Contracts\CrudAccessServiceInterface;
use App\Infrastructure\Forms\User\UserFormFields;
use Illuminate\Support\Facades\Hash;
use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest as StoreRequest;
use Backpack\PermissionManager\app\Http\Requests\UserUpdateCrudRequest as UpdateRequest;

/**
 * User management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    // Access control is configured via application CrudAccessServiceInterface in setup()
    use UserFormFields;

    /**
     * Configure the CrudPanel object and check user permissions
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings(__('users.entity.singular'), __('users.entity.plural'));

        // Configure access using centralized service
        $userId = optional(backpack_user())->id ?? 0;
        app(\App\Application\User\Contracts\CrudAccessServiceInterface::class)
            ->configureCrudAccess($this->crud, (int) $userId);
    }

    /**
     * Setup list view columns
     *
     * @return void
     */
    public function setupListOperation()
    {
        $this->crud->addColumns([
            [
                'name'  => 'name',
                'label' => __('users.fields.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => __('users.fields.email'),
                'type'  => 'email',
            ],
            [ // n-n relationship (with pivot table)
                'label'     => __('users.fields.roles'),
                'type'      => 'select_multiple',
                'name'      => 'roles',
                'entity'    => 'roles',
                'attribute' => 'name',
                'model'     => config('permission.models.role'),
            ],
        ]);

        if (backpack_pro()) {
            // Role Filter
            $this->crud->addFilter(
                [
                    'name'  => 'role',
                    'type'  => 'select2',
                    'label' => __('users.filters.role'),
                ],
                config('permission.models.role')::all()->pluck('name', 'id')->toArray(),
                function ($value) {
                    $this->crud->addClause('whereHas', 'roles', function ($query) use ($value) {
                        $query->where('role_id', '=', $value);
                    });
                }
            );

            // Extra Permission Filter
            $this->crud->addFilter(
                [
                    'name'  => 'permissions',
                    'type'  => 'select2',
                    'label' => __('users.filters.permission'),
                ],
                config('permission.models.permission')::all()->pluck('name', 'id')->toArray(),
                function ($value) {
                    $this->crud->addClause(function ($query) use ($value) {
                        $query->whereHas('permissions', function ($q) use ($value) {
                            $q->where('id', $value);
                        })->orWhereHas('roles.permissions', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    });
                }
            );
        }
    }

    /**
     * Setup create form fields
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->addUserFields();
        CRUD::setValidation(StoreRequest::class);
    }

    /**
     * Setup update form fields
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->addUserFields();
        CRUD::setValidation(UpdateRequest::class);
    }

    /**
     * Setup show operation
     *
     * @return void
     */
    public function setupShowOperation()
    {
        $this->crud->column('name')->label(__('users.fields.name'));
        $this->crud->column('email')->label(__('users.fields.email'));
        $this->crud->column([
            'label'             => __('users.fields.roles_permissions'),
            'field_unique_name' => 'user_role_permission',
            'type'              => 'checklist_dependency',
            'name'              => 'roles_permissions',
            'subfields'         => [
                'primary' => [
                    'label'            => __('users.fields.roles'),
                    'name'             => 'roles',
                    'entity'           => 'roles',
                    'entity_secondary' => 'permissions',
                    'attribute'        => 'name',
                    'model'            => config('permission.models.role'),
                ],
                'secondary' => [
                    'label'            => __('users.fields.permissions'),
                    'name'             => 'permissions',
                    'entity'           => 'permissions',
                    'entity_primary'   => 'roles',
                    'attribute'        => 'name',
                    'model'            => config('permission.models.permission'),
                ],
            ],
        ]);
        $this->crud->column('created_at')->label(__('users.fields.created_at'));
        $this->crud->column('updated_at')->label(__('users.fields.updated_at'));
    }

    /**
     * Custom store method for password hashing
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation();

        return $this->traitStore();
    }

    /**
     * Custom update method for password hashing
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation();

        return $this->traitUpdate();
    }

    /**
     * Process password field - hash if provided
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Request
     */
    protected function handlePasswordInput($request)
    {
        // Remove password if empty
        if ($request->has('password') && $request->input('password') === null) {
            $request->request->remove('password');
            $request->request->remove('password_confirmation');
        }

        // Hash password if provided
        if ($request->has('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        }

        return $request;
    }

    /**
     * Add user field definitions to the form
     *
     * @return void
     */
    protected function addUserFields()
    {
        $this->crud->addFields([
            [
                'name'  => 'name',
                'label' => __('users.fields.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => __('users.fields.email'),
                'type'  => 'email',
            ],
            [
                'name'  => 'password',
                'label' => __('users.fields.password'),
                'type'  => 'password',
            ],
            [
                'name'  => 'password_confirmation',
                'label' => __('users.fields.password_confirmation'),
                'type'  => 'password',
            ],
            [
                'label'             => __('users.fields.roles_permissions'),
                'field_unique_name' => 'user_role_permission',
                'type'              => 'checklist_dependency',
                'name'              => 'roles,permissions',
                'subfields'         => [
                    'primary' => [
                        'label'            => __('users.fields.roles'),
                        'name'             => 'roles',
                        'entity'           => 'roles',
                        'entity_secondary' => 'permissions',
                        'attribute'        => 'name',
                        'model'            => config('permission.models.role'),
                        'pivot'            => true,
                        'number_columns'   => 3,
                    ],
                    'secondary' => [
                        'label'          => __('users.fields.permissions'),
                        'name'           => 'permissions',
                        'entity'         => 'permissions',
                        'entity_primary' => 'roles',
                        'attribute'      => 'name',
                        'model'          => config('permission.models.permission'),
                        'pivot'          => true,
                        'number_columns' => 3,
                    ],
                ],
            ],
        ]);
    }
}
