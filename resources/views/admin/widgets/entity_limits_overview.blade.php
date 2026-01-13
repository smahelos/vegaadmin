{{-- Entity Limits Overview Widget --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="las la-shield-alt"></i> Entity Limits Overview
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="las la-list"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Limits</span>
                        <span class="info-box-number">{{ $stats['total_limits'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success">
                        <i class="las la-check-circle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Limits</span>
                        <span class="info-box-number">{{ $stats['active_limits'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-warning">
                        <i class="las la-cubes"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Entity Types</span>
                        <span class="info-box-number">{{ $stats['entity_types'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($recentLimits->count() > 0)
        <h6 class="mt-3">Recent Limits</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Entity Type</th>
                        <th>Metric Type</th>
                        <th>Period</th>
                        <th>Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLimits as $limit)
                    <tr>
                        <td>
                            <small class="text-muted">{{ $limit->permission_name }}</small>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ ucfirst($limit->entity_type) }}</span>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ ucfirst($limit->metric_type) }}</span>
                        </td>
                        <td>{{ ucfirst($limit->period_type) }}</td>
                        <td>
                            <strong>{{ number_format($limit->limit_value) }}</strong>
                        </td>
                        <td>
                            @if($limit->is_active)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="mt-3">
            <a href="{{ backpack_url('entity-limit') }}" class="btn btn-primary btn-sm">
                <i class="las la-cog"></i> Manage Limits
            </a>
            <a href="{{ backpack_url('uels-dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="las la-tachometer-alt"></i> UELS Dashboard
            </a>
            <a href="{{ backpack_url('entity-usage') }}" class="btn btn-info btn-sm">
                <i class="las la-chart-bar"></i> Usage Tracking
            </a>
        </div>
    </div>
</div>
