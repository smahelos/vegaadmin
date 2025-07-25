<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\Widget;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
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
        CRUD::setModel(\App\Models\Expense::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense');
        CRUD::setEntityNameStrings(
            trans('admin.expenses.expense'), 
            trans('admin.expenses.expenses')
        );
        
        // Only allow access if user has permission (using backpack guard)
        $user = backpack_user();
        if (!$user || !backpack_user()->hasPermissionTo('can_create_edit_expense', 'backpack')) {
            CRUD::denyAccess(['list', 'create', 'update', 'delete']);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('expense_date')
            ->label(trans('admin.expenses.date'))
            ->type('date');
            
        CRUD::column('reference_number')
            ->label(trans('admin.expenses.reference_number'));
            
        CRUD::column('supplier')
            ->label(trans('admin.expenses.supplier'))
            ->type('relationship')
            ->attribute('name');
            
        CRUD::column('category')
            ->label(trans('admin.expenses.category'))
            ->type('relationship')
            ->attribute('name');
            
        CRUD::column('amount')
            ->label(trans('admin.expenses.amount'))
            ->type('number')
            ->prefix('');
            
        CRUD::column('currency')
            ->label(trans('admin.expenses.currency'));
            
        CRUD::column('status')
            ->label(trans('admin.expenses.status'))
            ->type('relationship')
            ->attribute('name');
            
        CRUD::column('description')
            ->label(trans('admin.expenses.description'))
            ->type('text')
            ->limit(80)
            ->escaped(true);

        CRUD::column('attachments')
            ->label(trans('admin.expenses.attachments'))
            ->type('closure')
            ->function(function($entry) {
                if (!is_array($entry->attachments)) {
                    return 'shit';
                }
                $fileNames = array_map(function($path) {
                    return basename($path);
                }, $entry->attachments);
                return implode(', ', $fileNames);
            })
            ->limit(50);

        // Filters
        CRUD::filter('expense_date')
            ->type('date_range')
            ->label(trans('admin.expenses.date_range'));
            
        CRUD::filter('supplier_id')
            ->type('select2')
            ->label(trans('admin.expenses.supplier'))
            ->values(\App\Models\Supplier::all()
                ->pluck('name', 'id')->toArray());
            
        CRUD::filter('category_id')
            ->type('select2')
            ->label(trans('admin.expenses.category'))
            ->values(\App\Models\ExpenseCategory::where('is_active', 1)
                    ->pluck('name', 'id')->toArray());

        // Get the category ID for 'expense'
        $categoryId = \App\Models\StatusCategory::where('slug', 'expense-statuses')->first()->id ?? null;

        CRUD::filter('status_id')
            ->type('select2')
            ->label(trans('admin.expenses.status'))
            ->values(\App\Models\Status::where('category_id', $categoryId)->where('is_active', 1)
                ->pluck('name', 'id')->toArray());
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseRequest::class);
        
        Widget::add()->type('script')->content(asset('assets/js/admin/forms/expense-tax-calculator.js'));

        // Basic information
        CRUD::field('expense_date')
            ->label(trans('admin.expenses.date'))
            ->type('date')
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('reference_number')
            ->label(trans('admin.expenses.reference_number'))
            ->hint(trans('admin.expenses.reference_number_hint'))
            ->wrapper(['class' => 'form-group col-md-6']);
        
        // Amount and currency
        CRUD::field('amount')
            ->label(trans('admin.expenses.amount'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('currency')
            ->label(trans('admin.expenses.currency'))
            ->type('select2_from_array')
            ->options(['CZK' => 'CZK', 'EUR' => 'EUR', 'USD' => 'USD'])
            ->wrapper(['class' => 'form-group col-md-6']);
            
        // Related entities
        CRUD::field('supplier_id')
            ->label(trans('admin.expenses.supplier'))
            ->type('relationship')
            ->model(\App\Models\Supplier::class)
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('category_id')
            ->label(trans('admin.expenses.category'))
            ->type('relationship')
            ->model(\App\Models\ExpenseCategory::class)
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('payment_method_id')
            ->name('paymentMethod')
            ->label(trans('admin.expenses.payment_method'))
            ->type('relationship')
            ->model(\App\Models\PaymentMethod::class)
            ->attribute('name')
            ->relation_type('BelongsTo')
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('status_id')
            ->label(trans('admin.expenses.status'))
            ->type('relationship')
            ->model(\App\Models\Status::class)
            ->attribute('name')
            ->options(function ($query) {
                // Get the category ID for 'expense'
                $categoryId = \App\Models\StatusCategory::where('slug', 'expense-statuses')->first()->id ?? null;
                
                // Return only active statuses for the category
                return $query->where('category_id', $categoryId)->where('is_active', 1)->get();
            })
            ->wrapper(['class' => 'form-group col-md-6']);
        
        // Additional info
        CRUD::field('description')
            ->label(trans('admin.expenses.description'))
            ->type('textarea');
            

        $this->crud->addField([
            'name' => 'attachments',
            'label' => trans('admin.expenses.attachments'),
            'type' => 'files_list',
            'upload' => true,
            'disk' => 'public',
            'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt',
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ],
            'hint' => trans('admin.expenses.attachments_hint'),
        ]);

        $taxes = \App\Models\Tax::all();
        $options = [];    
        foreach ($taxes as $tax) {
            // Format: "DPH 21%" with data-rate attribute containing the rate value
            $options[$tax->rate] = "{$tax->name}";
        }
        CRUD::field('tax_rate')
            ->label(trans('admin.expenses.tax_rate'))
            ->type('select_from_array')
            ->options($options)
            ->wrapper(['class' => 'form-group col-md-3']);
        
        $this->crud->addField([
            'name' => 'tax_included',
            'label' => trans('admin.expenses.tax_included'),
            'type' => 'checkbox',
            'default' => true,
            'wrapper' => [
                'class' => 'form-group col-md-3 mt-5 tax_included_parent'
            ],
        ]);
            
        CRUD::field('tax_amount')
            ->label(trans('admin.expenses.tax_amount'))
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
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Configure the Show operation.
     *
     * @return void
     */
    protected function setupShowOperation()
    {
        // Re-use most of the same columns from list operation
        $this->setupListOperation();
        
        // Override the attachments column with a custom renderer for the Show operation
        // $this->crud->modifyColumn('attachments', [
        //     'label' => trans('admin.expenses.attachments'),
        //     'type' => 'closure',
        //     'function' => function($entry) {
        //         if (empty($entry->attachments)) {
        //             return '';
        //         }
                
        //         $html = '<div class="attachments-list">';
        //         foreach ($entry->attachments as $filePath) {
        //             $url = asset('storage/' . $filePath);
        //             $fileName = basename($filePath);
        //             $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    
        //             // Icon based on file type
        //             $icon = 'fa-file';
        //             if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        //                 $icon = 'fa-file-image';
        //             } elseif ($extension === 'pdf') {
        //                 $icon = 'fa-file-pdf';
        //             } elseif (in_array($extension, ['doc', 'docx'])) {
        //                 $icon = 'fa-file-word';
        //             } elseif (in_array($extension, ['xls', 'xlsx'])) {
        //                 $icon = 'fa-file-excel';
        //             }
                    
        //             $html .= '<div class="attachment-item mb-2">';
        //             $html .= '<i class="fa ' . $icon . ' mr-2"></i>';
        //             $html .= '<a href="' . $url . '" target="_blank">' . $fileName . '</a>';
        //             $html .= '</div>';
        //         }
        //         $html .= '</div>';
                
        //         return $html;
        //     }
        // ]);
        
        // Also ensure description is properly displayed if null
        if (collect($this->crud->columns())->firstWhere('name', 'description')) {
            $this->crud->modifyColumn('description', [
                'escaped' => true,
                'type' => 'closure',
                'function' => function($entry) {
                    return $entry->description ?? '';
                }
            ]);
        }
    }
}
