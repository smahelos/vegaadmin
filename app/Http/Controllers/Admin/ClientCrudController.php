<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\ClientRequest;
use App\Infrastructure\Forms\Party\ClientFormFields;
use App\Models\Client;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Client management controller
 *
 * @property-read CrudPanel $crud
 */
class ClientCrudController extends CrudController
{
    use ClientFormFields;
    use CreateOperation {
        store as traitStore;
    }
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation; // Usage recording handled by generic observer

    /**
     * Configure the CrudPanel object
     */
    public function setup(): void
    {
        CRUD::setModel(Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client');
        CRUD::setEntityNameStrings('client', 'clients');
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label('User')
            ->values(
                User::pluck('name', 'id')->toArray(),
            )
            ->whenActive(function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            });

        CRUD::column('user_id')
            ->type('integer')
            ->label('Owner')
            ->entity('user')
            ->attribute('name')
            ->value(function ($entry) {
                return $entry->user_id ? $entry->user->name : 'N/A';
            });

        CRUD::column('shortcut')->type('text');
        CRUD::column('name')->type('text')->after('shortcut');
        CRUD::column('phone')->type('text');
        CRUD::column('email')->type('email');
        CRUD::column('country')->type('text');
        CRUD::column('is_default')->type('text');
    }

    /**
     * Setup create form fields
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ClientRequest::class);
        CRUD::setFromDb();

        CRUD::field('user_id')
            ->type('hidden')
            ->label('Owner')
            ->entity('user')
            ->attribute('name')
            ->options(function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            });

        CRUD::column('shortcut')->type('text');
        CRUD::column('name')->type('text')->after('shortcut');
        CRUD::column('phone')->type('text');
        CRUD::column('email')->type('email');
        CRUD::column('country')->type('text');
    }

    /**
     * Setup update form fields
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Ensure user_id is always set to the current backpack user before storing.
     */
    protected function beforeEntityStore(): void
    {
        if (backpack_user()) {
            $request = $this->crud->getRequest();
            if (! $request->has('user_id') || empty($request->input('user_id'))) {
                $request->merge(['user_id' => backpack_user()->id]);
            }
        }
    }

    /**
     * Override store to ensure user_id is set before persisting.
     * (Usage recording moved to model observer & event system.)
     */
    public function store(): JsonResponse|RedirectResponse
    {
        $this->beforeEntityStore();

        return $this->traitStore();
    }

    /**
     * Returns client data as JSON by ID
     *
     * @param  int  $id  Client ID
     */
    public function fetch(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        return response()->json($client);
    }

    // Usage recording is now handled by ClientObserver (model created event)
}
