<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Infrastructure\Forms\Payment\PaymentMethodFormFields;
use App\Models\PaymentMethod;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Payment method management controller
 *
 * @property-read CrudPanel $crud
 */
class PaymentMethodCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use PaymentMethodFormFields;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(PaymentMethod::class);
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

        CRUD::addFields($this->getPaymentMethodFields());
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
