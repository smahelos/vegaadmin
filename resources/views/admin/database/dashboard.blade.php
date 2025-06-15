@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
    trans('backpack::crud.admin') => backpack_url('dashboard'),
    __('admin.database.dashboard') => false,
];

// if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-5" bp-section="page-heading">{!! __('admin.database.database_management_dashboard') !!}</h1>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('admin.database.dashboard') }}</h3>
            </div>
            <div class="card-body">

                <!-- Database Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>{{ $databaseStats['total_size_mb'] ?? 0 }} MB</h4>
                                        <small>{{ __('admin.database.total_database_size') }}</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="la la-database fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>{{ $databaseStats['total_tables'] ?? 0 }}</h4>
                                        <small>{{ __('admin.database.total_tables') }}</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="la la-table fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-orange text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>{{ $archiveStats['archived_records'] ?? 0 }}</h4>
                                        <small>{{ __('admin.database.archived_records') }}</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="la la-archive fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>{{ $performanceTrends['avg_query_time'] ?? 0 }}ms</h4>
                                        <small>{{ __('admin.database.avg_query_time') }}</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="la la-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>{{ __('admin.database.maintenance_actions') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-primary btn-block"
                                            onclick="runDatabaseCommand('optimize')">
                                            <i class="la la-cogs"></i> {{ __('admin.database.run_optimization') }}
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-orange btn-block"
                                            onclick="runDatabaseCommand('archive')">
                                            <i class="la la-archive"></i> {{ __('admin.database.run_archive') }}
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-info btn-block"
                                            onclick="runDatabaseCommand('monitor')">
                                            <i class="la la-chart-line"></i> {{ __('admin.database.run_monitoring') }}
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-success btn-block"
                                            onclick="runDatabaseCommand('health-check')">
                                            <i class="la la-heartbeat"></i> {{ __('admin.database.run_health_check') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Maintenance Tasks -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ __('admin.database.recent_tasks') }}</h5>
                                <a href="{{ backpack_url('database-maintenance-log') }}"
                                    class="btn btn-sm btn-outline-primary">
                                    {{ __('admin.database.view_all') }}
                                </a>
                            </div>
                            <div class="card-body p-0">
                                @if($recentTasks->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{ __('admin.database.task_type') }}</th>
                                                <th>{{ __('admin.database.table_name') }}</th>
                                                <th>{{ __('admin.database.status') }}</th>
                                                <th>{{ __('admin.database.space_savings') }}</th>
                                                <th>{{ __('admin.database.duration') }}</th>
                                                <th>{{ __('admin.database.started_at') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentTasks as $task)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-outline-secondary">
                                                        {{ ucfirst($task['task_type']) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <code class="text-white bg-orange">{{ $task['table_name'] }}</code>
                                                </td>
                                                <td>
                                                    @php
                                                    $statusBadge = match($task['status']) {
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'running' => 'warning',
                                                    'pending' => 'info',
                                                    default => 'secondary'
                                                    };
                                                    $statusIcon = match($task['status']) {
                                                    'completed' => 'la-check-circle',
                                                    'failed' => 'la-times-circle',
                                                    'running' => 'la-spinner la-spin',
                                                    'pending' => 'la-clock',
                                                    default => 'la-question-circle'
                                                    };
                                                    @endphp
                                                    <span class="badge bg-{{ $statusBadge }}">
                                                        <i class="la {{ $statusIcon }}"></i>
                                                        {{ ucfirst($task['status']) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($task['space_savings']['amount'] > 0)
                                                    <span class="text-success font-weight-bold">
                                                        <i class="la la-arrow-down"></i>
                                                        {{ $task['space_savings']['formatted'] }}
                                                    </span>
                                                    @else
                                                    <span class="text-muted">
                                                        <i class="la la-minus"></i>
                                                        {{ __('admin.database.no_savings') }}
                                                    </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($task['duration_seconds'] > 0)
                                                    @if($task['duration_seconds'] >= 60)
                                                    {{ floor($task['duration_seconds'] / 60) }}m {{
                                                    $task['duration_seconds'] % 60 }}s
                                                    @else
                                                    {{ $task['duration_seconds'] }}s
                                                    @endif
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($task['started_at'])
                                                    <small>
                                                        {{ $task['started_at']->format('M d, H:i') }}
                                                        <br>
                                                        <span class="text-muted">
                                                            {{ $task['started_at']->diffForHumans() }}
                                                        </span>
                                                    </small>
                                                    @else
                                                    <span class="text-muted">{{ __('admin.database.not_started')
                                                        }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Summary row -->
                                @php
                                $totalSavings = $recentTasks->sum(function($task) {
                                return $task['space_savings']['bytes'];
                                });
                                $completedTasks = $recentTasks->where('status', 'completed')->count();
                                @endphp
                                @if($totalSavings > 0)
                                <div class="border-top p-3 bg-light">
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <small class="text-muted">{{ __('admin.database.total_space_saved')
                                                }}</small>
                                            <div class="font-weight-bold text-success">
                                                @if($totalSavings >= 1024 * 1024 * 1024)
                                                {{ round($totalSavings / (1024 * 1024 * 1024), 2) }} GB
                                                @elseif($totalSavings >= 1024 * 1024)
                                                {{ round($totalSavings / (1024 * 1024), 2) }} MB
                                                @elseif($totalSavings >= 1024)
                                                {{ round($totalSavings / 1024, 2) }} KB
                                                @else
                                                {{ round($totalSavings, 0) }} B
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">{{ __('admin.database.completed_tasks') }}</small>
                                            <div class="font-weight-bold text-primary">
                                                {{ $completedTasks }}/{{ $recentTasks->count() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @else
                                <div class="p-4 text-center text-muted">
                                    <i class="la la-tasks fa-2x mb-2"></i>
                                    <p class="mb-0">{{ __('admin.database.no_recent_tasks') }}</p>
                                    <small>{{ __('admin.database.no_recent_tasks_desc') }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Health Status -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ __('admin.database.health_status') }}</h5>
                                <div>
                                    <a href="{{ backpack_url('database-health-metric') }}"
                                        class="btn btn-sm btn-outline-primary me-2">
                                        {{ __('admin.database.view_all') }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-success"
                                        onclick="runDatabaseCommand('health-check')">
                                        <i class="la la-refresh"></i> {{ __('admin.database.refresh_metrics') }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if(isset($healthStatus['health_score']) && $healthStatus['health_score'] > 0)

                                <!-- Overall Health Score and Summary -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="progress mb-2" style="height: 25px;">
                                                <div class="progress-bar 
                                                    @if($healthStatus['health_score'] >= 80) bg-success
                                                    @elseif($healthStatus['health_score'] >= 60) bg-orange
                                                    @else bg-danger
                                                    @endif" style="width: {{ $healthStatus['health_score'] }}%">
                                                    <strong>{{ $healthStatus['health_score'] }}%</strong>
                                                </div>
                                            </div>
                                            <h6>{{ __('admin.database.overall_health_score') }}</h6>
                                            <small class="text-muted">
                                                {{ $healthStatus['last_check'] ? $healthStatus['last_check']->format('M
                                                d, H:i') : __('admin.database.never') }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="p-2">
                                                    <h4 class="text-danger mb-1">{{ $healthStatus['critical_alerts'] ??
                                                        0 }}</h4>
                                                    <small class="text-muted">{{ __('admin.database.critical')
                                                        }}</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2">
                                                    <h4 class="text-warning mb-1">{{ $healthStatus['warning_alerts'] ??
                                                        0 }}</h4>
                                                    <small class="text-muted">{{ __('admin.database.warning') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2">
                                                    <h4 class="text-info mb-1">{{ $healthStatus['info_alerts'] ?? 0 }}
                                                    </h4>
                                                    <small class="text-muted">{{ __('admin.database.info') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <h6 class="text-center">{{ __('admin.database.active_alerts') }}</h6>
                                    </div>
                                    <div class="col-md-4">
                                        @if(isset($healthStatus['health_trend']) && count($healthStatus['health_trend'])
                                        > 0)
                                        <div class="text-center">
                                            <h6>{{ __('admin.database.7_day_trend') }}</h6>
                                            <div class="d-flex justify-content-between align-items-end"
                                                style="height: 60px;">
                                                @foreach($healthStatus['health_trend'] as $day)
                                                <div class="flex-fill mx-1">
                                                    <div class="bg-{{ $day['score'] >= 80 ? 'success' : ($day['score'] >= 60 ? 'warning' : 'danger') }}"
                                                        style="height: {{ $day['score'] }}%; min-height: 5px; border-radius: 2px;"
                                                        title="{{ $day['day'] }}: {{ $day['score'] }}%"></div>
                                                    <small class="text-muted" style="font-size: 10px;">{{
                                                        substr($day['date'], -2) }}</small>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Detailed Metrics -->
                                @if(isset($healthStatus['detailed_metrics']) && count($healthStatus['detailed_metrics'])
                                > 0)
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h6>{{ __('admin.database.detailed_metrics') }}</h6>
                                        <div class="row">
                                            @foreach($healthStatus['detailed_metrics'] as $key => $metric)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card border-{{ $metric['color'] }}">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3">
                                                                <i
                                                                    class="la {{ $metric['icon'] }} fa-2x text-{{ $metric['color'] }}"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1">{{ $metric['name'] }}</h6>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="h5 mb-0 me-2">{{ $metric['value'] }}{{
                                                                        $metric['unit'] }}</span>
                                                                    <span class="badge bg-{{ $metric['color'] }}">
                                                                        {{ ucfirst($metric['status']) }}
                                                                    </span>
                                                                </div>
                                                                @if($metric['recommendation'])
                                                                <small class="text-muted">
                                                                    <i class="la la-lightbulb"></i> {{
                                                                    $metric['recommendation'] }}
                                                                </small>
                                                                @endif
                                                                <div class="text-muted mt-1" style="font-size: 11px;">
                                                                    {{ $metric['measured_at']->diffForHumans() }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Recent Alerts -->
                                @if(isset($healthStatus['recent_alerts']) && $healthStatus['recent_alerts']->count() >
                                0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>{{ __('admin.database.recent_alerts') }}</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('admin.database.alert_type') }}</th>
                                                        <th>{{ __('admin.database.severity') }}</th>
                                                        <th>{{ __('admin.database.message') }}</th>
                                                        <th>{{ __('admin.database.created_at') }}</th>
                                                        <th>{{ __('admin.database.actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($healthStatus['recent_alerts'] as $alert)
                                                    <tr>
                                                        <td>
                                                            <code>{{ $alert->alert_type }}</code>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'orange' : 'info') }}">
                                                                {{ ucfirst($alert->severity) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            {{ $alert->message }}
                                                        </td>
                                                        <td>
                                                            <small>
                                                                {{ $alert->created_at->format('M d, H:i') }}<br>
                                                                <span class="text-muted">{{
                                                                    $alert->created_at->diffForHumans() }}</span>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            @if(!$alert->resolved)
                                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                                onclick="resolveAlert({{ $alert->id }})">
                                                                <i class="la la-check"></i> {{
                                                                __('admin.database.resolve') }}
                                                            </button>
                                                            @else
                                                            <span class="text-success">
                                                                <i class="la la-check-circle"></i> {{
                                                                __('admin.database.resolved') }}
                                                            </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @else
                                <div class="text-center text-muted py-5">
                                    <i class="la la-heartbeat fa-3x mb-3"></i>
                                    <h5>{{ __('admin.database.no_health_data') }}</h5>
                                    <p>{{ __('admin.database.no_health_data_desc') }}</p>
                                    <button type="button" class="btn btn-primary"
                                        onclick="runDatabaseCommand('health-check')">
                                        <i class="la la-play"></i> {{ __('admin.database.run_health_check') }}
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Trends Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">{{ __('admin.database.performance_trends') }}</h5>
                                    <small class="text-muted">
                                        {{ __('admin.database.last_30_days') }}
                                        @if(isset($performanceTrends['total_metrics']) &&
                                        $performanceTrends['total_metrics'] > 0)
                                        ({{ $performanceTrends['total_metrics'] }} {{ __('admin.database.data_points')
                                        }})
                                        @else
                                        ({{ __('admin.database.sample_data') }})
                                        @endif
                                    </small>
                                </div>
                                <div>
                                    <a href="{{ backpack_url('performance-metric') }}"
                                        class="btn btn-sm btn-outline-primary me-2">
                                        {{ __('admin.database.view_all') }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-red me-2"
                                        onclick="showCleanupModal()">
                                        <i class="la la-broom"></i> {{ __('admin.database.cleanup_data') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info" onclick="generateSampleMetrics()">
                                        <i class="la la-plus"></i> {{ __('admin.database.generate_sample_data') }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if(isset($performanceTrends['error']))
                                <div class="alert alert-warning">
                                    <i class="la la-exclamation-triangle"></i>
                                    {{ __('admin.database.performance_data_error') }}: {{ $performanceTrends['error'] }}
                                </div>
                                @endif

                                <!-- Metric Type Selector and Controls -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="metricTypeSelector" class="form-label">{{
                                            __('admin.database.select_metric_type') }}</label>
                                        <select id="metricTypeSelector" class="form-select"
                                            onchange="changeMetricType()">
                                            @if($availableMetricTypes->count() > 0)
                                            @foreach($availableMetricTypes as $metricTypeData)
                                            <option value="{{ $metricTypeData['type'] }}"
                                                @if($metricTypeData['type']===($performanceTrends['metric_type'] ?? ''
                                                )) selected @endif>
                                                {{ $metricTypeData['label'] }} ({{ $metricTypeData['count'] }} {{
                                                __('admin.database.records') }})
                                            </option>
                                            @endforeach
                                            @else
                                            <option value="query_time">{{ __('admin.database.metrics.query_time')
                                                }} ({{ __('admin.database.sample') }})</option>
                                            <option value="connection_usage">{{
                                                __('admin.database.metrics.connection_usage') }} ({{
                                                __('admin.database.sample') }})</option>
                                            <option value="memory_usage">{{
                                                __('admin.database.metrics.memory_usage') }} ({{
                                                __('admin.database.sample') }})</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="daysSelector" class="form-label">{{ __('admin.database.time_period')
                                            }}</label>
                                        <select id="daysSelector" class="form-select" onchange="changeMetricType()">
                                            <option value="7">{{ __('admin.database.last_7_days') }}</option>
                                            <option value="30" selected>{{ __('admin.database.last_30_days') }}</option>
                                            <option value="90">{{ __('admin.database.last_90_days') }}</option>
                                            <option value="365">{{ __('admin.database.last_year') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm"
                                                onclick="refreshChart()">
                                                <i class="la la-refresh"></i> {{ __('admin.database.refresh_chart') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chart Statistics -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h4 class="text-primary" id="avgValue">{{ $performanceTrends['avg_value'] ??
                                                0 }}</h4>
                                            <small class="text-muted" id="avgLabel">
                                                {{ __('admin.database.average') }} <span id="currentUnit">{{
                                                    $performanceTrends['unit'] ?? 'ms' }}</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h4 class="text-info" id="currentMetricType">{{
                                                $performanceTrends['metric_label'] ?? 'N/A' }}</h4>
                                            <small class="text-muted">{{ __('admin.database.current_metric') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h4 class="text-success" id="dataPointsCount">{{
                                                count($performanceTrends['labels'] ?? []) }}</h4>
                                            <small class="text-muted">{{ __('admin.database.data_points') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chart Container with fixed height -->
                                <div class="chart-container" style="position: relative; height: 400px; width: 100%;">
                                    <canvas id="performanceChart"></canvas>
                                    <div id="chartLoading" class="position-absolute d-none"
                                        style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">{{ __('admin.database.loading')
                                                    }}...</span>
                                            </div>
                                            <div class="mt-2">{{ __('admin.database.loading_chart') }}...</div>
                                        </div>
                                    </div>
                                </div>

                                @if((!isset($performanceTrends['total_metrics']) || $performanceTrends['total_metrics']
                                == 0))
                                <div class="mt-3 p-3 bg-light rounded">
                                    <div class="row text-center">
                                        <div class="col-md-12">
                                            <i class="la la-info-circle text-info"></i>
                                            <strong>{{ __('admin.database.no_real_metrics_title') }}</strong>
                                            <p class="mb-2">{{ __('admin.database.no_real_metrics_desc') }}</p>
                                            <button type="button" class="btn btn-sm btn-primary"
                                                onclick="runDatabaseCommand('monitor')">
                                                <i class="la la-play"></i> {{ __('admin.database.start_monitoring') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('before_scripts')
    <!-- Command Output Modal - Finální opravená verze -->
    <div class="modal fade" id="commandOutputModal" tabindex="-1" aria-labelledby="commandOutputModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commandOutputModalLabel">
                        <i class="la la-terminal"></i> {{ __('admin.database.command_output') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <pre id="commandOutput"
                            style="white-space: pre-wrap; word-wrap: break-word; padding: 15px; border-radius: 5px; font-size: 12px; line-height: 1.4; margin: 0; font-family: 'Courier New', monospace;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="la la-times"></i> {{ __('admin.database.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cleanup Modal -->
    <div class="modal fade" id="cleanupModal" tabindex="-1" aria-labelledby="cleanupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cleanupModalLabel">
                        <i class="la la-broom"></i> {{ __('admin.database.cleanup_performance_data') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Current Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6>{{ __('admin.database.current_statistics') }}</h6>
                            <div id="metricsStats" class="row">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4 id="totalMetrics">-</h4>
                                            <small>{{ __('admin.database.total_metrics') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4 id="sampleMetrics">-</h4>
                                            <small>{{ __('admin.database.sample_metrics') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4 id="oldestMetric">-</h4>
                                            <small>{{ __('admin.database.oldest_metric') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4 id="metricTypes">-</h4>
                                            <small>{{ __('admin.database.metric_types') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cleanup Options -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6>{{ __('admin.database.cleanup_options') }}</h6>
                            <div class="form-group">
                                <label for="cleanupType">{{ __('admin.database.cleanup_type') }}</label>
                                <select id="cleanupType" class="form-select"
                                    onchange="updateCleanupDescription()">
                                    <option value="old">{{ __('admin.database.cleanup_old_data') }}</option>
                                    <option value="sample">{{ __('admin.database.cleanup_sample_data') }}
                                    </option>
                                    <option value="duplicate">{{ __('admin.database.cleanup_duplicates') }}
                                    </option>
                                    <option value="all">{{ __('admin.database.cleanup_all_data') }}</option>
                                </select>
                            </div>

                            <div id="daysSelector" class="form-group">
                                <label for="cleanupDays">{{ __('admin.database.keep_last_days') }}</label>
                                <select id="cleanupDays" class="form-select">
                                    <option value="30">30 {{ __('admin.database.days') }}</option>
                                    <option value="60">60 {{ __('admin.database.days') }}</option>
                                    <option value="90" selected>90 {{ __('admin.database.days') }}</option>
                                    <option value="180">180 {{ __('admin.database.days') }}</option>
                                    <option value="365">365 {{ __('admin.database.days') }}</option>
                                </select>
                            </div>

                            <div class="alert alert-info" id="cleanupDescription">
                                {{ __('admin.database.cleanup_old_description') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('admin.database.cancel') }}
                    </button>
                    <button type="button" class="btn btn-orange" onclick="executeCleanup()">
                        <i class="la la-broom"></i> {{ __('admin.database.execute_cleanup') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
    <style>
        .modal-backdrop.fade {
            opacity: 0.75 !important; /* Adjust backdrop opacity */
        }
    </style>
@endsection

@section('after_scripts')
    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    {{-- Setup global variables for JavaScript --}}
    <script>
        // Set up global variables for JavaScript file
        window.baseUrl = '{{ backpack_url('') }}';
        
        // Chart data
        window.chartData = {
            labels: @json($performanceTrends['labels'] ?? []),
            data: @json($performanceTrends['data'] ?? []),
            metricType: @json($performanceTrends['metric_type'] ?? 'query_time'),
            metricUnit: @json($performanceTrends['unit'] ?? 'ms'),
            metricLabel: @json($performanceTrends['metric_label'] ?? 'Query Time')
        };
        
        // Translations
        window.translations = {
            'admin.database.performance_trends': '{{ __("admin.database.performance_trends") }}',
            'admin.database.value': '{{ __("admin.database.value") }}',
            'admin.database.date': '{{ __("admin.database.date") }}',
            'admin.database.no_chart_data': '{{ __("admin.database.no_chart_data") }}',
            'admin.database.failed_to_load_chart': '{{ __("admin.database.failed_to_load_chart") }}',
            'admin.database.chart_loading_error': '{{ __("admin.database.chart_loading_error") }}',
            'admin.database.confirm_generate_sample': '{{ __("admin.database.confirm_generate_sample") }}',
            'admin.database.generating': '{{ __("admin.database.generating") }}',
            'admin.database.sample_metrics_generated': '{{ __("admin.database.sample_metrics_generated") }}',
            'admin.database.sample_generation_failed': '{{ __("admin.database.sample_generation_failed") }}',
            'admin.database.sample_generation_error': '{{ __("admin.database.sample_generation_error") }}',
            'admin.database.confirm_resolve_alert': '{{ __("admin.database.confirm_resolve_alert") }}',
            'admin.database.alert_resolved': '{{ __("admin.database.alert_resolved") }}',
            'admin.database.alert_resolve_failed': '{{ __("admin.database.alert_resolve_failed") }}',
            'admin.database.alert_resolve_error': '{{ __("admin.database.alert_resolve_error") }}',
            'admin.database.running': '{{ __("admin.database.running") }}',
            'admin.database.command_completed': '{{ __("admin.database.command_completed") }}',
            'admin.database.command_failed': '{{ __("admin.database.command_failed") }}',
            'admin.database.command_error': '{{ __("admin.database.command_error") }}',
            'admin.database.cleanup_old_description': '{{ __("admin.database.cleanup_old_description") }}',
            'admin.database.cleanup_sample_description': '{{ __("admin.database.cleanup_sample_description") }}',
            'admin.database.cleanup_duplicate_description': '{{ __("admin.database.cleanup_duplicate_description") }}',
            'admin.database.cleanup_all_description': '{{ __("admin.database.cleanup_all_description") }}',
            'admin.database.confirm_cleanup_all': '{{ __("admin.database.confirm_cleanup_all") }}',
            'admin.database.cleaning': '{{ __("admin.database.cleaning") }}',
            'admin.database.records_deleted': '{{ __("admin.database.records_deleted") }}',
            'admin.database.cleanup_failed': '{{ __("admin.database.cleanup_failed") }}',
            'admin.database.cleanup_error': '{{ __("admin.database.cleanup_error") }}'
        };
        
        // Expose functions globally for onclick handlers
        window.changeMetricType = function() { return window.DatabaseDashboard.changeMetricType(); };
        window.refreshChart = function() { return window.DatabaseDashboard.refreshChart(); };
        window.generateSampleMetrics = function() { return window.DatabaseDashboard.generateSampleMetrics(); };
        window.resolveAlert = function(alertId) { return window.DatabaseDashboard.resolveAlert(alertId); };
        window.runDatabaseCommand = function(command) { return window.DatabaseDashboard.runDatabaseCommand(command); };
        window.showCleanupModal = function() { return window.DatabaseDashboard.showCleanupModal(); };
        window.updateCleanupDescription = function() { return window.DatabaseDashboard.updateCleanupDescription(); };
        window.executeCleanup = function() { return window.DatabaseDashboard.executeCleanup(); };
    </script>
    
    {{-- Load external JavaScript file --}}
    <script src="{{ asset('assets/js/admin/database-dashboard.js') }}?v={{ filemtime(public_path('assets/js/admin/database-dashboard.js')) }}"></script>
@endsection
