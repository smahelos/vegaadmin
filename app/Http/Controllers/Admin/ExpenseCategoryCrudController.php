<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExpenseCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseCategoryCrudController extends CrudController
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
        CRUD::setModel(\App\Models\ExpenseCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-category');
        CRUD::setEntityNameStrings('expense category', 'expense categories');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        //CRUD::setFromDb(); // set columns from db columns.

        CRUD::column('name')->label('Name');
        CRUD::column('slug')->label('Slug');
        CRUD::column('description')->label('Description');

        CRUD::column('color_preview')
            ->type('custom_html')
            ->value(function($entry) {
                return '<span class="badge bg-'.$entry->color.'">'.$entry->name.'</span>';
        });
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
        CRUD::setValidation(ExpenseCategoryRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

           
        CRUD::field('name')->label('Name');
        CRUD::field('slug')->label('Slug');
            
        CRUD::field('description')
            ->label(trans('admin.expenses.description'))
            ->type('textarea');

        CRUD::addField([
            'name' => 'color',
            'label' => 'Color',
            'type' => 'select_from_array',
            'options' => [
                'green' => 'Green',
                'yellow' => 'Yellow',
                'red' => 'Red',
                'blue' => 'Blue',
                'gray' => 'Gray',
                'purple' => 'Purple',
                'indigo' => 'Indigo',
                'pink' => 'Pink',
            ],
            'allows_null' => false,
            'default' => 'bg-gray-100 text-gray-800',
        ]);
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
}
