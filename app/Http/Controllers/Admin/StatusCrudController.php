<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StatusRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
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
        CRUD::column('type')->label('Type');
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
        CRUD::field('type')->label(__('admin.statuses.status_types'))->type('select_from_array')
            ->options([
                'invoice_type' => __('invoices.status.invoice_statuses'),
                'user_type' => __('users.status.user_statuses'),
            ])->allows_null(false);
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
