<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DatabaseMaintenanceLogRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Database Maintenance Log CRUD Controller
 */
class DatabaseMaintenanceLogCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\DatabaseMaintenanceLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/database-maintenance-log');
        CRUD::setEntityNameStrings(
            __('admin.database.maintenance_log'), 
            __('admin.database.maintenance_logs')
        );
        
        // Only allow viewing and showing logs, no create/edit/delete
        CRUD::denyAccess(['create', 'update', 'delete']);
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation()
    {
        CRUD::column('task_type')
            ->label(__('admin.database.task_type'))
            ->type('text');
            
        CRUD::column('table_name')
            ->label(__('admin.database.table_name'))
            ->type('text');
            
        CRUD::column('status')
            ->label(__('admin.database.status'))
            ->type('badge')
            ->options([
                'pending' => 'secondary',
                'running' => 'warning', 
                'completed' => 'success',
                'failed' => 'danger'
            ]);
            
        CRUD::column('started_at')
            ->label(__('admin.database.started_at'))
            ->type('datetime');
            
        CRUD::column('completed_at')
            ->label(__('admin.database.completed_at'))
            ->type('datetime');
            
        // Calculate duration from timestamps
        CRUD::column('duration')
            ->label(__('admin.database.duration'))
            ->type('closure')
            ->function(function($entry) {
                if ($entry->started_at && $entry->completed_at) {
                    $start = strtotime($entry->started_at);
                    $end = strtotime($entry->completed_at);
                    $duration = $end - $start;
                    
                    if ($duration < 60) {
                        return $duration . ' sec';
                    } else {
                        $minutes = floor($duration / 60);
                        $seconds = $duration % 60;
                        return $minutes . 'm ' . $seconds . 's';
                    }
                }
                return $entry->status === 'running' ? __('admin.database.running') : '-';
            });
            
        CRUD::column('description')
            ->label(__('admin.database.description'))
            ->type('text')
            ->limit(100);
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation()
    {
        CRUD::column('task_type')
            ->label(__('admin.database.task_type'))
            ->type('text');
            
        CRUD::column('table_name')
            ->label(__('admin.database.table_name'))
            ->type('text');
            
        CRUD::column('status')
            ->label(__('admin.database.status'))
            ->type('badge')
            ->options([
                'pending' => 'secondary',
                'running' => 'warning',
                'completed' => 'success', 
                'failed' => 'danger'
            ]);
            
        CRUD::column('description')
            ->label(__('admin.database.description'))
            ->type('textarea');
            
        CRUD::column('started_at')
            ->label(__('admin.database.started_at'))
            ->type('datetime');
            
        CRUD::column('completed_at')
            ->label(__('admin.database.completed_at'))
            ->type('datetime');
            
        // Calculate and display duration
        CRUD::column('duration')
            ->label(__('admin.database.duration'))
            ->type('closure')
            ->function(function($entry) {
                if ($entry->started_at && $entry->completed_at) {
                    $start = strtotime($entry->started_at);
                    $end = strtotime($entry->completed_at);
                    $duration = $end - $start;
                    
                    if ($duration < 60) {
                        return $duration . ' seconds';
                    } elseif ($duration < 3600) {
                        $minutes = floor($duration / 60);
                        $seconds = $duration % 60;
                        return $minutes . ' minutes ' . $seconds . ' seconds';
                    } else {
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                        return $hours . ' hours ' . $minutes . ' minutes';
                    }
                }
                return $entry->status === 'running' ? __('admin.database.running') : __('admin.database.not_available');
            });
            
        // Display results as formatted JSON
        CRUD::column('results')
            ->label(__('admin.database.results'))
            ->type('closure')
            ->function(function($entry) {
                if (empty($entry->results)) {
                    return '<span class="text-muted">' . __('admin.database.no_results') . '</span>';
                }
                
                // Decode JSON if it's a string
                $results = is_string($entry->results) ? json_decode($entry->results, true) : $entry->results;
                
                if (!$results) {
                    return '<pre class="text-muted">' . htmlspecialchars($entry->results) . '</pre>';
                }
                
                // Format based on task type
                return $this->formatTaskResults($entry->task_type, $results);
            })
            ->escaped(false); // Allow HTML for formatting
            
        CRUD::column('created_at')
            ->label(__('admin.database.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(__('admin.database.updated_at'))
            ->type('datetime');
    }

    /**
     * Format task results based on task type
     */
    private function formatTaskResults($taskType, $results)
    {
        switch ($taskType) {
            case 'optimize':
                return $this->formatOptimizeResults($results);
            case 'archive':
                return $this->formatArchiveResults($results);
            case 'monitor':
                return $this->formatMonitorResults($results);
            default:
                return $this->formatGenericResults($results);
        }
    }

    /**
     * Format optimize task results
     */
    private function formatOptimizeResults($results)
    {
        $html = '<div class="maintenance-results">';
        
        // Before optimization stats
        if (isset($results['before'])) {
            $html .= '<div class="mb-3">';
            $html .= '<h6 class="text-primary">' . __('admin.database.before_optimization') . '</h6>';
            $html .= '<div class="row text-sm">';
            $html .= '<div class="col-md-6">';
            $html .= '<strong>' . __('admin.database.row_count') . ':</strong> ' . number_format($results['before']['row_count'] ?? 0) . '<br>';
            $html .= '<strong>' . __('admin.database.total_size') . ':</strong> ' . ($results['before']['size_mb'] ?? '0') . ' MB<br>';
            $html .= '<strong>' . __('admin.database.data_size') . ':</strong> ' . ($results['before']['data_size_mb'] ?? '0') . ' MB<br>';
            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            $html .= '<strong>' . __('admin.database.index_size') . ':</strong> ' . ($results['before']['index_size_mb'] ?? '0') . ' MB<br>';
            $html .= '<strong>' . __('admin.database.engine') . ':</strong> ' . ($results['before']['storage_engine'] ?? 'N/A') . '<br>';
            $html .= '<strong>' . __('admin.database.data_free') . ':</strong> ' . number_format($results['before']['data_free'] ?? 0) . ' bytes<br>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Optimization process
        if (isset($results['optimize']) && is_array($results['optimize'])) {
            $html .= '<div class="mb-3">';
            $html .= '<h6 class="text-info">' . __('admin.database.optimization_process') . '</h6>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-sm table-striped">';
            $html .= '<thead><tr><th>' . __('admin.database.operation') . '</th><th>' . __('admin.database.message_type') . '</th><th>' . __('admin.database.message') . '</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($results['optimize'] as $operation) {
                $html .= '<tr>';
                $html .= '<td>' . ($operation['Op'] ?? '') . '</td>';
                $html .= '<td><span class="badge badge-' . ($operation['Msg_type'] === 'status' ? 'success' : 'info') . '">' . ($operation['Msg_type'] ?? '') . '</span></td>';
                $html .= '<td>' . ($operation['Msg_text'] ?? '') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // After optimization stats
        if (isset($results['after'])) {
            $html .= '<div class="mb-3">';
            $html .= '<h6 class="text-success">' . __('admin.database.after_optimization') . '</h6>';
            $html .= '<div class="row text-sm">';
            $html .= '<div class="col-md-6">';
            $html .= '<strong>' . __('admin.database.row_count') . ':</strong> ' . number_format($results['after']['row_count'] ?? 0) . '<br>';
            $html .= '<strong>' . __('admin.database.total_size') . ':</strong> ' . ($results['after']['size_mb'] ?? '0') . ' MB<br>';
            $html .= '<strong>' . __('admin.database.data_size') . ':</strong> ' . ($results['after']['data_size_mb'] ?? '0') . ' MB<br>';
            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            $html .= '<strong>' . __('admin.database.index_size') . ':</strong> ' . ($results['after']['index_size_mb'] ?? '0') . ' MB<br>';
            $html .= '<strong>' . __('admin.database.engine') . ':</strong> ' . ($results['after']['storage_engine'] ?? 'N/A') . '<br>';
            $html .= '<strong>' . __('admin.database.data_free') . ':</strong> ' . number_format($results['after']['data_free'] ?? 0) . ' bytes<br>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Calculate improvement if both before and after are available
        if (isset($results['before']) && isset($results['after'])) {
            $sizeBefore = floatval($results['before']['size_mb'] ?? 0);
            $sizeAfter = floatval($results['after']['size_mb'] ?? 0);
            $sizeSaved = $sizeBefore - $sizeAfter;
            
            if ($sizeSaved > 0) {
                $percentSaved = ($sizeSaved / $sizeBefore) * 100;
                $html .= '<div class="alert alert-success">';
                $html .= '<strong>' . __('admin.database.space_saved') . ':</strong> ' . number_format($sizeSaved, 2) . ' MB (' . number_format($percentSaved, 1) . '%)';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Format archive task results
     */
    private function formatArchiveResults($results)
    {
        $html = '<div class="maintenance-results">';
        $html .= '<h6 class="text-warning">' . __('admin.database.archive_results') . '</h6>';
        
        if (isset($results['archived_records'])) {
            $html .= '<p><strong>' . __('admin.database.archived_records') . ':</strong> ' . number_format($results['archived_records']) . '</p>';
        }
        
        if (isset($results['archive_location'])) {
            $html .= '<p><strong>' . __('admin.database.archive_location') . ':</strong> ' . $results['archive_location'] . '</p>';
        }
        
        $html .= '<pre class="bg-light p-3">' . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Format monitor task results
     */
    private function formatMonitorResults($results)
    {
        $html = '<div class="maintenance-results">';
        $html .= '<h6 class="text-info">' . __('admin.database.monitor_results') . '</h6>';
        
        if (isset($results['metrics'])) {
            $html .= '<div class="row">';
            foreach ($results['metrics'] as $metric => $value) {
                $html .= '<div class="col-md-6 mb-2">';
                $html .= '<strong>' . ucfirst(str_replace('_', ' ', $metric)) . ':</strong> ' . $value;
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '<pre class="bg-light p-3 mt-3">' . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Format generic task results
     */
    private function formatGenericResults($results)
    {
        $html = '<div class="maintenance-results">';
        $html .= '<h6 class="text-secondary">' . __('admin.database.task_results') . '</h6>';
        $html .= '<pre class="bg-light p-3">' . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        $html .= '</div>';
        return $html;
    }
}
