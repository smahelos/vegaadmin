<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PerformanceMetricRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Performance Metrics CRUD Controller
 */
class PerformanceMetricCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PerformanceMetric::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/performance-metric');
        CRUD::setEntityNameStrings(
            __('admin.database.performance_metric'), 
            __('admin.database.performance_metrics')
        );
        
        // Only allow viewing and showing metrics, no create/edit/delete
        CRUD::denyAccess(['create', 'update', 'delete']);
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation()
    {
        CRUD::column('metric_type')
            ->label(__('admin.database.metric_type'))
            ->type('text');
            
        CRUD::column('table_name')
            ->label(__('admin.database.table_name'))
            ->type('text');
            
        CRUD::column('metric_value')
            ->label(__('admin.database.metric_value'))
            ->type('number')
            ->decimals(4);
            
        CRUD::column('metric_unit')
            ->label(__('admin.database.metric_unit'))
            ->type('text');
            
        CRUD::column('metadata')
            ->label(__('admin.database.metadata'))
            ->type('closure')
            ->function(function($entry) {
                if (empty($entry->metadata)) {
                    return '<span class="text-muted">N/A</span>';
                }
                
                // Decode JSON if it's a string
                $metadata = is_string($entry->metadata) ? json_decode($entry->metadata, true) : $entry->metadata;
                
                if (!$metadata || !is_array($metadata)) {
                    return '<span class="text-muted">Invalid</span>';
                }
                
                // Show key metrics in a compact format
                $html = '<div class="metadata-compact">';
                $count = 0;
                foreach ($metadata as $key => $value) {
                    if ($count >= 2) break; // Limit to 2 items in list view
                    
                    $formattedKey = $this->formatMetadataKey($key);
                    $formattedValue = $this->formatMetadataValue($key, $value);
                    
                    $html .= '<small class="d-block">';
                    $html .= '<strong>' . htmlspecialchars($formattedKey) . ':</strong> ';
                    $html .= strip_tags($formattedValue);
                    $html .= '</small>';
                    $count++;
                }
                
                if (count($metadata) > 2) {
                    $html .= '<small class="text-muted">... +' . (count($metadata) - 2) . ' more</small>';
                }
                
                $html .= '</div>';
                return $html;
            })
            ->escaped(false);
            
        CRUD::column('measured_at')
            ->label(__('admin.database.measured_at'))
            ->type('datetime');
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::column('query_type')
            ->label(__('admin.database.query_type'))
            ->type('text');
        
        CRUD::column('metadata')
            ->label(__('admin.database.metadata'))
            ->type('closure')
            ->function(function($entry) {
                if (empty($entry->metadata)) {
                    return '<span class="text-muted">N/A</span>';
                }
                
                // Decode JSON if it's a string
                $metadata = is_string($entry->metadata) ? json_decode($entry->metadata, true) : $entry->metadata;
                
                if (!$metadata || !is_array($metadata)) {
                    return '<pre class="bg-light p-2 text-muted">' . htmlspecialchars($entry->metadata) . '</pre>';
                }
                
                // Create a beautiful table for metadata display
                $html = '<div class="metadata-display">';
                $html .= '<table class="table table-sm table-bordered">';
                $html .= '<thead class="table-light">';
                $html .= '<tr><th>' . __('admin.database.metadata_key') . '</th><th>' . __('admin.database.metadata_value') . '</th></tr>';
                $html .= '</thead>';
                $html .= '<tbody>';
                
                foreach ($metadata as $key => $value) {
                    $formattedKey = $this->formatMetadataKey($key);
                    $formattedValue = $this->formatMetadataValue($key, $value);
                    
                    $html .= '<tr>';
                    $html .= '<td><strong>' . htmlspecialchars($formattedKey) . '</strong></td>';
                    $html .= '<td>' . $formattedValue . '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
                
                return $html;
            })
            ->escaped(false);
            
        CRUD::column('created_at')
            ->label(__('admin.database.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(__('admin.database.updated_at'))
            ->type('datetime');
    }
    
    /**
     * Format metadata key for display
     */
    private function formatMetadataKey($key)
    {
        // Convert snake_case to readable format
        $formatted = str_replace('_', ' ', $key);
        $formatted = ucwords($formatted);
        
        // Special cases for common metrics
        $specialCases = [
            'Size Before Mb' => __('admin.database.size_before_mb'),
            'Size After Mb' => __('admin.database.size_after_mb'),
            'Execution Time Ms' => __('admin.database.execution_time_ms'),
            'Memory Usage Mb' => __('admin.database.memory_usage_mb'),
            'Rows Affected' => __('admin.database.rows_affected'),
            'Query Count' => __('admin.database.query_count'),
        ];
        
        return $specialCases[$formatted] ?? $formatted;
    }
    
    /**
     * Format metadata value for display
     */
    private function formatMetadataValue($key, $value)
    {
        // Handle different value types
        if (is_null($value)) {
            return '<span class="text-muted">N/A</span>';
        }
        
        if (is_bool($value)) {
            return $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
        }
        
        if (is_numeric($value)) {
            // Format size values
            if (strpos($key, 'size') !== false && strpos($key, 'mb') !== false) {
                return number_format((float)$value, 2) . ' <small class="text-muted">MB</small>';
            }
            
            // Format time values
            if (strpos($key, 'time') !== false && strpos($key, 'ms') !== false) {
                return number_format((float)$value, 2) . ' <small class="text-muted">ms</small>';
            }
            
            // Format memory values
            if (strpos($key, 'memory') !== false && strpos($key, 'mb') !== false) {
                return number_format((float)$value, 2) . ' <small class="text-muted">MB</small>';
            }
            
            // Default numeric formatting
            return number_format((float)$value, 2);
        }
        
        if (is_array($value)) {
            return '<code>' . json_encode($value, JSON_UNESCAPED_UNICODE) . '</code>';
        }
        
        return htmlspecialchars($value);
    }
}
