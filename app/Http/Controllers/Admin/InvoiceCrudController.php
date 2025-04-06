<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\InvoiceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Class InvoiceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings('invoice', 'invoices');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(InvoiceRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        // CRUD::field('currency')->type('select2_from_array')->options([
        //     'CZK' => 'CZK',
        //     'EUR' => 'EUR',
        //     'USD' => 'USD',
        // ])->allows_multiple_selection(); // set the "allows_multiple_selection" attribute to true

        CRUD::field([
            'label' => "Client",
            'type' => 'select',
            'name' => 'client_id', // the method that defines the relationship in your Model
            'entity' => 'clients', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model' => 'App\Models\Client',
            'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
            'subfields'   => [
                [
                    'name' => 'ico',
                    'type' => 'text',
                    'wrapper' => [
                        'class' => 'form-group col-md-3',
                    ],
                ],
                [
                    'name' => 'dic',
                    'type' => 'text',
                    'wrapper' => [
                        'class' => 'form-group col-md-9',
                    ],
                ],
            ],
        ]);

        CRUD::field('payment_amount')->type('number'); // set the "type" attribute to "number"
        CRUD::field('payment_amount')->after('currency'); // move a field after a different field
        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function store()
    {
        Log::debug('Invoice data', request()->all());
        $response = $this->traitStore();
        // $this->crud->hasAccessOrFail('create');
        // $this->crud->setRequest($this->request);
        // $this->crud->setSaveAction();
        // $this->crud->setOperationSetting('entry', $this->crud->create());
        // $this->crud->setOperationSetting('response', $this->crud->getRequest());
        // $this->crud->setOperationSetting('redirect_location', url($this->crud->route));
        return $response;
    }
}
