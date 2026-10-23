@extends(backpack_view('blank'))

@php
$title = __('admin.uels.dashboard');
$widgets['before_content'][] = [
'type' => 'jumbotron',
'heading' => __('entity_limits.dashboard.heading'),
'content' => __('entity_limits.dashboard.intro'),
'button_link' => backpack_url('entity-limit'),
'button_text' => __('entity_limits.dashboard.manage_limits'),
];
@endphp

@section('content')

<!-- Overview Stats -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="info-box bg-info text-center text-white p-3 border rounded-2">
            <span class="info-box-icon bg-info"><i class="nav-icon la la-cogs"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.total_limits') }}</span>
                <span class="info-box-number">{{ $stats['total_limits'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-orange text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-orange"><i class="nav-icon la la-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.users_with_usage') }}</span>
                <span class="info-box-number">{{ $stats['total_users_with_usage'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="info-box bg-success text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-success"><i class="nav-icon la la-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.active_limits') }}</span>
                <span class="info-box-number">{{ $stats['active_limits'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="info-box bg-primary text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-primary"><i class="nav-icon la la-key"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.permissions') }}</span>
                <span class="info-box-number">{{ $stats['total_permissions_with_limits'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="info-box bg-secondary text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-secondary"><i class="nav-icon la la-user-secret"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.anonymous_usage') }}</span>
                <span class="info-box-number">{{ $stats['anonymous_usage_records'] }}</span>
            </div>
        </div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-2">
        <div class="info-box bg-danger text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-danger"><i class="nav-icon la la-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.today') }}</span>
                <span class="info-box-number">{{ $stats['today_usage'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="info-box bg-blue text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-blue"><i class="nav-icon la la-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.this_week') }}</span>
                <span class="info-box-number">{{ $stats['this_week_usage'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="info-box bg-teal text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-teal"><i class="nav-icon la la-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.this_month') }}</span>
                <span class="info-box-number">{{ $stats['this_month_usage'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-purple text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-purple"><i class="nav-icon la la-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.this_year') }}</span>
                <span class="info-box-number">{{ $stats['this_year_usage'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-lime text-center text-white  p-3 border rounded-2">
            <span class="info-box-icon bg-lime"><i class="nav-icon la la-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('entity_limits.dashboard.stats.lifetime') }}</span>
                <span class="info-box-number">{{ $stats['lifetime_usage'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <!-- Limit Violations -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="nav-icon la la-exclamation-triangle text-danger"></i> {{ __('entity_limits.dashboard.violations.title') }}</h3>
            </div>
            <div class="card-body">
                @if(count($violations) > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('entity_limits.usages.fields.user_id') }}</th>
                                <th>{{ __('entity_limits.usages.fields.entity_type') }}</th>
                                <th>{{ __('entity_limits.usages.fields.metric_type') }}</th>
                                <th>{{ __('entity_limits.usages.fields.period_type') }}</th>
                                <th>{{ __('entity_limits.widget.usage') }}</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($violations as $violation)
                            <tr>
                                <td>{{ $violation['user']->name ?? __('entity_limits.usages.labels.anonymous') }}</td>
                                <td><span class="badge bg-green">{{ __("entity_limits.entity_types.{$violation['entity_type']}.singular") }}</span>
                                </td>
                                <td><span class="badge bg-yellow">{{ __('entity_limits.metric_types.'.($violation['metric_type'] ?? 'count')) }}</span>
                                </td>
                                <td><span class="badge bg-blue">{{ __("entity_limits.periods.{$violation['period_type']}") }}</span></td>
                                <td>{{ $violation['current_usage'] }}/{{ $violation['limit_value'] }}</td>
                                <td>
                                    <span class="badge bg-red">{{ number_format($violation['percentage'], 1)
                                        }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted"><i class="la la-check-circle text-success"></i> {{ __('entity_limits.dashboard.violations.none') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Active Limits Breakdown -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="nav-icon la la-list"></i> {{ __('entity_limits.dashboard.breakdown.title') }}</h3>
            </div>
            <div class="card-body">
                @if(count($limitsBreakdown) > 0)
                @foreach($limitsBreakdown as $entityType => $metricTypes)
                <div class="mb-3">
                    <h6 class="mb-1"><strong>{{ __("entity_limits.entity_types.$entityType.singular") }}</strong></h6>
                    @foreach($metricTypes as $metricType => $periods)
                    <div class="ml-3 mb-2">
                        <small class="text-muted">{{ __("entity_limits.metric_types.$metricType") }}:</small>
                        @foreach($periods as $period => $data)
                        <span class="badge bg-azure mr-1"
                            title="{{ __('entity_limits.dashboard.breakdown.permissions', ['permissions' => implode(', ', $data['permissions'])]) }}">
                            {{ __("entity_limits.periods.$period") }}: {{ $data['count'] }}
                        </span>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endforeach
                @else
                <p class="text-muted">{{ __('entity_limits.dashboard.breakdown.none') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="nav-icon la la-clock"></i> {{ __('entity_limits.dashboard.recent.title') }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" onclick="exportUsageData()">
                        <i class="la la-download"></i> {{ __('entity_limits.dashboard.recent.export') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if(count($recentActivity) > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('entity_limits.usages.fields.user_id') }}</th>
                                <th>{{ __('entity_limits.usages.fields.entity_type') }}</th>
                                <th>{{ __('entity_limits.usages.fields.metric_type') }}</th>
                                <th>{{ __('entity_limits.usages.fields.period_type') }}</th>
                                <th>{{ __('entity_limits.usages.fields.current_value') }}</th>
                                <th>{{ __('entity_limits.usages.fields.period_start') }}</th>
                                <th>{{ __('entity_limits.dashboard.recent.last_updated') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $activity)
                            <tr>
                                <td>
                                    @if($activity['user_id'])
                                    {{ $activity['user_name'] }}
                                    <small class="text-muted">(ID: {{ $activity['user_id'] }})</small>
                                    @else
                                    <span class="text-muted">{{ __('entity_limits.usages.labels.anonymous') }}</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-green">{{ __("entity_limits.entity_types.{$activity['entity_type']}.singular") }}</span>
                                </td>
                                <td><span class="badge bg-yellow">{{ __("entity_limits.metric_types.{$activity['metric_type']}") }}</span>
                                </td>
                                <td><span class="badge bg-blue">{{ __("entity_limits.periods.{$activity['period_type']}") }}</span></td>
                                <td>
                                    @if($activity['metric_type'] === 'value')
                                    €{{ number_format($activity['current_value'], 2) }}
                                    @elseif($activity['metric_type'] === 'size')
                                    {{ number_format($activity['current_value'] / 1024, 2) }} KB
                                    @else
                                    {{ number_format($activity['current_value']) }}
                                    @endif
                                </td>
                                <td>{{ $activity['period_start']?->isoFormat('ll') ?? __('general.placeholders.not_available') }}</td>
                                <td>{{ $activity['last_updated_at']?->isoFormat('lll') ?? __('general.placeholders.not_available') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted">{{ __('entity_limits.dashboard.recent.none') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Usage Tools -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="nav-icon la la-tools"></i> {{ __('entity_limits.dashboard.analysis.title') }}</h3>
            </div>
            <div class="card-body">
                <form id="userAnalysisForm">
                    <div class="form-group">
                        <label for="user_select">{{ __('entity_limits.usages.fields.user_id') }}</label>
                        <select class="form-control" id="user_select" name="user_id">
                            <option value="">{{ __('entity_limits.dashboard.analysis.choose_user') }}</option>
                            <!-- Users will be loaded via AJAX -->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">
                        <i class="la la-search"></i> {{ __('entity_limits.dashboard.analysis.submit') }}
                    </button>
                </form>

                <div id="analysisResults" class="mt-3" style="display: none;">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="nav-icon la la-refresh"></i> {{ __('entity_limits.dashboard.reset.title') }}</h3>
            </div>
            <div class="card-body">
                <form id="bulkResetForm">
                    <div class="form-group">
                        <label for="entity_type_reset">{{ __('entity_limits.usages.fields.entity_type') }}</label>
                        <select class="form-control" id="entity_type_reset" name="entity_type" required>
                            <option value="">{{ __('entity_limits.dashboard.reset.choose_entity_type') }}</option>
                            @foreach(\App\Models\EntityLimit::ENTITY_TYPES as $entityType)
                            <option value="{{ $entityType }}">{{ __("entity_limits.entity_types.$entityType.singular") }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="metric_type_reset">{{ __('entity_limits.usages.fields.metric_type') }}</label>
                        <select class="form-control" id="metric_type_reset" name="metric_type" required>
                            <option value="">{{ __('entity_limits.dashboard.reset.choose_metric_type') }}</option>
                            @foreach(array_keys(\App\Models\EntityLimit::METRIC_TYPES) as $metricType)
                            <option value="{{ $metricType }}">{{ __("entity_limits.metric_types.$metricType") }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="period_type_reset">{{ __('entity_limits.usages.fields.period_type') }}</label>
                        <select class="form-control" id="period_type_reset" name="period_type" required>
                            <option value="">{{ __('entity_limits.dashboard.reset.choose_period_type') }}</option>
                            @foreach(array_keys(\App\Models\EntityLimit::PERIOD_TYPES) as $periodType)
                            <option value="{{ $periodType }}">{{ __("entity_limits.periods.$periodType") }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="reset_all_users" name="reset_all_users">
                        <label class="form-check-label" for="reset_all_users">
                            {{ __('entity_limits.dashboard.reset.all_users') }}
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="include_anonymous" name="include_anonymous">
                        <label class="form-check-label" for="include_anonymous">
                            {{ __('entity_limits.dashboard.reset.include_anonymous') }}
                        </label>
                    </div>
                    </label>
            </div>
            <button type="submit" class="btn btn-warning">
                <i class="la la-refresh"></i> {{ __('entity_limits.dashboard.reset.submit') }}
            </button>
            </form>
        </div>
    </div>
</div>
</div>

@endsection

@section('after_scripts')
<script>
const uelsText = @js([
    'dashboard' => __('entity_limits.dashboard'),
    'entityTypes' => collect(\App\Models\EntityLimit::ENTITY_TYPES)->mapWithKeys(fn ($type) => [$type => __("entity_limits.entity_types.$type.singular")]),
    'metricTypes' => __('entity_limits.metric_types'),
    'periods' => __('entity_limits.periods'),
    'notAllowed' => __('entity_limits.statuses.not_allowed'),
    'entityTypeLabel' => __('entity_limits.usages.fields.entity_type'),
]);

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Export usage data
function exportUsageData() {
    window.open('{{ backpack_url('uels-dashboard/export') }}', '_blank');
}

// User Analysis Form
document.getElementById('userAnalysisForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('user_select').value;
    if (!userId) {
        new Noty({
            type: 'warning',
            text: uelsText.dashboard.analysis.select_user_first
        }).show();
        return;
    }
    
    // Show loading
    const resultsDiv = document.getElementById('analysisResults');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div class="text-center"><i class="la la-spinner la-spin"></i> ' + escapeHtml(uelsText.dashboard.analysis.loading) + '</div>';
    
    fetch('{{ backpack_url('uels-dashboard/user-analysis') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        let html = '<h6>' + escapeHtml(uelsText.dashboard.analysis.for_user + ' ' + data.user.name) + '</h6>';
        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead><tr><th>' + escapeHtml(uelsText.entityTypeLabel) + '</th>';
        ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'].forEach(period => {
            html += '<th>' + escapeHtml(uelsText.periods[period]) + '</th>';
        });
        html += '</tr></thead><tbody>';
        
        Object.keys(data.analysis).forEach(entityType => {
            ['count', 'value', 'size'].forEach(metricType => {
                html += '<tr><td><strong>' + escapeHtml(uelsText.entityTypes[entityType] ?? entityType) + '</strong> - ' + escapeHtml(uelsText.metricTypes[metricType]) + '</td>';

                ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'].forEach(period => {
                        const usage = data.analysis[entityType][metricType][period];
                        let badge = 'green';
                        if (!usage.allowed) badge = 'gray';
                        else if (usage.percentage >= 80) badge = 'yellow';
                        if (Math.round(usage.percentage) == 100) badge = 'red';

                        html += '<td><span class="badge bg-' + badge + '">';
                        if (usage.limit_value) {
                            html += usage.current_usage + '/' + usage.limit_value + ' (' + Math.round(usage.percentage) + '%)';
                        } else {
                            html += escapeHtml(uelsText.notAllowed);
                        }
                        html += '</span></td>';
                });
                
                html += '</tr>';
            });
        });
        
        html += '</tbody></table></div>';
        resultsDiv.innerHTML = html;
    })
    .catch(error => {
        resultsDiv.innerHTML = '<div class="alert alert-danger">' + escapeHtml(uelsText.dashboard.error + ': ' + error.message) + '</div>';
    });
});

// Bulk Reset Form
document.getElementById('bulkResetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    if (!data.entity_type || !data.metric_type || !data.period_type) {
        new Noty({
            type: 'warning',
            text: uelsText.dashboard.reset.select_required
        }).show();
        return;
    }
    
    if (!confirm(uelsText.dashboard.reset.confirm)) {
        return;
    }
    
    fetch('{{ backpack_url('uels-dashboard/bulk-reset') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            new Noty({
                type: 'success',
                text: data.message
            }).show();
            
            // Reset form
            document.getElementById('bulkResetForm').reset();
            
            // Refresh page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || uelsText.dashboard.reset.failed);
        }
    })
    .catch(error => {
        new Noty({
            type: 'error',
            text: escapeHtml(uelsText.dashboard.error + ': ' + error.message)
        }).show();
    });
});

// Load users for analysis dropdown
fetch('{{ url('api/admin/user') }}')
    .then(response => response.json())
    .then(users => {
        const select = document.getElementById('user_select');
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name + ' (' + user.email + ')';
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Failed to load users:', error);
    });
</script>
@endsection
