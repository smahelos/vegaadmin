<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EntityUsageRequest;
use App\Models\EntityLimitUsage;
use App\Models\EntityLimit;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Prologue\Alerts\Facades\Alert;

/**
 * Class EntityUsageCrudController
 * Universal Entity Limit System Usage CRUD Controller - Permission-based System
 * 
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EntityUsageCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

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
        
        CRUD::setModel(EntityLimitUsage::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/entity-usage');
        CRUD::setEntityNameStrings(__('entity_limits.usages.entity.singular'), __('entity_limits.usages.entity.plural'));
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label(__('entity_limits.usages.fields.user_id'))
            ->values(
                User::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'user_id', $value);
            });

        CRUD::filter('entity_type')
            ->type('select2')
            ->label(__('entity_limits.usages.fields.entity_type'))
            ->values(
                collect(EntityLimit::ENTITY_TYPES)->mapWithKeys(fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")])->all()
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'entity_type', $value);
            });


        CRUD::filter('metric_type')
            ->type('select2')
            ->label(__('entity_limits.usages.fields.metric_type'))
            ->values(collect(EntityLimit::METRIC_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.metric_types.$type")])->all())
            ->whenActive(function($value) {
                CRUD::addClause('where', 'metric_type', $value);
            });

        CRUD::filter('period_type')
            ->type('select2')
            ->label(__('entity_limits.usages.fields.period_type'))
            ->values(collect(EntityLimit::PERIOD_TYPES)->keys()->mapWithKeys(fn ($type) => [$type => __("entity_limits.periods.$type")])->all())
            ->whenActive(function($value) {
                CRUD::addClause('where', 'period_type', $value);
        });

        CRUD::addFilter([
            'type' => 'datepicker_range',
            'name' => 'period_start',
            'label' => __('entity_limits.usages.filters.period_range'),
        ], false, function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'period_start', '>=', $dates->from);
            CRUD::addClause('where', 'period_start', '<=', $dates->to);
        });

        // Define columns for list view
        CRUD::column('user')
            ->label(__('entity_limits.usages.fields.user_id'))
            ->type('closure')
            ->function(function($entry) {
                if ($entry->user_id) {
                    return e($entry->user ? $entry->user->name . ' (' . $entry->user->email . ')' : __('entity_limits.usages.labels.unknown_user', ['id' => $entry->user_id]));
                } else {
                    return '<span class="text-muted">' . e(__('entity_limits.usages.labels.anonymous')) . '</span>';
                }
            })
            ->escaped(false)
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                      ->orWhere('email', 'like', '%'.$searchTerm.'%');
                });
            });

        CRUD::column('entity_type')
            ->label(__('entity_limits.usages.fields.entity_type'))
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge text-bg-secondary">' . e(__("entity_limits.entity_types.{$entry->entity_type}.singular")) . '</span>';
            })
            ->escaped(false);

        CRUD::column('metric_type')
            ->label(__('entity_limits.usages.fields.metric_type'))
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge text-bg-light">' . e(__("entity_limits.metric_types.{$entry->metric_type}")) . '</span>';
            })
            ->escaped(false);

        CRUD::column('period_type')
            ->label(__('entity_limits.usages.fields.period_type'))
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge text-bg-info">' . e(__("entity_limits.periods.{$entry->period_type}")) . '</span>';
            })
            ->escaped(false);
            
        CRUD::column('period_start')
            ->label(__('entity_limits.usages.fields.period_start'))
            ->type('date');

        CRUD::column('period_end')
            ->label(__('entity_limits.usages.fields.period_end'))
            ->type('date');
            
        CRUD::column('current_value')
            ->label(__('entity_limits.usages.fields.current_value'))
            ->type('closure')
            ->function(function($entry) {
                if ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->current_value, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->current_value);
                } else {
                    return number_format($entry->current_value);
                }
            })
            ->default('0');

        CRUD::column('last_reset_at')
            ->label(__('entity_limits.usages.fields.last_reset_at'))
            ->type('datetime');

        // Set default order
        CRUD::orderBy('last_reset_at', 'desc');
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        // Add additional details for show view
        CRUD::column('created_at')
            ->label(__('entity_limits.usages.fields.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(__('entity_limits.usages.fields.updated_at'))
            ->type('datetime');
    }

    /**
     * Reset usage for selected entity type and period
     */
    public function resetUsage(Request $request)
    {
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'entity_type' => 'required|in:' . implode(',', \App\Models\EntityLimit::ENTITY_TYPES),
            'metric_type' => 'required|in:' . implode(',', array_keys(\App\Models\EntityLimit::METRIC_TYPES)),
            'period_type' => 'required|in:' . implode(',', array_keys(\App\Models\EntityLimit::PERIOD_TYPES)),
        ]);

        $limitService = app(UniversalLimitService::class);
        $user = $request->user_id ? User::findOrFail($request->user_id) : null;
        
        // Reset usage for specific entity type, metric type and period
        $result = $limitService->resetUsage($user, $request->entity_type, $request->metric_type, $request->period_type);
        
        if ($result) {
            $userLabel = $user ? $user->name : 'Anonymous';
            Alert::success("Usage has been reset successfully for {$userLabel}.")->flash();
        } else {
            Alert::error('Failed to reset usage.')->flash();
        }

        return redirect()->back();
    }

    /**
     * Bulk reset usage for multiple users
     */
    public function bulkResetUsage(Request $request)
    {
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'entity_type' => 'required|in:' . implode(',', \App\Models\EntityLimit::ENTITY_TYPES),
            'metric_type' => 'required|in:' . implode(',', array_keys(\App\Models\EntityLimit::METRIC_TYPES)),
            'period_type' => 'required|in:' . implode(',', array_keys(\App\Models\EntityLimit::PERIOD_TYPES)),
            'include_anonymous' => 'boolean',
        ]);

        $limitService = app(UniversalLimitService::class);
        $successCount = 0;
        
        // Reset for specific users if provided
        if (!empty($request->user_ids)) {
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                if ($user && $limitService->resetUsage($user, $request->entity_type, $request->metric_type, $request->period_type)) {
                    $successCount++;
                }
            }
        }
        
        // Reset for anonymous users if requested
        if ($request->include_anonymous) {
            if ($limitService->resetUsage(null, $request->entity_type, $request->metric_type, $request->period_type)) {
                $successCount++;
            }
        }
        
        Alert::success("Successfully reset usage for {$successCount} users/sessions.")->flash();
        
        return redirect()->back();
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
