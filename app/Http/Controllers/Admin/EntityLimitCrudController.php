<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EntityLimitRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EntityLimitCrudController
 * Universal Entity Limit System CRUD Controller - Permission-based System
 * 
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EntityLimitCrudController extends CrudController
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
        // Check permissions first
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            // Deny access to operations
            CRUD::denyAccess(['list','show','create','update','delete']);
        }
        
        CRUD::setModel(\App\Models\EntityLimit::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/entity-limit');
        CRUD::setEntityNameStrings('entity limit', 'entity limits');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Define columns for list view
        CRUD::column('permission_name')
            ->label('Permission Name')
            ->type('text');
            
        CRUD::column('entity_type')
            ->label('Entity Type')
            ->type('select_from_array')
            ->options(array_combine(\App\Models\EntityLimit::ENTITY_TYPES, array_map('ucfirst', \App\Models\EntityLimit::ENTITY_TYPES)));
            
        CRUD::column('metric_type')
            ->label('Metric Type')
            ->type('select_from_array')
            ->options(\App\Models\EntityLimit::METRIC_TYPES);
            
        CRUD::column('period_type')
            ->label('Period')
            ->type('select_from_array')
            ->options(\App\Models\EntityLimit::PERIOD_TYPES);
            
        CRUD::column('limit_value')
            ->label('Limit Value')
            ->type('closure')
            ->function(function($entry) {
                if ($entry->metric_type === 'count') {
                    return number_format($entry->limit_value ?? 0);
                } elseif ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->limit_value ?? 0, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->limit_value ?? 0);
                }
                return 'N/A';
            });
            
        CRUD::column('is_active')
            ->label('Status')
            ->type('boolean')
            ->options([0 => 'Inactive', 1 => 'Active']);
            
        CRUD::column('description')
            ->label('Description')
            ->type('text')
            ->limit(100);

        // Set default order
        CRUD::orderBy('entity_type');
        CRUD::orderBy('is_active', 'desc');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(EntityLimitRequest::class);

        // Permission Name field
        $permissions = \Spatie\Permission\Models\Permission::pluck('name', 'name')->toArray();
        $permissions[\App\Models\EntityLimit::ANONYMOUS_PERMISSION] = 'Anonymous Users';
        
        CRUD::field('permission_name')
            ->label('Permission Name')
            ->type('select_from_array')
            ->options($permissions)
            ->allows_null(false)
            ->hint('Select which permission this limit applies to, or choose Anonymous Users for non-authenticated users');

        // Entity Type
        CRUD::field('entity_type')
            ->label('Entity Type')
            ->type('select_from_array')
            ->options(array_combine(\App\Models\EntityLimit::ENTITY_TYPES, array_map('ucfirst', \App\Models\EntityLimit::ENTITY_TYPES)))
            ->allows_null(false);

        // Metric Type
        CRUD::field('metric_type')
            ->label('Metric Type')
            ->type('select_from_array')
            ->options(\App\Models\EntityLimit::METRIC_TYPES)
            ->allows_null(false)
            ->default('count');

        // Period Type
        CRUD::field('period_type')
            ->label('Period Type')
            ->type('select_from_array')
            ->options(\App\Models\EntityLimit::PERIOD_TYPES)
            ->allows_null(false)
            ->default('monthly');

        // Limit Value
        CRUD::field('limit_value')
            ->label('Limit Value')
            ->type('number')
            ->attributes(['min' => 0, 'step' => 0.01])
            ->hint('The maximum value allowed per period. For count: number of items, for value: monetary amount, for size: bytes');

        // Description
        CRUD::field('description')
            ->label('Description')
            ->type('textarea')
            ->attributes(['rows' => 3])
            ->hint('Optional human-readable description of this limit');

        // Is Active
        CRUD::field('is_active')
            ->label('Active')
            ->type('boolean')
            ->default(true)
            ->hint('Whether this limit is currently enforced');
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

    /**
     * Helper method to format bytes into human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
