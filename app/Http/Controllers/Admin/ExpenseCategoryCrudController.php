<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseCategoryCrudController
 *
 * @property-read CrudPanel $crud
 */
class ExpenseCategoryCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(ExpenseCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-category');
        CRUD::setEntityNameStrings(__('expenses.categories.entity.singular'), __('expenses.categories.entity.plural'));
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        // CRUD::setFromDb(); // set columns from db columns.

        CRUD::column('name')->label(__('expenses.categories.fields.name'));
        CRUD::column('slug')->label(__('expenses.categories.fields.slug'));
        CRUD::column('description')->label(__('expenses.categories.fields.description'));

        CRUD::column('color_preview')
            ->type('custom_html')
            ->label(__('expenses.categories.fields.color'))
            ->value(function ($entry) {
                // Tabler has no plain bg-gray badge color
                $color = ['gray' => 'secondary'][$entry->color ?? 'gray'] ?? $entry->color;

                return '<span class="badge bg-' . e($color) . ' text-' . e($color) . '-fg">' . e($entry->name) . '</span>';
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
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseCategoryRequest::class);
        // CRUD::setFromDb(); // set fields from db columns.

        CRUD::field('name')->label(__('expenses.categories.fields.name'));
        CRUD::field('slug')
            ->label(__('expenses.categories.fields.slug'))
            ->hint(__('expenses.categories.hints.slug'));

        CRUD::field('description')
            ->label(__('expenses.categories.fields.description'))
            ->type('textarea');

        CRUD::addField([
            'name' => 'color',
            'label' => __('expenses.categories.fields.color'),
            'type' => 'select_from_array',
            'options' => [
                'green' => __('general.colors.green'),
                'yellow' => __('general.colors.yellow'),
                'red' => __('general.colors.red'),
                'blue' => __('general.colors.blue'),
                'gray' => __('general.colors.gray'),
                'purple' => __('general.colors.purple'),
                'indigo' => __('general.colors.indigo'),
                'pink' => __('general.colors.pink'),
            ],
            'allows_null' => false,
            'default' => 'gray',
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
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
