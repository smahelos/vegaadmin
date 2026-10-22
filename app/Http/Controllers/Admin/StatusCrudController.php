<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatusRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Domain\Shared\Status\Contracts\StatusServiceInterface as DomainStatusServiceInterface;
use App\Models\Status;
use App\Models\StatusCategory;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Status management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StatusCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Status::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/status');
        CRUD::setEntityNameStrings(__('statuses.entity.singular'), __('statuses.entity.plural'));

        // Filter by category_id from URL
        if (request()->has('category_id')) {
            $this->crud->addClause('where', 'category_id', request()->input('category_id'));

            // Get category name for a more descriptive page title
            $category = \App\Models\StatusCategory::find(request()->input('category_id'));
            if ($category) {
                CRUD::setEntityNameStrings(
                    $category->name . ' ' . __('statuses.entity.singular'),
                    $category->name . ' ' . __('statuses.entity.plural')
                );
            }
        }
    }

    /**
     * Setup list view columns
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('statuses.fields.name'));
        CRUD::column('slug')->label(__('statuses.fields.slug'));

        // Add column for category
        CRUD::addColumn([
            'name' => 'category',
            'label' => __('statuses.fields.category_id'),
            'type' => 'relationship',
            'relation_type' => 'BelongsTo',
            'entity' => 'category',
            'model' => StatusCategory::class,
            'attribute' => 'name',
            'wrapper'   => [
                'href' => function ($crud, $column, $entry, $related_key) {
                    return backpack_url('status-category/'.$entry->category_id.'/show');
                },
            ],
        ])->afterColumn('slug');

        // Add a filter for category
        CRUD::addFilter([
            'name'  => 'category_id',
            'type'  => 'select2',
            'label' => __('statuses.filters.category')
        ], function() {
            return StatusCategory::pluck('name', 'id')->toArray();
        }, function($value) {
            CRUD::addClause('where', 'category_id', $value);
        });

        CRUD::column('color_preview')
            ->type('custom_html')
            ->label(__('statuses.fields.color'))
            ->value(fn ($entry) => $entry->color_preview);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => __('statuses.fields.is_active'),
            'type' => 'boolean',
        ]);
    }

    /**
     * Setup create form fields
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StatusRequest::class);

        CRUD::field('name')->label(__('statuses.fields.name'));
        CRUD::field('slug')->label(__('statuses.fields.slug'));

        // Add field for category selection
        CRUD::addField([
            'name'      => 'category_id',
            'label'     => __('statuses.fields.category_id'),
            'type'      => 'relationship',
            'entity'    => 'category',
            'attribute' => 'name',
            'required' => true,
            'model'     => StatusCategory::class,
        ])->afterField('type');


        CRUD::addField([
            'name' => 'color',
            'label' => __('statuses.fields.color'),
            'type' => 'select_from_array',
            'options' => [
                'green' => __('statuses.colors.green'),
                'yellow' => __('statuses.colors.yellow'),
                'red' => __('statuses.colors.red'),
                'blue' => __('statuses.colors.blue'),
                'gray' => __('statuses.colors.gray'),
                'purple' => __('statuses.colors.purple'),
                'indigo' => __('statuses.colors.indigo'),
                'pink' => __('statuses.colors.pink'),
            ],
            'allows_null' => false,
            'default' => 'gray',
        ]);
        CRUD::field('is_active')->label(__('statuses.fields.is_active'))->type('boolean');
    }

    /**
     * Setup update form fields
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
