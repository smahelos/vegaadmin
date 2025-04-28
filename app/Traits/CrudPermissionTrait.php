<?php
namespace App\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Configure Backpack CRUD access using Spatie Permissions
 */
trait CrudPermissionTrait
{
    // Available operations for CRUD controller
    public array $operations = ['list', 'show', 'create', 'update', 'delete'];

    /**
     * Set CRUD access based on permissions assigned to the authenticated user
     *
     * @return void
     */
    public function setAccessUsingPermissions()
    {
        // Default - deny all access
        $this->crud->denyAccess($this->operations);

        // Get context
        $table = CRUD::getModel()->getTable();
        $user = request()->user();

        // Exit if no authenticated user
        if (!$user) {
            return;
        }

        // Enable operations based on permissions
        foreach ([
            // Format: permission level => [allowed crud operations]
            'can_view_user' => ['list', 'show'],
            'can_create_edit_user' => ['list', 'show', 'create', 'update', 'delete'],
        ] as $level => $operations) {
            if ($user->can("$table.$level")) {
                $this->crud->allowAccess($operations);
            }
        }
    }
}