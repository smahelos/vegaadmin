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
     */
    public function setup(): void
    {
        CRUD::setModel(ExpenseCategory::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/expense-category',
        );
        CRUD::setEntityNameStrings(
            __('expenses.categories.entity.singular'),
            __('expenses.categories.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('expenses.categories.fields.name'),
        );
        $column = CRUD::column('slug');
        $column->label(
            __('expenses.categories.fields.slug'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('expenses.categories.fields.description'),
        );

        $column = CRUD::column('color_preview');
        $column->type('custom_html');
        $column->label(
            __('expenses.categories.fields.color'),
        );
        $column->value(
            function ($entry) {
                // Tabler has no plain bg-gray badge color
                $color = ['gray' => 'secondary'][$entry->color ?? 'gray'] ?? $entry->color;

                return '<span class="badge bg-' . e($color) . ' text-' . e($color) . '-fg">' . e($entry->name) . '</span>';
            },
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ExpenseCategoryRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('expenses.categories.fields.name'),
        );
        $field = CRUD::field('slug');
        $field->label(
            __('expenses.categories.fields.slug'),
        );
        $field->hint(
            __('expenses.categories.hints.slug'),
        );

        $field = CRUD::field('description');
        $field->label(
            __('expenses.categories.fields.description'),
        );
        $field->type('textarea');

        CRUD::addField(
            [
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
            ],
        );
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
}
