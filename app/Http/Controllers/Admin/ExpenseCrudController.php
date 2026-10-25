<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Supplier;
use App\Models\Tax;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

/**
 * Class ExpenseCrudController
 *
 * @property-read CrudPanel $crud
 */
class ExpenseCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Expense::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense');
        CRUD::setEntityNameStrings(
            __('expenses.entity.singular'),
            __('expenses.entity.plural'),
        );

        // Only allow access if user has permission (using backpack guard)
        $user = backpack_user();
        if (! $user || ! backpack_user()->hasPermissionTo('can_create_edit_expense', 'backpack')) {
            CRUD::denyAccess([
                'list',
                'create',
                'update',
                'delete',
            ]);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        CRUD::column('expense_date')
            ->label(__('expenses.fields.expense_date'))
            ->type('date');

        CRUD::column('reference_number')
            ->label(__('expenses.fields.reference_number'));

        CRUD::column('supplier')
            ->label(__('expenses.fields.supplier_id'))
            ->type('relationship')
            ->attribute('name');

        CRUD::column('category')
            ->label(__('expenses.fields.category_id'))
            ->type('relationship')
            ->attribute('name');

        CRUD::column('amount')
            ->label(__('expenses.fields.amount'))
            ->type('number')
            ->prefix('');

        CRUD::column('currency')
            ->label(__('expenses.fields.currency'));

        CRUD::column('status')
            ->label(__('expenses.fields.status_id'))
            ->type('relationship')
            ->attribute('name');

        CRUD::column('description')
            ->label(__('expenses.fields.description'))
            ->type('text')
            ->limit(80)
            ->escaped(true);

        CRUD::column('attachments')
            ->label(__('expenses.fields.attachments'))
            ->type('closure')
            ->function(function ($entry) {
                if (! is_array($entry->attachments)) {
                    return '';
                }
                $fileNames = array_map(function ($path) {
                    return basename($path);
                }, $entry->attachments);

                return implode(', ', $fileNames);
            })
            ->limit(50);

        // Filters
        CRUD::filter('expense_date')
            ->type('datepicker_range')
            ->label(__('expenses.filters.date_range'))
            ->whenActive(function ($value): void {
                $dates = json_decode($value);
                CRUD::addClause(
                    'where',
                    'expense_date',
                    '>=',
                    $dates->from,
                );
                CRUD::addClause(
                    'where',
                    'expense_date',
                    '<=',
                    $dates->to,
                );
            });

        CRUD::filter('supplier_id')
            ->type('select2')
            ->label(__('expenses.fields.supplier_id'))
            ->values(Supplier::pluck('name', 'id')->toArray());

        CRUD::filter('category_id')
            ->type('select2')
            ->label(__('expenses.fields.category_id'))
            ->values(ExpenseCategory::where('is_active', 1)
                ->pluck('name', 'id')->toArray());

        // Get the category ID for 'expense'
        $categoryId = StatusCategory::where('slug', 'expense-statuses')->first()->id ?? null;

        CRUD::filter('status_id')
            ->type('select2')
            ->label(__('expenses.fields.status_id'))
            ->values(Status::where('category_id', $categoryId)->where('is_active', 1)
                ->pluck('name', 'id')->toArray());
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ExpenseRequest::class);

        Widget::add()->type('script')->content(asset('assets/js/admin/forms/expense-tax-calculator.js'));

        // Basic information
        CRUD::field('expense_date')
            ->label(__('expenses.fields.expense_date'))
            ->type('datepicker')
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('reference_number')
            ->label(__('expenses.fields.reference_number'))
            ->hint(__('expenses.hints.reference_number'))
            ->wrapper(['class' => 'form-group col-md-6']);

        // Amount and currency
        CRUD::field('amount')
            ->label(__('expenses.fields.amount'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('currency')
            ->label(__('expenses.fields.currency'))
            ->type('select2_from_array')
            ->options([
                'CZK' => 'CZK',
                'EUR' => 'EUR',
                'USD' => 'USD',
            ])
            ->wrapper(['class' => 'form-group col-md-6']);

        // Related entities
        CRUD::field('supplier_id')
            ->label(__('expenses.fields.supplier_id'))
            ->type('relationship')
            ->model(Supplier::class)
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('category_id')
            ->label(__('expenses.fields.category_id'))
            ->type('relationship')
            ->model(ExpenseCategory::class)
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('payment_method_id')
            ->label(__('expenses.fields.payment_method_id'))
            ->type('relationship')
            ->entity('paymentMethod')
            ->model(PaymentMethod::class)
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('status_id')
            ->label(__('expenses.fields.status_id'))
            ->type('relationship')
            ->model(Status::class)
            ->attribute('name')
            ->options(function ($query) {
                // Get the category ID for 'expense'
                $categoryId = StatusCategory::where('slug', 'expense-statuses')->first()->id ?? null;

                // Return only active statuses for the category
                return $query->where('category_id', $categoryId)->where('is_active', 1)->get();
            })
            ->wrapper(['class' => 'form-group col-md-6']);

        // Additional info
        CRUD::field('description')
            ->label(__('expenses.fields.description'))
            ->type('textarea');

        $this->crud->addField([
            'name' => 'attachments',
            'label' => __('expenses.fields.attachments'),
            'type' => 'files_list',
            'upload' => true,
            'disk' => 'public',
            'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
            'hint' => __('expenses.hints.attachments'),
        ]);

        $taxes = Tax::all();
        $options = [];
        foreach ($taxes as $tax) {
            // Format: "DPH 21%" with data-rate attribute containing the rate value
            $options[$tax->rate] = "{$tax->name}";
        }
        CRUD::field('tax_rate')
            ->label(__('expenses.fields.tax_rate'))
            ->type('select_from_array')
            ->options($options)
            ->wrapper(['class' => 'form-group col-md-3']);

        $this->crud->addField([
            'name' => 'tax_included',
            'label' => __('expenses.fields.tax_included'),
            'type' => 'checkbox',
            'default' => true,
            'wrapper' => [
                'class' => 'form-group col-md-3 mt-5 tax_included_parent',
            ],
        ]);

        CRUD::field('tax_amount')
            ->label(__('expenses.fields.tax_amount'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->wrapper(['class' => 'form-group col-md-6']);

        // Set user_id automatically to current user
        CRUD::field('user_id')
            ->type('hidden')
            ->value(backpack_user()->id);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Configure the Show operation.
     */
    protected function setupShowOperation(): void
    {
        // Re-use most of the same columns from list operation
        $this->setupListOperation();

        // Also ensure description is properly displayed if null
        if (collect($this->crud->columns())->firstWhere('name', 'description')) {
            $this->crud->modifyColumn('description', [
                'escaped' => true,
                'type' => 'closure',
                'function' => function ($entry) {
                    return $entry->description ?? '';
                },
            ]);
        }
    }
}
