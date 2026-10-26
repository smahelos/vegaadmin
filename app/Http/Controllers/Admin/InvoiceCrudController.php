<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Supplier;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Invoice management controller
 *
 * @property-read CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use CreateOperation {
        store as traitStore;
    }
    use DeleteOperation;
    use InvoiceFormFields;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation; // Usage recording handled by generic observer

    /**
     * Fields rendered with their type from InvoiceFormFields (date, select, select_from_array).
     */
    private const PAYMENT_FIELDS = [
        'issue_date',
        'tax_point_date',
        'payment_method_id',
        'due_in',
        'payment_currency',
    ];

    /**
     * Configure the CrudPanel object
     */
    public function setup(): void
    {
        CRUD::setModel(Invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings(__('invoices.entity.singular'), __('invoices.entity.plural'));
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label(__('invoices.fields.user_id'))
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

        CRUD::filter('payment_status_id')
            ->type('select2')
            ->label(__('invoices.fields.status'))
            ->values(
                Status::pluck('name', 'id')->toArray(),
            )
            ->whenActive(function ($value): void {
                CRUD::addClause(
                    'where',
                    'payment_status_id',
                    $value,
                );
            });

        CRUD::addFilter(
            [
                'type' => 'datepicker_range',
                'name' => 'tax_point_date',
                'label' => __('invoices.fields.tax_point_date'),
            ],
            false,
            function ($value): void {
                $dates = json_decode($value);
                CRUD::addClause(
                    'where',
                    'tax_point_date',
                    '>=',
                    $dates->from,
                );
                CRUD::addClause(
                    'where',
                    'tax_point_date',
                    '<=',
                    $dates->to,
                );
            },
        );

        CRUD::column('invoice_vs')->label(__('invoices.fields.invoice_vs'));
        CRUD::column('payment_amount')->type('number')->label(__('invoices.fields.payment_amount'));
        CRUD::column('issue_date')->type('date')->label(__('invoices.fields.issue_date'));
        CRUD::column('tax_point_date')->type('date')->label(__('invoices.fields.tax_point_date'));

        CRUD::column('payment_status')
            ->type('text')
            ->label(__('invoices.fields.status'))
            ->value(function ($entry) {
                return $entry->paymentStatus?->name ?? __('general.placeholders.not_available');
            });

        CRUD::column('due_in')->label(__('invoices.fields.due_in'));
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('invoice_vs')->label(__('invoices.fields.invoice_vs'));
        CRUD::column('user_id')->type('select')->entity('user')->attribute('name')->model(User::class)
            ->label(__('invoices.fields.user_id'));
        CRUD::column('supplier_id')->type('select')->entity('supplier')->attribute('name')->model(Supplier::class)
            ->label(__('invoices.fields.supplier'));
        CRUD::column('client_id')->type('select')->entity('client')->attribute('name')->model(Client::class)
            ->label(__('invoices.fields.client'));
        CRUD::column('issue_date')->type('date')->label(__('invoices.fields.issue_date'));
        CRUD::column('tax_point_date')->type('date')->label(__('invoices.fields.tax_point_date'));
        CRUD::column('due_in')->label(__('invoices.fields.due_in'));
        CRUD::column('payment_method_id')->type('select')->entity('paymentMethod')->attribute('name')->model(PaymentMethod::class)
            ->label(__('invoices.fields.payment_method'));
        CRUD::column('payment_status_id')->type('select')->entity('paymentStatus')->attribute('name')->model(Status::class)
            ->label(__('invoices.fields.status'));
        CRUD::column('payment_amount')->type('number')->decimals(2)->label(__('invoices.fields.payment_amount'));
        CRUD::column('payment_currency')->label(__('invoices.fields.payment_currency'));
        CRUD::column('invoice_ks')->label(__('invoices.fields.invoice_ks'));
        CRUD::column('invoice_ss')->label(__('invoices.fields.invoice_ss'));
        CRUD::column('invoice_text')->label(__('invoices.fields.invoice_text'));
        CRUD::column('template')->label(__('invoices.fields.template'));
        CRUD::column('created_at')->type('datetime')->label(__('general.common.created_at'));
        CRUD::column('updated_at')->type('datetime')->label(__('general.common.updated_at'));
    }

    /**
     * Setup create form fields
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(InvoiceRequest::class);

        // Backpack saves only declared fields; store() fills the owner in
        CRUD::field('user_id')->type('hidden');

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        // Get clients, suppliers, payment methods and statuses for select fields
        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statusCategoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;
        $statuses = Status::where('category_id', $statusCategoryId)->pluck('name', 'id')->toArray();
        Log::info('InvoiceCrudController: setupCreateOperation - statuses: ' . json_encode($statuses));
        $fields = $this->getInvoiceFields(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses,
        );

        // Add fields to CRUD form
        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect();

                CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect(),
                    )
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 ',
                    ]);
            } elseif ($field['name'] === 'payment_status_id') {
                CRUD::field('payment_status_id')
                    ->label(__('invoices.fields.status'))
                    ->type('relationship')
                    ->model(Status::class)
                    ->attribute('name')
                    ->options(function ($query) {
                        // Get the category ID for 'invoice'
                        $categoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;

                        // Return only active statuses for the category
                        return $query->where('category_id', $categoryId)->where('is_active', 1)->get();
                    })
                    ->wrapper(['class' => 'form-group col-md-12']);

            } elseif (in_array(
                $field['name'],
                self::PAYMENT_FIELDS,
                true,
            )) {
                $this->addPaymentField($field, true);

            } elseif (
                $field['name'] === 'invoice_ks' ||
                $field['name'] === 'invoice_ss'
            ) {
                CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6',
                    ]);

            } elseif (
                $field['name'] === 'payment_amount'
            ) {
                CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-4',
                    ]);

            } elseif (
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
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6',
                    ]);

            } elseif (
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
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3',
                    ]);

            } elseif ($field['type'] === 'select_from_array') {
                // Select from array field
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->options($field['options'] ?? []);

                if (isset($field['options']) && is_array($field['options'])) {
                    if ($field['type'] === 'select_from_array') {
                        $fieldConfig->options($field['options']);
                    } else {
                        $fieldConfig->options(function () use ($field) {
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
            } elseif ($field['type'] === 'select' && isset($field['entity'])) {
                if ($field['name'] === 'client_id' || $field['name'] === 'supplier_id') {
                    CRUD::field('separator_' . $field['name'])
                        ->type('custom_html')
                        ->value('<hr class="my-4" />');
                }

                // Entity select field
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2')
                    ->wrapper([
                        'class' => 'form-group col-md-6',
                    ])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
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
            } elseif ($field['type'] === 'file' && $field['name'] === 'invoice_logo') {
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
                // Other field types
                $fieldConfig = CRUD::field($field['name'])
                    ->type($field['type'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->label($field['label']);

                if (isset($field['options']) && is_array($field['options'])) {
                    $fieldConfig->options(function () use ($field) {
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
    public function store(): JsonResponse|RedirectResponse
    {
        $this->beforeEntityStore();

        return $this->traitStore();
    }

    /**
     * Setup update form fields
     */
    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(InvoiceRequest::class);

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/invoice.js'));

        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        $paymentMethods = PaymentMethod::pluck('name', 'id')->toArray();
        $statusCategoryId = StatusCategory::where('slug', 'invoice-statuses')->first()->id ?? null;
        $statuses = Status::where('category_id', $statusCategoryId)->pluck('name', 'id')->toArray();

        $fields = $this->getInvoiceFields(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses,
        );

        foreach ($fields as $field) {
            if ($field['name'] === 'country' || $field['name'] === 'client_country') {
                $options = App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect();

                CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->options(
                        App::make(CountryServiceInterface::class)->getSimpleCountriesForSelect(),
                    )
                    ->attributes(
                        [
                            'disabled' => 'disabled',
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3 ',
                    ]);
            } elseif ($field['name'] === 'payment_status_id') {
                CRUD::field('payment_status_id')
                    ->label(__('invoices.fields.status'))
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

            } elseif (in_array(
                $field['name'],
                self::PAYMENT_FIELDS,
                true,
            )) {
                $this->addPaymentField($field, false);

            } elseif (
                $field['name'] === 'invoice_ks' ||
                $field['name'] === 'invoice_ss'
            ) {
                CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6',
                    ]);

            } elseif (
                $field['name'] === 'payment_amount'
            ) {
                CRUD::field($field['name'])
                    ->type('text')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-4',
                    ]);

            } elseif (
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
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-6',
                    ]);

            } elseif (
                $field['name'] === 'invoice_text'
            ) {
                CRUD::field($field['name'])
                    ->type('textarea')
                    ->label($field['label'])
                    ->attributes(
                        [
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-12',
                    ]);

            } elseif (
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
                            'class' => 'form-control',
                        ],
                    )
                    ->wrapper([
                        'class' => 'form-group col-md-3',
                    ]);

            } elseif ($field['type'] === 'select_from_array') {
                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2_from_array')
                    ->label($field['label'])
                    ->options($field['options'] ?? []);

                if (isset($field['options']) && is_array($field['options'])) {
                    if ($field['type'] === 'select_from_array') {
                        $fieldConfig->options($field['options']);
                    } else {
                        $fieldConfig->options(function () use ($field) {
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
            } elseif ($field['type'] === 'select' && isset($field['entity'])) {
                if ($field['name'] === 'client_id' || $field['name'] === 'supplier_id') {
                    CRUD::field('separator_' . $field['name'])
                        ->type('custom_html')
                        ->value('<hr class="my-4" />');
                }

                $fieldConfig = CRUD::field($field['name'])
                    ->type('select2')
                    ->wrapper([
                        'class' => 'form-group col-md-6',
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
            } elseif ($field['type'] === 'file' && $field['name'] === 'invoice_logo') {
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
                    $fieldConfig->options(function () use ($field) {
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

    /**
     * Add an invoice date or payment field with the type defined in InvoiceFormFields
     * (selects get select2). On create the field defaults apply; on update an empty value stays
     * empty and a stored option missing from the list (e.g. due_in of 21 days) is kept.
     */
    private function addPaymentField(array $field, bool $create): void
    {
        $config = CRUD::field($field['name'])
            ->label($field['label'])
            ->wrapper(['class' => 'form-group ' . ($field['name'] === 'payment_currency' ? 'col-md-2' : 'col-md-6')]);

        if (! $create && $field['type'] === 'select_from_array') {
            $current = CRUD::getCurrentEntry()?->{$field['name']};
            if (filled($current) && ! array_key_exists($current, $field['options'])) {
                $field['options'][$current] = $field['name'] === 'due_in' ? $current . ' ' . __('invoices.units.days') : $current;
            }
        }

        match ($field['type']) {
            'select' => $config->type('select2')->entity($field['entity'])->model($field['model'])->attribute($field['attribute']),
            'select_from_array' => $config->type('select2_from_array')->options($field['options']),
            'date' => $config->type('datepicker'),
            default => $config->type($field['type']),
        };

        if ($create && isset($field['default'])) {
            $config->default($field['default']);
        }
        if (! empty($field['required'])) {
            $config->required(true);
        }
    }
}
