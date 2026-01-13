<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use App\Infrastructure\Forms\Party\ClientFormFields;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

/**
 * Client management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ClientCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use ClientFormFields; // Usage recording handled by generic observer

    /**
     * Configure the CrudPanel object
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client');
        CRUD::setEntityNameStrings('client', 'clients');
    }

    /**
     * Setup list view columns
     *
     * @return void
     */
    protected function setupListOperation()
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label('User')
            ->values(
                User::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'user_id', $value);
            });

        CRUD::column('user_id')
            ->type('integer')
            ->label('Owner')
            ->entity('user')
            ->attribute('name')
            ->value(function($entry) {
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
     *
     * @return void
     */
    protected function setupCreateOperation()
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
     *
     * @return void
     */
    protected function setupUpdateOperation()
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
            if (!$request->has('user_id') || empty($request->input('user_id'))) {
                $request->merge(['user_id' => backpack_user()->id]);
            }
        }
    }

    /**
     * Override store to ensure user_id is set before persisting.
     * (Usage recording moved to model observer & event system.)
     */
    public function store()
    {
        $this->beforeEntityStore();
        return parent::store();
    }

    /**
     * Returns client data as JSON by ID
     *
     * @param int $id Client ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch($id)
    {
        $client = \App\Models\Client::findOrFail($id);
        return response()->json($client);
    }

    // Usage recording is now handled by ClientObserver (model created event)
}
