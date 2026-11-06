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
        $user = backpack_user();
        if (!$user || !$user->hasPermissionTo('can_configure_system', 'backpack')) {
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
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/entity-limit',
        );
        CRUD::setEntityNameStrings(
            __('entity_limits.entity.singular'),
            __('entity_limits.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        // Define columns for list view
        $column = CRUD::column('permission_name');
        $column->label(
            __('entity_limits.fields.permission_name'),
        );
        $column->type('text');

        $column = CRUD::column('entity_type');
        $column->label(
            __('entity_limits.fields.entity_type'),
        );
        $column->type('select_from_array');
        $entityTypes = collect(EntityLimit::ENTITY_TYPES);
        $entityTypeLabels = $entityTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")],
        );
        $column->options($entityTypeLabels->all());

        $column = CRUD::column('metric_type');
        $column->label(
            __('entity_limits.fields.metric_type'),
        );
        $column->type('select_from_array');
        $metricTypes = collect(EntityLimit::METRIC_TYPES);
        $metricTypes = $metricTypes->keys();
        $metricTypeLabels = $metricTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.metric_types.$type")],
        );
        $column->options($metricTypeLabels->all());

        $column = CRUD::column('period_type');
        $column->label(
            __('entity_limits.fields.period_type'),
        );
        $column->type('select_from_array');
        $periodTypes = collect(EntityLimit::PERIOD_TYPES);
        $periodTypes = $periodTypes->keys();
        $periodTypeLabels = $periodTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.periods.$type")],
        );
        $column->options($periodTypeLabels->all());

        $column = CRUD::column('limit_value');
        $column->label(
            __('entity_limits.fields.limit_value'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry->metric_type === 'count') {
                    return number_format($entry->limit_value ?? 0);
                } elseif ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->limit_value ?? 0, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->limit_value ?? 0);
                }

                return __('general.placeholders.not_available');
            },
        );
        $column->default('0');

        $column = CRUD::column('is_active');
        $column->label(
            __('entity_limits.fields.is_active'),
        );
        $column->type('boolean');
        $column->options(
            [0 => __('general.no'), 1 => __('general.yes')],
        );

        $column = CRUD::column('description');
        $column->label(
            __('entity_limits.fields.description'),
        );
        $column->type('text');
        $column->limit(100);

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
        $permissionNames = Permission::pluck('name', 'name');
        $permissions = $permissionNames->toArray();
        $permissions[EntityLimit::ANONYMOUS_PERMISSION] = __('entity_limits.labels.anonymous_users');

        $field = CRUD::field('permission_name');
        $field->label(
            __('entity_limits.fields.permission_name'),
        );
        $field->type('select_from_array');
        $field->options($permissions);
        $field->allows_null(false);
        $field->hint(
            __('entity_limits.hints.permission_name'),
        );

        // Entity Type
        $field = CRUD::field('entity_type');
        $field->label(
            __('entity_limits.fields.entity_type'),
        );
        $field->type('select_from_array');
        $entityTypes = collect(EntityLimit::ENTITY_TYPES);
        $entityTypeLabels = $entityTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")],
        );
        $field->options($entityTypeLabels->all());
        $field->allows_null(false);

        // Metric Type
        $field = CRUD::field('metric_type');
        $field->label(
            __('entity_limits.fields.metric_type'),
        );
        $field->type('select_from_array');
        $metricTypes = collect(EntityLimit::METRIC_TYPES);
        $metricTypes = $metricTypes->keys();
        $metricTypeLabels = $metricTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.metric_types.$type")],
        );
        $field->options($metricTypeLabels->all());
        $field->allows_null(false);
        $field->default('count');

        // Period Type
        $field = CRUD::field('period_type');
        $field->label(
            __('entity_limits.fields.period_type'),
        );
        $field->type('select_from_array');
        $periodTypes = collect(EntityLimit::PERIOD_TYPES);
        $periodTypes = $periodTypes->keys();
        $periodTypeLabels = $periodTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.periods.$type")],
        );
        $field->options($periodTypeLabels->all());
        $field->allows_null(false);
        $field->default('monthly');

        // Limit Value
        $field = CRUD::field('limit_value');
        $field->label(
            __('entity_limits.fields.limit_value'),
        );
        $field->type('number');
        $field->attributes(['min' => 0, 'step' => 0.01]);
        $field->hint(
            __('entity_limits.hints.limit_value'),
        );

        // Description
        $field = CRUD::field('description');
        $field->label(
            __('entity_limits.fields.description'),
        );
        $field->type('textarea');
        $field->attributes(['rows' => 3]);
        $field->hint(
            __('entity_limits.hints.description'),
        );

        // Is Active
        $field = CRUD::field('is_active');
        $field->label(
            __('entity_limits.fields.is_active'),
        );
        $field->type('boolean');
        $field->default(true);
        $field->hint(
            __('entity_limits.hints.is_active'),
        );
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
