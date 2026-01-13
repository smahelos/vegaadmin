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
        CRUD::setEntityNameStrings('entity usage', 'entity usages');
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
            ->label('User')
            ->values(
                User::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'user_id', $value);
            });

        CRUD::filter('entity_type')
            ->type('select2')
            ->label('Entity Type')
            ->values(
                array_combine(EntityLimit::ENTITY_TYPES, array_map('ucfirst', EntityLimit::ENTITY_TYPES))
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'entity_type', $value);
            });

        // CRUD::addFilter([
        //     'type' => 'dropdown',
        //     'name' => 'entity_type',
        //     'label' => 'Entity Type',
        // ], array_combine(\App\Models\EntityLimit::ENTITY_TYPES, array_map('ucfirst', \App\Models\EntityLimit::ENTITY_TYPES)), function($value) {
        //     CRUD::addClause('where', 'entity_type', $value);
        // });

        CRUD::filter('metric_type')
            ->type('select2')
            ->label('Metric Type')
            ->values(EntityLimit::METRIC_TYPES)
            ->whenActive(function($value) {
                CRUD::addClause('where', 'metric_type', $value);
            });

        CRUD::filter('period_type')
            ->type('select2')
            ->label('Period')
            ->values(EntityLimit::PERIOD_TYPES)
            ->whenActive(function($value) {
                CRUD::addClause('where', 'period_type', $value);
        });

        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'period_start',
            'label' => 'Period Range',
        ], false, function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'period_start', '>=', $dates->from);
            CRUD::addClause('where', 'period_start', '<=', $dates->to);
        });

        // Define columns for list view
        CRUD::column('user')
            ->label('User')
            ->type('closure')
            ->function(function($entry) {
                if ($entry->user_id) {
                    return $entry->user ? $entry->user->name . ' (' . $entry->user->email . ')' : 'Unknown User (ID: ' . $entry->user_id . ')';
                } else {
                    return '<span class="text-muted">Anonymous</span>';
                }
            })
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                      ->orWhere('email', 'like', '%'.$searchTerm.'%');
                });
            });

        CRUD::column('entity_type')
            ->label('Entity Type')
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge badge-secondary">' . ucfirst($entry->entity_type) . '</span>';
            });

        CRUD::column('metric_type')
            ->label('Metric Type')
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge badge-light">' . ucfirst($entry->metric_type) . '</span>';
            });

        CRUD::column('period_type')
            ->label('Period Type')
            ->type('closure')
            ->function(function($entry) {
                return '<span class="badge badge-info">' . ucfirst($entry->period_type) . '</span>';
            });
            
        CRUD::column('period_start')
            ->label('Period Start')
            ->type('date');

        CRUD::column('period_end')
            ->label('Period End')
            ->type('date');
            
        CRUD::column('current_value')
            ->label('Current Value')
            ->type('closure')
            ->function(function($entry) {
                if ($entry->metric_type === 'value') {
                    return '€' . number_format($entry->current_value, 2);
                } elseif ($entry->metric_type === 'size') {
                    return $this->formatBytes($entry->current_value);
                } else {
                    return number_format($entry->current_value);
                }
            });

        CRUD::column('last_reset_at')
            ->label('Last Reset')
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
            ->label('Created At')
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label('Updated At')
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
