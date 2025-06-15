/**
 * Database Dashboard JavaScript
 * Handles all database management dashboard functionality
 */

// Global variables
let performanceChart = null;

/**
 * Initialize dashboard when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

/**
 * Main dashboard initialization
 */
function initializeDashboard() {
    initializeChart();
    cleanupModalBackdrops();
    initializeTranslations();
}

/**
 * Initialize translations object for use in JavaScript
 */
function initializeTranslations() {
    // Translations will be populated by the Blade template
    if (typeof window.translations === 'undefined') {
        window.translations = {};
    }
}

/**
 * Initialize performance chart
 */
function initializeChart() {
    const ctx = document.getElementById('performanceChart');
    
    if (!ctx) {
        console.error('Performance chart canvas not found');
        return;
    }
    
    // Get initial chart data from global variables set by Blade template
    if (typeof window.chartData === 'undefined') {
        console.warn('Chart data not available');
        return;
    }
    
    const {
        labels,
        data,
        metricType,
        metricUnit,
        metricLabel
    } = window.chartData;
    
    console.log('Initial chart data:', { 
        labels: labels.length, 
        data: data.length, 
        type: metricType, 
        unit: metricUnit 
    });
    
    createChart(ctx, labels, data, metricLabel, metricUnit);
}

/**
 * Create or update chart
 */
function createChart(ctx, labels, data, metricLabel, unit) {
    // Destroy existing chart if it exists
    if (performanceChart) {
        performanceChart.destroy();
    }

    if (labels.length > 0 && data.length > 0) {
        try {
            performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: `${metricLabel} (${unit})`,
                        data: data,
                        borderColor: getChartColor(metricLabel),
                        backgroundColor: getChartColor(metricLabel, 0.1),
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: getChartColor(metricLabel),
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    devicePixelRatio: 1,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: `${metricLabel} - ${trans('admin.database.performance_trends')}`,
                            padding: {
                                top: 10,
                                bottom: 30
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: `${trans('admin.database.value')} (${unit})`
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                maxTicksLimit: 8
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: trans('admin.database.date')
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 10,
                                maxRotation: 45
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        point: {
                            hoverRadius: 6
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            right: 20,
                            bottom: 20,
                            left: 20
                        }
                    }
                }
            });
            
            console.log('Chart created successfully');
        } catch (error) {
            console.error('Error creating chart:', error);
            showNoDataMessage(ctx);
        }
    } else {
        console.log('No data available for chart');
        showNoDataMessage(ctx);
    }
}

/**
 * Get chart color based on metric type
 */
function getChartColor(metricLabel, alpha = 1) {
    const colors = {
        'query_time': `rgba(54, 162, 235, ${alpha})`,
        'connection_usage': `rgba(255, 99, 132, ${alpha})`,
        'memory_usage': `rgba(75, 192, 192, ${alpha})`,
        'disk_usage': `rgba(255, 206, 86, ${alpha})`,
        'cache_hit_rate': `rgba(153, 102, 255, ${alpha})`,
        'slow_queries': `rgba(255, 159, 64, ${alpha})`,
        'lock_wait_time': `rgba(199, 199, 199, ${alpha})`,
        'table_size': `rgba(83, 102, 255, ${alpha})`,
        'index_usage': `rgba(40, 167, 69, ${alpha})`
    };

    // Find color by checking if metricLabel contains key
    for (const [key, color] of Object.entries(colors)) {
        if (metricLabel.toLowerCase().includes(key.replace('_', ' ')) || 
            metricLabel.toLowerCase().includes(key)) {
            return color;
        }
    }

    return `rgba(54, 162, 235, ${alpha})`; // Default blue
}

/**
 * Show no data message on canvas
 */
function showNoDataMessage(canvas) {
    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;
    
    // Set canvas size explicitly
    canvas.width = container.offsetWidth || 800;
    canvas.height = 400;
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw no data message
    ctx.fillStyle = '#6c757d';
    ctx.font = '16px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(
        trans('admin.database.no_chart_data'), 
        canvas.width / 2, 
        canvas.height / 2
    );
}

/**
 * Change metric type and refresh chart
 */
function changeMetricType() {
    const metricType = document.getElementById('metricTypeSelector').value;
    const days = document.getElementById('daysSelector').value;
    
    loadChartData(metricType, days);
}

/**
 * Refresh current chart
 */
function refreshChart() {
    const metricType = document.getElementById('metricTypeSelector').value;
    const days = document.getElementById('daysSelector').value;
    
    loadChartData(metricType, days);
}

/**
 * Load chart data via AJAX
 */
function loadChartData(metricType, days) {
    const loading = document.getElementById('chartLoading');
    const ctx = document.getElementById('performanceChart');

    // Show loading indicator
    loading.classList.remove('d-none');

    fetch(getUrl('database-dashboard/run-command'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            command: 'get-trends',
            metric_type: metricType,
            days: parseInt(days)
        })
    })
    .then(response => response.json())
    .then(result => {
        loading.classList.add('d-none');
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update chart
            createChart(ctx, data.labels, data.data, data.metric_label, data.unit);
            
            // Update statistics
            document.getElementById('avgValue').textContent = data.avg_value;
            document.getElementById('currentUnit').textContent = data.unit;
            document.getElementById('currentMetricType').textContent = data.metric_label;
            document.getElementById('dataPointsCount').textContent = data.labels.length;
            
            console.log('Chart updated successfully');
        } else {
            console.error('Failed to load chart data:', result.message);
            showNoDataMessage(ctx);
            
            showNotification('error', result.message || trans('admin.database.failed_to_load_chart'));
        }
    })
    .catch(error => {
        loading.classList.add('d-none');
        console.error('Error loading chart data:', error);
        showNoDataMessage(ctx);
        
        showNotification('error', trans('admin.database.chart_loading_error'));
    });
}

/**
 * Generate sample metrics function
 */
function generateSampleMetrics() {
    if (!confirm(trans('admin.database.confirm_generate_sample'))) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `<i class="la la-spinner la-spin"></i> ${trans('admin.database.generating')}...`;

    fetch(getUrl('database-dashboard/generate-sample-metrics'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            showNotification('success', data.message || trans('admin.database.sample_metrics_generated'));
            
            // Reload page to show new data
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('error', data.message || trans('admin.database.sample_generation_failed'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Error:', error);
        showNotification('error', trans('admin.database.sample_generation_error'));
    });
}

/**
 * Resolve alert function
 */
function resolveAlert(alertId) {
    if (!confirm(trans('admin.database.confirm_resolve_alert'))) {
        return;
    }

    fetch(getUrl('database-dashboard/resolve-alert'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({ alert_id: alertId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', trans('admin.database.alert_resolved'));
            
            // Reload page to update alerts
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message || trans('admin.database.alert_resolve_failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', trans('admin.database.alert_resolve_error'));
    });
}

/**
 * Database command execution
 */
function runDatabaseCommand(command) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `<i class="la la-spinner la-spin"></i> ${trans('admin.database.running')}...`;
    
    let requestData = { command: command };
    
    // Add specific options for health check
    if (command === 'health-check') {
        requestData.store = true;
        requestData.alert = true;
    }
    
    fetch(getUrl('database-dashboard/run-command'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Always restore button
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        // Show output in modal
        document.getElementById('commandOutput').textContent = data.output || data.error;
        
        // Complete cleanup before showing new modal
        cleanupModalBackdrops();
        
        // Find modal element
        const modalElement = document.getElementById('commandOutputModal');
        
        // Create new modal instance with explicit settings
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,          // Allow closing by clicking backdrop
            keyboard: true,          // Allow closing with ESC key
            focus: true,            // Auto focus modal
            show: false             // Don't show automatically
        });
        
        // Event listener for complete cleanup after closing
        modalElement.addEventListener('hidden.bs.modal', function modalCleanupHandler() {
            cleanupModalBackdrops();
            // Remove this listener after use
            modalElement.removeEventListener('hidden.bs.modal', modalCleanupHandler);
        });
        
        // Show modal
        modal.show();
        
        // Show notification
        if (data.success) {
            showNotification('success', data.message || trans('admin.database.command_completed'));
        } else {
            showNotification('error', data.message || trans('admin.database.command_failed'));
        }
    })
    .catch(error => {
        // Always restore button on error
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        console.error('Error:', error);
        showNotification('error', trans('admin.database.command_error'));
    });
}

/**
 * Cleanup modal backdrops helper function
 */
function cleanupModalBackdrops() {
    // Remove all backdrop elements
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.remove();
    });
    
    // Clean body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-left');
    
    // Clean html overflow (sometimes set there too)
    document.documentElement.style.removeProperty('overflow');
}

/**
 * Show cleanup modal
 */
function showCleanupModal() {
    // Load current statistics
    loadMetricsStats();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('cleanupModal'));
    modal.show();
}

/**
 * Load current metrics statistics
 */
function loadMetricsStats() {
    fetch(getUrl('database-dashboard/run-command'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            command: 'metrics-stats'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const stats = result.stats;
            
            document.getElementById('totalMetrics').textContent = stats.total_metrics || 0;
            document.getElementById('sampleMetrics').textContent = stats.sample_metrics || 0;
            document.getElementById('metricTypes').textContent = stats.metrics_by_type?.length || 0;
            
            // Format oldest metric date
            if (stats.oldest_metric) {
                const oldestDate = new Date(stats.oldest_metric);
                const daysAgo = Math.floor((Date.now() - oldestDate.getTime()) / (1000 * 60 * 60 * 24));
                document.getElementById('oldestMetric').textContent = daysAgo + 'd';
            } else {
                document.getElementById('oldestMetric').textContent = '-';
            }
        }
    })
    .catch(error => {
        console.error('Error loading metrics stats:', error);
    });
}

/**
 * Update cleanup description based on selected type
 */
function updateCleanupDescription() {
    const cleanupType = document.getElementById('cleanupType').value;
    const daysSelector = document.getElementById('daysSelector');
    const description = document.getElementById('cleanupDescription');
    
    const descriptions = {
        'old': trans('admin.database.cleanup_old_description'),
        'sample': trans('admin.database.cleanup_sample_description'),
        'duplicate': trans('admin.database.cleanup_duplicate_description'),
        'all': trans('admin.database.cleanup_all_description')
    };
    
    description.textContent = descriptions[cleanupType] || descriptions['old'];
    description.className = cleanupType === 'all' ? 'alert alert-danger' : 'alert alert-info';
    
    // Show/hide days selector
    daysSelector.style.display = cleanupType === 'old' ? 'block' : 'none';
}

/**
 * Execute cleanup operation
 */
function executeCleanup() {
    const cleanupType = document.getElementById('cleanupType').value;
    const days = document.getElementById('cleanupDays').value;
    
    // Confirmation for dangerous operations
    if (cleanupType === 'all') {
        if (!confirm(trans('admin.database.confirm_cleanup_all'))) {
            return;
        }
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `<i class="la la-spinner la-spin"></i> ${trans('admin.database.cleaning')}...`;
    
    fetch(getUrl('database-dashboard/run-command'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            command: 'clean-metrics',
            cleanup_type: cleanupType,
            days: parseInt(days)
        })
    })
    .then(response => response.json())
    .then(result => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (result.success) {
            const message = result.message + (result.deleted_count ? ` (${result.deleted_count} ${trans('admin.database.records_deleted')})` : '');
            showNotification('success', message);
            
            // Close modal and refresh stats
            bootstrap.Modal.getInstance(document.getElementById('cleanupModal')).hide();
            
            // Refresh chart and page data
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification('error', result.message || trans('admin.database.cleanup_failed'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Error during cleanup:', error);
        showNotification('error', trans('admin.database.cleanup_error'));
    });
}

/**
 * Helper function to get translation
 */
function trans(key) {
    return window.translations[key] || key;
}

/**
 * Helper function to get CSRF token
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

/**
 * Helper function to build URL
 */
function getUrl(path) {
    return window.baseUrl + '/' + path;
}

/**
 * Helper function to show notifications
 */
function showNotification(type, message) {
    if (typeof Noty !== 'undefined') {
        new Noty({
            type: type,
            text: message,
            timeout: 4000,
            layout: 'topRight'
        }).show();
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

// Make functions globally available
window.DatabaseDashboard = {
    changeMetricType,
    refreshChart,
    generateSampleMetrics,
    resolveAlert,
    runDatabaseCommand,
    showCleanupModal,
    updateCleanupDescription,
    executeCleanup
};
