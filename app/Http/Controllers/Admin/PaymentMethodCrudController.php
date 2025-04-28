<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PaymentMethodRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Payment method management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PaymentMethodCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PaymentMethod::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/payment-method');
        CRUD::setEntityNameStrings('payment method', 'payment methods');
    }

    /**
     * Setup list view columns
     * 
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb();
    }

    /**
     * Setup create form fields
     * 
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PaymentMethodRequest::class);
        CRUD::setFromDb();
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
}