<?php

namespace App\Http\Controllers\Admin;

use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EntityUsageCrudController
 * Universal Entity Limit System Usage CRUD Controller - Permission-based System
 *
 * @property-read CrudPanel $crud
 */
class EntityUsageCrudController extends CrudController
{
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;

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

        CRUD::setModel(EntityLimitUsage::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/entity-usage',
        );
        CRUD::setEntityNameStrings(
            __('entity_limits.usages.entity.singular'),
            __('entity_limits.usages.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        // Add filters
        $filter = CRUD::filter('user_id');
        $filter->type('select2');
        $filter->label(
            __('entity_limits.usages.fields.user_id'),
        );
        $userNames = User::pluck('name', 'id');
        $filter->values($userNames->toArray());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            },
        );

        $filter = CRUD::filter('entity_type');
        $filter->type('select2');
        $filter->label(
            __('entity_limits.usages.fields.entity_type'),
        );
        $entityTypes = collect(EntityLimit::ENTITY_TYPES);
        $entityTypeLabels = $entityTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")],
        );
        $filter->values($entityTypeLabels->all());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'entity_type',
                    $value,
                );
            },
        );

        $filter = CRUD::filter('metric_type');
        $filter->type('select2');
        $filter->label(
            __('entity_limits.usages.fields.metric_type'),
        );
        $metricTypes = collect(EntityLimit::METRIC_TYPES);
        $metricTypes = $metricTypes->keys();
        $metricTypeLabels = $metricTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.metric_types.$type")],
        );
        $filter->values($metricTypeLabels->all());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'metric_type',
                    $value,
                );
            },
        );

        $filter = CRUD::filter('period_type');
        $filter->type('select2');
        $filter->label(
            __('entity_limits.usages.fields.period_type'),
        );
        $periodTypes = collect(EntityLimit::PERIOD_TYPES);
        $periodTypes = $periodTypes->keys();
        $periodTypeLabels = $periodTypes->mapWithKeys(
            fn ($type) => [$type => __("entity_limits.periods.$type")],
        );
        $filter->values($periodTypeLabels->all());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'period_type',
                    $value,
                );
            },
        );

        CRUD::addFilter(
            [
                'type' => 'datepicker_range',
                'name' => 'period_start',
                'label' => __('entity_limits.usages.filters.period_range'),
            ],
            false,
            function ($value): void {
                $dates = json_decode($value);
                CRUD::addClause(
                    'where',
                    'period_start',
                    '>=',
                    $dates->from,
                );
                CRUD::addClause(
                    'where',
                    'period_start',
                    '<=',
                    $dates->to,
                );
            },
        );

        // Define columns for list view
        $column = CRUD::column('user');
        $column->label(
            __('entity_limits.usages.fields.user_id'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry->user_id) {
                    if ($entry->user) {
                        $label = $entry->user->name . ' (' . $entry->user->email . ')';
                    } else {
                        $label = __('entity_limits.usages.labels.unknown_user', ['id' => $entry->user_id]);
                    }

                    return e($label);
                } else {
                    return '<span class="text-muted">' . e(
                        __('entity_limits.usages.labels.anonymous'),
                    ) . '</span>';
                }
            },
        );
        $column->escaped(false);
        $column->searchLogic(
            function (
                $query,
                $column,
                $searchTerm,
            ): void {
                $query->orWhereHas(
                    'user',
                    function ($q) use ($searchTerm): void {
                        $q->where(
                            'name',
                            'like',
                            '%' . $searchTerm . '%',
                        );
                        $q->orWhere(
                            'email',
                            'like',
                            '%' . $searchTerm . '%',
                        );
                    },
                );
            },
        );

        $column = CRUD::column('entity_type');
        $column->label(
            __('entity_limits.usages.fields.entity_type'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                return '<span class="badge text-bg-secondary">' . e(
                    __("entity_limits.entity_types.{$entry->entity_type}.singular"),
                ) . '</span>';
            },
        );
        $column->escaped(false);

        $column = CRUD::column('metric_type');
        $column->label(
            __('entity_limits.usages.fields.metric_type'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                return '<span class="badge text-bg-light">' . e(
                    __("entity_limits.metric_types.{$entry->metric_type}"),
                ) . '</span>';
            },
        );
        $column->escaped(false);

        $column = CRUD::column('period_type');
        $column->label(
            __('entity_limits.usages.fields.period_type'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                return '<span class="badge text-bg-info">' . e(
                    __("entity_limits.periods.{$entry->period_type}"),
                ) . '</span>';
            },
        );
        $column->escaped(false);

        $column = CRUD::column('period_start');
        $column->label(
            __('entity_limits.usages.fields.period_start'),
        );
        $column->type('date');

        $column = CRUD::column('period_end');
        $column->label(
            __('entity_limits.usages.fields.period_end'),
        );
        $column->type('date');

        $column = CRUD::column('current_value');
        $column->label(
            __('entity_limits.usages.fields.current_value'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->current_value, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->current_value);
                } else {
                    return number_format($entry->current_value);
                }
            },
        );
        $column->default('0');

        $column = CRUD::column('last_reset_at');
        $column->label(
            __('entity_limits.usages.fields.last_reset_at'),
        );
        $column->type('datetime');

        // Set default order
        CRUD::orderBy('last_reset_at', 'desc');
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        // Add additional details for show view
        $column = CRUD::column('created_at');
        $column->label(
            __('entity_limits.usages.fields.created_at'),
        );
        $column->type('datetime');

        $column = CRUD::column('updated_at');
        $column->label(
            __('entity_limits.usages.fields.updated_at'),
        );
        $column->type('datetime');
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
