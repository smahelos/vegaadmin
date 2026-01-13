<?php

namespace App\Infrastructure\Authorization\Services;

use App\Application\User\Contracts\CrudAccessServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Infrastructure service that configures Backpack CRUD access using Spatie permissions.
 */
class CrudAccessService implements CrudAccessServiceInterface
{
    public function __construct(private readonly UserAuthorizationAdapterInterface $auth)
    {
    }

    public function configureCrudAccess(CrudPanel $crud, int $userId): void
    {
        // Default deny
        $crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);

        $table = CRUD::getModel()->getTable();
        // Derive entity name from table (e.g. users -> user, subscriptions -> subscription)
        $entity = \Illuminate\Support\Str::singular($table);

        $isAdmin = $this->auth->isBackpackAdmin($userId);
        $canView = $this->auth->hasBackpackViewPermission($userId, $entity);
        $canEdit = $this->auth->hasBackpackPermission($userId, 'can_create_edit_' . $entity);

        // Admin or edit permission: allow full CRUD including list/show
        if ($isAdmin || $canEdit) {
            $crud->allowAccess(['list', 'show', 'create', 'update', 'delete']);
            return;
        }

        // View-only permission: allow list and show
        if ($canView) {
            $crud->allowAccess(['list', 'show']);
        }
    }
}
