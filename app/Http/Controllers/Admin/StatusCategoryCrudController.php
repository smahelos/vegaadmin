<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatusCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StatusCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StatusCategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\StatusCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/status-category');
        CRUD::setEntityNameStrings(
            trans('admin.status_categories.status_category'), 
            trans('admin.status_categories.status_categories')
        );
        
        // Only allow access if user has permission to manage statuses
        if (!backpack_user()->can('can_create_edit_status')) {
            CRUD::denyAccess(['list', 'create', 'update', 'delete']);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label(trans('admin.status_categories.name'));
        CRUD::column('slug')->label(trans('admin.status_categories.slug'));
        CRUD::column('description')->label(trans('admin.status_categories.description'));
        
        // Add a column showing the number of statuses in each category
        CRUD::addColumn([
            'name'      => 'statuses_count',
            'label'     => trans('admin.status_categories.statuses_count'),
            'type'      => 'relationship_count',
            'wrapper'   => [
                'href' => function ($crud, $column, $entry, $related_key) {
                    return backpack_url('status?category_id='.$entry->getKey());
                },
            ],
        ]);
        
        CRUD::column('created_at')
            ->label(trans('admin.common.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(trans('admin.common.updated_at'))
            ->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StatusCategoryRequest::class);

        CRUD::field('name')
            ->label(trans('admin.status_categories.name'))
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('slug')
            ->label(trans('admin.status_categories.slug'))
            ->hint(trans('admin.status_categories.slug_hint'))
            ->wrapper(['class' => 'form-group col-md-6']);
            
        CRUD::field('description')
            ->label(trans('admin.status_categories.description'))
            ->type('textarea');
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
    
    /**
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        // Add a tab with related statuses
        CRUD::addTab(trans('admin.status_categories.statuses_tab'));
        
        CRUD::addField([
            'name'          => 'statuses',
            'label'         => trans('admin.status_categories.statuses'),
            'type'          => 'relationship_table',
            'columns'       => [
                'name'          => trans('admin.statuses.name'),
                'slug'          => trans('admin.statuses.slug'),
                'color_preview' => trans('admin.statuses.color'),
                'type'          => trans('admin.statuses.type'),
                'is_active'     => trans('admin.statuses.is_active'),
            ],
            'button_create' => true,
            'button_delete' => false,
        ]);
    }
}
