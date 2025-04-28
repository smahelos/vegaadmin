<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Traits\ClientFormFields;

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
    use ClientFormFields;

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
        CRUD::column('user_id')
            ->type('integer')
            ->label('Owner')
            ->entity('user')
            ->attribute('name');

        CRUD::column('shortcut')->type('text');
        CRUD::column('name')->type('text')->after('shortcut');
        CRUD::column('phone')->type('text');
        CRUD::column('email')->type('email');
        CRUD::column('country')->type('text');
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
}