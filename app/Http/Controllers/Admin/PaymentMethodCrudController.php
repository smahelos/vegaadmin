<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PaymentMethodRequest;
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
        CRUD::setEntityNameStrings(__('payment_methods.entity.singular'), __('payment_methods.entity.plural'));
    }

    /**
     * Setup list view columns
     * 
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('payment_methods.fields.name'));
        CRUD::column('slug')->label(__('payment_methods.fields.slug'));
        CRUD::column('country')->label(__('payment_methods.fields.country'));
        CRUD::column('currency')->label(__('payment_methods.fields.currency'));
        CRUD::column('icon')->label(__('payment_methods.fields.icon'));
    }

    /**
     * Setup create form fields
     * 
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PaymentMethodRequest::class);

        CRUD::field('name')->label(__('payment_methods.fields.name'));
        CRUD::field('slug')->label(__('payment_methods.fields.slug'));
        CRUD::field('country')->label(__('payment_methods.fields.country'));
        CRUD::field('currency')->label(__('payment_methods.fields.currency'));
        CRUD::field('icon')->label(__('payment_methods.fields.icon'));
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