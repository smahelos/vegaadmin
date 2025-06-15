<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatusRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Services\StatusService;
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
        CRUD::setEntityNameStrings('status', 'statuses');

        // Filter by category_id from URL
        if (request()->has('category_id')) {
            $this->crud->addClause('where', 'category_id', request()->input('category_id'));
            
            // Get category name for a more descriptive page title
            $category = \App\Models\StatusCategory::find(request()->input('category_id'));
            if ($category) {
                CRUD::setEntityNameStrings(
                    $category->name . ' ' . trans('admin.statuses.status'), 
                    $category->name . ' ' . trans('admin.statuses.statuses')
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
        CRUD::column('name')->label('Name');
        CRUD::column('slug')->label('Slug');
        
        // Add column for category
        CRUD::addColumn([
            'name' => 'category',
            'label' => trans('admin.statuses.category'),
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
            'label' => trans('admin.statuses.filter_by_category')
        ], function() {
            return StatusCategory::pluck('name', 'id')->toArray();
        }, function($value) {
            CRUD::addClause('where', 'category_id', $value);
        });

        CRUD::column('color_preview')
            ->type('custom_html')
            ->value(function($entry) {
                return '<span class="badge bg-'.$entry->color.'">'.$entry->name.'</span>';
        });
        
        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Active',
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
        
        CRUD::field('name')->label('Name');
        CRUD::field('slug')->label('Slug');

        // Add field for category selection
        CRUD::addField([
            'name'      => 'category_id',
            'label'     => trans('admin.statuses.category'),
            'type'      => 'relationship',
            'entity'    => 'category',
            'attribute' => 'name',
            'required' => true,
            'model'     => StatusCategory::class,
        ])->afterField('type');

        // NEXT LINES ARE THE OLD ONES, WHERE WE USED THE STATUS SERVICE
        // But now we have 'relationship' fiedType
        //
        // // Get product categories from the service
        // $statusService = new StatusService();
        // // Prepare product categories for select form
        // $statusCategories = $statusService->getAllCategories();
        // CRUD::field('category_id')
        //     ->type('select_from_array')
        //     ->label(trans('admin.statuses.category'))
        //     ->entity('category')
        //     ->model('App\Models\StatusCategory')
        //     ->pivot(true)
        //     ->attribute('name')
        //     ->options($statusCategories);
        
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
        CRUD::field('is_active')->label('Active')->type('boolean');
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
