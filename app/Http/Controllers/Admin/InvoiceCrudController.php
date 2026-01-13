<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\InvoiceRequest;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use Illuminate\Support\Facades\App;

/**
 * Invoice management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use InvoiceFormFields; // Usage recording handled by generic observer

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

        CRUD::filter('payment_status_id')
            ->type('select2')
            ->label('Payment Status')
            ->values(
                Status::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'payment_status_id', $value);
            });

        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'tax_point_date',
            'label' => 'Tax Point Date',
        ], false, function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'tax_point_date', '>=', $dates->from);
            CRUD::addClause('where', 'tax_point_date', '<=', $dates->to);
        });

        CRUD::column('invoice_vs')->label('Invoice Number');
        CRUD::column('payment_amount')->type('number');
        CRUD::column('issue_date')->type('date');
        CRUD::column('tax_point_date')->type('date');

        CRUD::column('payment_status')
            ->type('text')
            ->label('Payment Status')
            ->value(function($entry) {
                return $entry->paymentStatus ? $entry->paymentStatus->name : 'N/A';
            });

        CRUD::column('due_in')->label('Due Days');
    }

    /**
     * Setup create form fields
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(\App\Http\Requests\Admin\InvoiceRequest::class);

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        // Get clients, suppliers, payment methods and statuses for select fields
        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statusCategoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;
        $statuses = Status::where('category_id', $statusCategoryId)->pluck('name', 'id')->toArray();
        Log::info('InvoiceCrudController: setupCreateOperation - statuses: ' . json_encode($statuses));
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);

        // Add fields to CRUD form
        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect();

                CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect()
                    )
                    ->attributes(
                        [
                            'class' => 'form-control bg-white text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 '
                    ]);
            } else if ($field['name'] === 'payment_status_id') {
                CRUD::field('payment_status_id')
                    ->label(trans('admin.invoices.status'))
                    ->type('relationship')
                    ->model(Status::class)
                    ->attribute('name')
                    ->options(function ($query) {
                        // Get the category ID for 'expense'
                        $categoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;

                        // Return only active statuses for the category
                        return $query->where('category_id', $categoryId)->where('is_active', 1)->get();
                    })
                    ->wrapper(['class' => 'form-group col-md-12']);

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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3'
                    ]);

            } else if ($field['type'] === 'select_from_array') {
                // Select from array field
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-white text-gray-900',
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
                    ->type('select2')
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ])
                    ->attributes(
                        [
                            'class' => 'form-control bg-white text-gray-900',
                        ]
                    )
                    ->label($field['label']);

                if (isset($field['entity']) && isset($field['model']) && isset($field['attribute'])) {
                    $fieldConfig->entity($field['entity'])
                            ->model($field['model'])
                            ->attribute($field['attribute']);
                }

                if ($field['name'] === 'payment_status_id') {
                    $fieldConfig->options((function ($query) use ($statusCategoryId) {
                        return $query->orderBy('name', 'ASC')->where('category_id', $statusCategoryId)->get();
                    }));
                }

                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
            } else if ($field['type'] === 'file' && $field['name'] === 'invoice_logo') {
                // Special case for invoice logo upload
                CRUD::addField([
                    'name' => 'invoice_logo',
                    'label' => __('invoices.fields.invoice_logo'),
                    'type' => 'upload',
                    'upload' => true,
                    'disk' => 'public',
                    'prefix' => 'invoices/logos/',
                ]);
            }
            else {
                // Other field types
                $fieldConfig = CRUD::field($field['name'])
                    ->type($field['type'])
                    ->attributes(
                        [
                            'class' => 'form-control bg-white text-gray-900',
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

    protected function beforeEntityStore(): void
    {
        // Ensure the new invoice belongs to current backpack user
        if (backpack_user()) {
            $this->crud->getRequest()->merge(['user_id' => backpack_user()->id]);
        }
    }

    /**
     * Override store only to set user before persisting. Usage recorded via observer.
     */
    public function store()
    {
        $this->beforeEntityStore();
        return parent::store();
    }

    /**
     * Setup update form fields
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(\App\Http\Requests\Admin\InvoiceRequest::class);

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statusCategoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;
        $statuses = Status::where('category_id', $statusCategoryId)->pluck('name', 'id')->toArray();

        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);

        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect();

                CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect()
                    )
                    ->attributes(
                        [
                            'disabled' => 'disabled',
                            'class' => 'form-control bg-gray-800 text-gray-300',
                        ]
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 '
                    ]);
            } else if ($field['name'] === 'payment_status_id') {
                CRUD::field('payment_status_id')
                    ->label(trans('admin.invoices.status'))
                    ->type('relationship')
                    ->model(Status::class)
                    ->attribute('name')
                    ->options(function ($query) {
                        // Get the category ID for 'invoice'
                        $categoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;

                        // Return only active statuses for the category
                        return $query->where('category_id', $categoryId)->where('is_active', 1)->get();
                    })
                    ->wrapper(['class' => 'form-group col-md-6']);

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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                            'class' => 'form-control bg-gray-800 text-gray-300',
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
                            'class' => 'form-control bg-white text-gray-900',
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
                                'class' => 'form-control bg-gray-800 text-gray-300',
                            ]
                        )
                        ->wrapper([
                            'class' => 'form-group col-md-3'
                        ]);

            } else if ($field['type'] === 'select_from_array') {
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2_from_array')
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
                    ->type('select2')
                    ->wrapper([
                        'class' => 'form-group col-md-6'
                    ])
                    ->label($field['label']);

                if (isset($field['entity']) && isset($field['model']) && isset($field['attribute'])) {
                    $fieldConfig->entity($field['entity'])
                            ->model($field['model'])
                            ->attribute($field['attribute']);
                }

                if ($field['name'] === 'payment_status_id') {
                    $fieldConfig->options((function ($query) use ($statusCategoryId) {
                        return $query->orderBy('name', 'ASC')->where('category_id', $statusCategoryId)->get();
                    }));
                }

                if (isset($field['required']) && $field['required']) {
                    $fieldConfig->required(true);
                }
            } else if ($field['type'] === 'file' && $field['name'] === 'invoice_logo') {
                // Special case for invoice logo upload
                CRUD::addField([
                    'name' => 'invoice_logo',
                    'label' => __('invoices.fields.invoice_logo'),
                    'type' => 'upload',
                    'upload' => true,
                    'disk' => 'public',
                    'prefix' => 'invoices/logos/',
                ]);
            } else {
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

    // Usage recording handled by InvoiceObserver
}
