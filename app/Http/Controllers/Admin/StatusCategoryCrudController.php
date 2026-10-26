<?php

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
class StatusCategoryCrudController extends CrudController
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
        CRUD::setModel(StatusCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/status-category');
        CRUD::setEntityNameStrings(
            __('statuses.categories.entity.singular'),
            __('statuses.categories.entity.plural'),
        );

        // Only allow access if user has permission to manage statuses
        if (! backpack_user()->hasPermissionTo('can_create_edit_status', 'backpack')) {
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
     */
    protected function setupListOperation(): void
    {
        CRUD::column('name')->label(__('statuses.categories.fields.name'));
        CRUD::column('slug')->label(__('statuses.categories.fields.slug'));
        CRUD::column('description')->label(__('statuses.categories.fields.description'));

        // Add a column showing the number of statuses in each category (counted in the list query)
        CRUD::addClause('withCount', 'statuses');
        CRUD::addColumn([
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
        ]);

        CRUD::column('created_at')
            ->label(trans('admin.general.created_at'))
            ->type('datetime');

        CRUD::column('updated_at')
            ->label(trans('admin.general.updated_at'))
            ->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(StatusCategoryRequest::class);

        CRUD::field('name')
            ->label(__('statuses.categories.fields.name'))
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('slug')
            ->label(__('statuses.categories.fields.slug'))
            ->hint(__('statuses.categories.hints.slug'))
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('description')
            ->label(__('statuses.categories.fields.description'))
            ->type('textarea');
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

        CRUD::column('statuses')
            ->type('relationship_table')
            ->label(__('statuses.categories.fields.statuses'))
            ->columns([
                'name' => __('statuses.fields.name'),
                'slug' => __('statuses.fields.slug'),
                'color_preview' => __('statuses.fields.color'),
                'is_active' => __('statuses.fields.is_active'),
            ])
            ->raw(['color_preview']);
    }
}
