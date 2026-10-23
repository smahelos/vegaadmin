<?php

namespace App\Http\Controllers\Admin;

use App\Models\MysqlOptimizationLog;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * MySQL Optimization Log CRUD Controller
 */
class MysqlOptimizationLogCrudController extends CrudController
{
    use ListOperation;
    use ShowOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup()
    {
        CRUD::setModel(MysqlOptimizationLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mysql-optimization-log');
        CRUD::setEntityNameStrings(
            __('admin/database.optimization_logs.entity.singular'),
            __('admin/database.optimization_logs.entity.plural'),
        );

        // Only allow viewing and showing logs, no create/edit/delete
        CRUD::denyAccess([
            'create',
            'update',
            'delete',
        ]);
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation()
    {
        CRUD::column('setting_name')
            ->label(__('admin/database.optimization_logs.fields.setting_name'))
            ->type('text');

        CRUD::column('current_value')
            ->label(__('admin/database.optimization_logs.fields.current_value'))
            ->type('text');

        CRUD::column('recommended_value')
            ->label(__('admin/database.optimization_logs.fields.recommended_value'))
            ->type('text');

        CRUD::column('description')
            ->label(__('admin/database.optimization_logs.fields.description'))
            ->type('text')
            ->limit(100);

        CRUD::column('priority')
            ->label(__('admin/database.optimization_logs.fields.priority'))
            ->type('badge')
            ->options([
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'info',
            ]);

        CRUD::column('applied')
            ->label(__('admin/database.optimization_logs.fields.applied'))
            ->type('badge')
            ->options([
                1 => 'success',
                0 => 'secondary',
            ])
            ->text([
                1 => __('admin/database.optimization_logs.statuses.applied'),
                0 => __('admin/database.optimization_logs.statuses.pending'),
            ]);

        CRUD::column('created_at')
            ->label(__('admin/database.optimization_logs.fields.created_at'))
            ->type('datetime');
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::column('description')
            ->label(__('admin/database.optimization_logs.fields.description'))
            ->type('textarea');

        CRUD::column('updated_at')
            ->label(__('admin/database.optimization_logs.fields.updated_at'))
            ->type('datetime');
    }
}
