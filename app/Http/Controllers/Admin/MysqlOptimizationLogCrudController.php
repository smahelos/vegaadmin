<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MysqlOptimizationLogRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * MySQL Optimization Log CRUD Controller
 */
class MysqlOptimizationLogCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\MysqlOptimizationLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mysql-optimization-log');
        CRUD::setEntityNameStrings(
            __('admin.database.optimization_log'), 
            __('admin.database.optimization_logs')
        );
        
        // Only allow viewing and showing logs, no create/edit/delete
        CRUD::denyAccess(['create', 'update', 'delete']);
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation()
    {
        CRUD::column('setting_name')
            ->label(__('admin.database.setting_name'))
            ->type('text');
            
        CRUD::column('current_value')
            ->label(__('admin.database.current_value'))
            ->type('text');
            
        CRUD::column('recommended_value')
            ->label(__('admin.database.recommended_value'))
            ->type('text');
            
        CRUD::column('description')
            ->label(__('admin.database.description'))
            ->type('text')
            ->limit(100);
            
        CRUD::column('priority')
            ->label(__('admin.database.priority'))
            ->type('badge')
            ->options([
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'info'
            ]);
            
        CRUD::column('applied')
            ->label(__('admin.database.applied'))
            ->type('badge')
            ->options([
                1 => 'success',
                0 => 'secondary'
            ])
            ->text([
                1 => __('admin.database.applied'),
                0 => __('admin.database.pending')
            ]);
            
        CRUD::column('created_at')
            ->label(__('admin.database.created_at'))
            ->type('datetime');
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
            
        CRUD::column('updated_at')
            ->label(__('admin.database.updated_at'))
            ->type('datetime');
    }
}
