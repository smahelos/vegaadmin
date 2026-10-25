<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EntityLimitRequest;
use App\Models\EntityLimit;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Spatie\Permission\Models\Permission;

/**
 * Class EntityLimitCrudController
 * Universal Entity Limit System CRUD Controller - Permission-based System
 *
 * @property-read CrudPanel $crud
 */
class EntityLimitCrudController extends CrudController
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
        // Check permissions first
        if (! backpack_user() || ! backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            // Deny access to operations
            CRUD::denyAccess([
                'list',
                'show',
                'create',
                'update',
                'delete',
            ]);
        }

        CRUD::setModel(EntityLimit::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/entity-limit');
        CRUD::setEntityNameStrings(__('entity_limits.entity.singular'), __('entity_limits.entity.plural'));
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        // Define columns for list view
        CRUD::column('permission_name')
            ->label(__('entity_limits.fields.permission_name'))
            ->type('text');

        CRUD::column('entity_type')
            ->label(__('entity_limits.fields.entity_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::ENTITY_TYPES)->mapWithKeys(fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")])->all());

        CRUD::column('metric_type')
            ->label(__('entity_limits.fields.metric_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::METRIC_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.metric_types.$type")])->all());

        CRUD::column('period_type')
            ->label(__('entity_limits.fields.period_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::PERIOD_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.periods.$type")])->all());

        CRUD::column('limit_value')
            ->label(__('entity_limits.fields.limit_value'))
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->metric_type === 'count') {
                    return number_format($entry->limit_value ?? 0);
                } elseif ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->limit_value ?? 0, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->limit_value ?? 0);
                }

                return __('general.placeholders.not_available');
            })
            ->default('0');

        CRUD::column('is_active')
            ->label(__('entity_limits.fields.is_active'))
            ->type('boolean')
            ->options([0 => __('general.no'), 1 => __('general.yes')]);

        CRUD::column('description')
            ->label(__('entity_limits.fields.description'))
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
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EntityLimitRequest::class);

        // Permission Name field
        $permissions = Permission::pluck('name', 'name')->toArray();
        $permissions[EntityLimit::ANONYMOUS_PERMISSION] = __('entity_limits.labels.anonymous_users');

        CRUD::field('permission_name')
            ->label(__('entity_limits.fields.permission_name'))
            ->type('select_from_array')
            ->options($permissions)
            ->allows_null(false)
            ->hint(__('entity_limits.hints.permission_name'));

        // Entity Type
        CRUD::field('entity_type')
            ->label(__('entity_limits.fields.entity_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::ENTITY_TYPES)->mapWithKeys(fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")])->all())
            ->allows_null(false);

        // Metric Type
        CRUD::field('metric_type')
            ->label(__('entity_limits.fields.metric_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::METRIC_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.metric_types.$type")])->all())
            ->allows_null(false)
            ->default('count');

        // Period Type
        CRUD::field('period_type')
            ->label(__('entity_limits.fields.period_type'))
            ->type('select_from_array')
            ->options(collect(EntityLimit::PERIOD_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.periods.$type")])->all())
            ->allows_null(false)
            ->default('monthly');

        // Limit Value
        CRUD::field('limit_value')
            ->label(__('entity_limits.fields.limit_value'))
            ->type('number')
            ->attributes(['min' => 0, 'step' => 0.01])
            ->hint(__('entity_limits.hints.limit_value'));

        // Description
        CRUD::field('description')
            ->label(__('entity_limits.fields.description'))
            ->type('textarea')
            ->attributes(['rows' => 3])
            ->hint(__('entity_limits.hints.description'));

        // Is Active
        CRUD::field('is_active')
            ->label(__('entity_limits.fields.is_active'))
            ->type('boolean')
            ->default(true)
            ->hint(__('entity_limits.hints.is_active'));
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Helper method to format bytes into human readable format
     */
    private function formatBytes(float|int $bytes, int $precision = 2): string
    {
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
