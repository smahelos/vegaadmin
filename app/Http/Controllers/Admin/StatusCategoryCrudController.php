<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatusCategoryRequest;
use App\Models\StatusCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StatusCategoryCrudController
 *
 * @property-read CrudPanel $crud
 */
final class StatusCategoryCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(StatusCategory::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/status-category',
        );
        CRUD::setEntityNameStrings(
            __('statuses.categories.entity.singular'),
            __('statuses.categories.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => 'can_view_status',
                'show' => 'can_view_status',
                'create' => 'can_create_status',
                'update' => 'can_update_status',
                'delete' => 'can_delete_status',
            ],
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('statuses.categories.fields.name'),
        );
        $column = CRUD::column('slug');
        $column->label(
            __('statuses.categories.fields.slug'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('statuses.categories.fields.description'),
        );

        // Add a column showing the number of statuses in each category (counted in the list query)
        CRUD::addClause('withCount', 'statuses');
        CRUD::addColumn(
            [
                'name' => 'statuses_count',
                'label' => __('statuses.categories.fields.statuses_count'),
                'type' => 'text',
                'wrapper' => [
                    'href' => function (
                        $crud,
                        $column,
                        $entry,
                        $related_key,
                    ) {
                        return backpack_url('status?category_id=' . $entry->getKey());
                    },
                ],
            ],
        );

        $column = CRUD::column('created_at');
        $column->label(
            __('admin.general.created_at'),
        );
        $column->type('datetime');

        $column = CRUD::column('updated_at');
        $column->label(
            __('admin.general.updated_at'),
        );
        $column->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(StatusCategoryRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('statuses.categories.fields.name'),
        );
        $field->wrapper(['class' => 'form-group col-md-6']);

        $field = CRUD::field('slug');
        $field->label(
            __('statuses.categories.fields.slug'),
        );
        $field->hint(
            __('statuses.categories.hints.slug'),
        );
        $field->wrapper(['class' => 'form-group col-md-6']);

        $field = CRUD::field('description');
        $field->label(
            __('statuses.categories.fields.description'),
        );
        $field->type('textarea');
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        $column = CRUD::column('statuses');
        $column->type('relationship_table');
        $column->label(
            __('statuses.categories.fields.statuses'),
        );
        $column->columns(
            [
                'name' => __('statuses.fields.name'),
                'slug' => __('statuses.fields.slug'),
                'color_preview' => __('statuses.fields.color'),
                'is_active' => __('statuses.fields.is_active'),
            ],
        );
        $column->raw(['color_preview']);
    }
}
