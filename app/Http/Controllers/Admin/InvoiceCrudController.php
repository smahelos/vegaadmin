<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\InvoiceRequest;
use App\Traits\InvoiceFormFields;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CountryService;
use Illuminate\Support\Facades\App;

/**
 * Invoice management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use InvoiceFormFields;

    /**
     * Configure the CrudPanel object
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
     * Setup list view columns
     * 
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('invoice_vs')->label('Invoice Number');
        CRUD::column('client_id');
        CRUD::column('payment_amount')->type('number');
        CRUD::column('issue_date')->type('date');
        CRUD::column('payment_status')->type('text');
        CRUD::column('due_in')->label('Due Days');
    }

    /**
     * Setup create form fields
     * 
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(InvoiceRequest::class);
        
        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        // Get clients, suppliers, payment methods and statuses for select fields
        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statuses = Status::pluck('name', 'id')->toArray();
        
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);

        // Add fields to CRUD form
        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryService::class)->getSimpleCountriesForSelect();
                
                CRUD::field($field['name'])
                    ->type('select_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryService::class)->getSimpleCountriesForSelect()
                    )
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 '
                    ]);
            } else if (
                $field['name'] === 'invoice_ks' ||
                $field['name'] === 'invoice_ss' ||
                $field['name'] === 'issue_date' ||
                $field['name'] === 'tax_point_date' ||
                $field['name'] === 'payment_method_id' ||
                $field['name'] === 'due_in'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ]);

            } else if (
                $field['name'] === 'payment_amount'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-5'
                    ]);

            } else if (
                $field['name'] === 'payment_currency'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-1'
                    ]);

            } else if (
                $field['name'] === 'name' ||
                $field['name'] === 'client_name' ||
                $field['name'] === 'account_number' ||
                $field['name'] === 'iban' ||
                $field['name'] === 'swift'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ]);

            } else if (
                $field['name'] === 'email' ||
                $field['name'] === 'phone' ||
                $field['name'] === 'street' ||
                $field['name'] === 'city' ||
                $field['name'] === 'zip' ||
                $field['name'] === 'country' ||
                $field['name'] === 'ico' ||
                $field['name'] === 'dic' ||
                $field['name'] === 'zip' ||
                $field['name'] === 'bank_code' ||
                $field['name'] === 'bank_name' ||
                $field['name'] === 'client_email' ||
                $field['name'] === 'client_phone' ||
                $field['name'] === 'client_street' ||
                $field['name'] === 'client_city' ||
                $field['name'] === 'client_zip' ||
                $field['name'] === 'client_country' ||
                $field['name'] === 'client_ico' ||
                $field['name'] === 'client_dic' ||
                $field['name'] === 'client_zip'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3'
                    ]);

            } else if ($field['type'] === 'select_from_array') {
                // Select from array field
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select_from_array')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->options($field['options'] ?? []);
                
                if (isset($field['options']) && is_array($field['options'])) {
                    if ($field['type'] === 'select_from_array') {
                        $fieldConfig->options($field['options']);
                    } else {
                        $fieldConfig->options(function() use ($field) {
                            return $field['options'];
                        });
                    }
                }

                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
                
                if (isset($field['default'])) {
                    $fieldConfig->default($field['default']);
                }
            } 
            else if ($field['type'] === 'select' && isset($field['entity'])) {
                if ($field['name'] === 'client_id' || $field['name'] === 'supplier_id') {
                    CRUD::field('separator_' . $field['name'])
                    ->type('custom_html')
                    ->value('<hr class="my-4" />');
                }
                
                // Entity select field
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select')
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->label($field['label']);
                    
                if (isset($field['entity']) && isset($field['model']) && isset($field['attribute'])) {
                    $fieldConfig->entity($field['entity'])
                            ->model($field['model'])
                            ->attribute($field['attribute']);
                }
                
                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
            } 
            else {
                // Other field types
                $fieldConfig = CRUD::field($field['name'])
                    ->type($field['type'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->label($field['label']);
                    
                if (isset($field['options']) && is_array($field['options'])) {
                    $fieldConfig->options(function() use ($field) {
                        return $field['options'];
                    });
                }
                
                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
                
                if (isset($field['default'])) {
                    $fieldConfig->default($field['default']);
                }
            }
        }
    }

    /**
     * Setup update form fields
     * 
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(InvoiceRequest::class);
        
        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statuses = Status::pluck('name', 'id')->toArray();
        
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);

        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryService::class)->getSimpleCountriesForSelect();
                
                CRUD::field($field['name'])
                    ->type('select_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryService::class)->getSimpleCountriesForSelect()
                    )
                    ->attributes(
                        [
                            'disabled' => 'disabled',
                            'class' => 'form-control bg-gray-800 text-gray-500',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 '
                    ]);
            } else if (
                $field['name'] === 'invoice_ks' ||
                $field['name'] === 'invoice_ss' ||
                $field['name'] === 'issue_date' ||
                $field['name'] === 'tax_point_date' ||
                $field['name'] === 'payment_method_id' ||
                $field['name'] === 'due_in'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ]);

            } else if (
                $field['name'] === 'payment_amount'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-5'
                    ]);

            } else if (
                $field['name'] === 'payment_currency'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-1'
                    ]);

            } else if (
                $field['name'] === 'name' ||
                $field['name'] === 'client_name' ||
                $field['name'] === 'account_number' ||
                $field['name'] === 'iban' ||
                $field['name'] === 'swift'
                ) {
                    CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'disabled' => 'disabled',
                            'class' => 'form-control bg-gray-800 text-gray-500',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ]);

            } else if (
                $field['name'] === 'invoice_text'
                ) {
                    CRUD::field($field['name'])
                    ->type('textarea')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-gray-400 text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-12'
                    ]);

            } else if (
                $field['name'] === 'email' ||
                $field['name'] === 'phone' ||
                $field['name'] === 'street' ||
                $field['name'] === 'city' ||
                $field['name'] === 'zip' ||
                $field['name'] === 'country' ||
                $field['name'] === 'ico' ||
                $field['name'] === 'dic' ||
                $field['name'] === 'zip' ||
                $field['name'] === 'bank_code' ||
                $field['name'] === 'bank_name' ||
                $field['name'] === 'client_email' ||
                $field['name'] === 'client_phone' ||
                $field['name'] === 'client_street' ||
                $field['name'] === 'client_city' ||
                $field['name'] === 'client_zip' ||
                $field['name'] === 'client_country' ||
                $field['name'] === 'client_ico' ||
                $field['name'] === 'client_dic' ||
                $field['name'] === 'client_zip'
                ) {
                    CRUD::field($field['name'])
                        ->type('text')
                        ->label($field['label'])
                        ->attributes(
                            [
                                'disabled' => 'disabled',
                                'class' => 'form-control bg-gray-800 text-gray-500',
                            ]
                        )
                        ->wrapper([
                            'class' => 'form-group col-md-3'
                        ]);

            } else if ($field['type'] === 'select_from_array') {
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select_from_array')
                    ->label($field['label'])
                    ->options($field['options'] ?? []);
                
                    if (isset($field['options']) && is_array($field['options'])) {
                        if ($field['type'] === 'select_from_array') {
                            $fieldConfig->options($field['options']);
                        } else {
                            $fieldConfig->options(function() use ($field) {
                                return $field['options'];
                            });
                        }
                    }

                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
                
                if (isset($field['default'])) {
                    $fieldConfig->default($field['default']);
                }
            } 
            else if ($field['type'] === 'select' && isset($field['entity'])) {
                if ($field['name'] === 'client_id' || $field['name'] === 'supplier_id') {
                    CRUD::field('separator_' . $field['name'])
                    ->type('custom_html')
                    ->value('<hr class="my-4" />');
                }
                
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select')
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ])
                    ->label($field['label']);
                    
                if (isset($field['entity']) && isset($field['model']) && isset($field['attribute'])) {
                    $fieldConfig->entity($field['entity'])
                            ->model($field['model'])
                            ->attribute($field['attribute']);
                }
                
                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
            } 
            else {
                $fieldConfig = CRUD::field($field['name'])
                    ->type($field['type'])
                    ->label($field['label']);
                    
                if (isset($field['options']) && is_array($field['options'])) {
                    $fieldConfig->options(function() use ($field) {
                        return $field['options'];
                    });
                }
                
                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
                
                if (isset($field['default'])) {
                    $fieldConfig->default($field['default']);
                }
            }
        }
    }

    /**
     * Create a new invoice with current user as owner
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function store()
    {
        $this->crud->getRequest()->request->add(['user_id' => backpack_user()->id]);
        
        try {
            $response = $this->traitStore();
            return $response;
        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage());
            throw $e;
        }
    }
}