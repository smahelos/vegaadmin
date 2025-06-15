<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ArchivePolicyRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Archive Policy CRUD Controller
 */
class ArchivePolicyCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\ArchivePolicy::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/archive-policy');
        CRUD::setEntityNameStrings(
            __('admin.database.archive_policy'), 
            __('admin.database.archive_policies')
        );
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation()
    {   
        CRUD::column('table_name')
            ->label(__('admin.database.table_name'))
            ->type('text');
            
        CRUD::column('retention_months')
            ->label(__('admin.database.retention_months'))
            ->type('number')
            ->suffix(' months');
            
        CRUD::column('date_column')
            ->label(__('admin.database.date_column'))
            ->type('text');
            
        CRUD::column('is_active')
            ->label(__('admin.database.is_active'))
            ->type('boolean');
            
        CRUD::column('created_at')
            ->label(__('admin.database.created_at'))
            ->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArchivePolicyRequest::class);

        // Add detailed description above form fields
        CRUD::addField([
            'name' => 'archive_policy_form_description',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="la la-info-circle"></i> ' . __('admin.database.archive_policy_form_title') . '</h6>
                            <p class="mb-2">' . __('admin.database.archive_policy_form_description') . '</p>
                            <hr>
                            <ul class="mb-0">
                                <li><strong>' . __('admin.database.table_name') . ':</strong> ' . __('admin.database.table_name_detailed_hint') . '</li>
                                <li><strong>' . __('admin.database.retention_months') . ':</strong> ' . __('admin.database.retention_months_detailed_hint') . '</li>
                                <li><strong>' . __('admin.database.date_column') . ':</strong> ' . __('admin.database.date_column_detailed_hint') . '</li>
                                <li><strong>' . __('admin.database.is_active') . ':</strong> ' . __('admin.database.is_active_detailed_hint') . '</li>
                                <li><strong>' . __('admin.database.description') . ':</strong> ' . __('admin.database.description_detailed_hint') . '</li>
                            </ul>
                        </div>'
        ]);

        CRUD::field('table_name')
            ->label(__('admin.database.table_name'))
            ->type('text')
            ->hint(__('admin.database.table_name_hint'));
            
        CRUD::field('retention_months')
            ->label(__('admin.database.retention_months'))
            ->type('number')
            ->attributes(['min' => 1, 'max' => 120])
            ->hint(__('admin.database.retention_months_hint'));
            
        CRUD::field('date_column')
            ->label(__('admin.database.date_column'))
            ->type('text')
            ->hint(__('admin.database.date_column_hint'));
            
        CRUD::field('is_active')
            ->label(__('admin.database.is_active'))
            ->type('boolean')
            ->default(true);
            
        CRUD::field('description')
            ->label(__('admin.database.description'))
            ->type('textarea')
            ->hint(__('admin.database.description_hint'));
    }

    /**
     * Define what happens when the Update operation is loaded
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::column('description')
            ->label(__('admin.database.description'))
            ->type('textarea');
    }
}
