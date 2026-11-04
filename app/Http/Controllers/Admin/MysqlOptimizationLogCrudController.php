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
    public function setup(): void
    {
        CRUD::setModel(MysqlOptimizationLog::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/mysql-optimization-log',
        );
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
    protected function setupListOperation(): void
    {
        $column = CRUD::column('setting_name');
        $column->label(
            __('admin/database.optimization_logs.fields.setting_name'),
        );
        $column->type('text');

        $column = CRUD::column('current_value');
        $column->label(
            __('admin/database.optimization_logs.fields.current_value'),
        );
        $column->type('text');

        $column = CRUD::column('recommended_value');
        $column->label(
            __('admin/database.optimization_logs.fields.recommended_value'),
        );
        $column->type('text');

        $column = CRUD::column('description');
        $column->label(
            __('admin/database.optimization_logs.fields.description'),
        );
        $column->type('text');
        $column->limit(100);

        $column = CRUD::column('priority');
        $column->label(
            __('admin/database.optimization_logs.fields.priority'),
        );
        $column->type('badge');
        $column->options(
            [
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'info',
            ],
        );

        $column = CRUD::column('applied');
        $column->label(
            __('admin/database.optimization_logs.fields.applied'),
        );
        $column->type('badge');
        $column->options([
            1 => 'success',
            0 => 'secondary',
        ]);
        $column->text(
            [
                1 => __('admin/database.optimization_logs.statuses.applied'),
                0 => __('admin/database.optimization_logs.statuses.pending'),
            ],
        );

        $column = CRUD::column('created_at');
        $column->label(
            __('admin/database.optimization_logs.fields.created_at'),
        );
        $column->type('datetime');
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        $column = CRUD::column('description');
        $column->label(
            __('admin/database.optimization_logs.fields.description'),
        );
        $column->type('textarea');

        $column = CRUD::column('updated_at');
        $column->label(
            __('admin/database.optimization_logs.fields.updated_at'),
        );
        $column->type('datetime');
    }
}
