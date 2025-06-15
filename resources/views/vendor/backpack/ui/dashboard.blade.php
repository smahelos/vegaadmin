@extends(backpack_view('blank'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('admin.dashboard.overview') }}</h3>
                </div>
                <div class="card-body">

                    {{-- Dashboard Statistics Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $dashboardStats['total_invoices'] ?? 0 }}</h4>
                                            <small>{{ __('admin.dashboard.total_invoices') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-file-invoice fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $dashboardStats['total_clients'] ?? 0 }}</h4>
                                            <small>{{ __('admin.dashboard.total_clients') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card bg-orange text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $dashboardStats['total_suppliers'] ?? 0 }}</h4>
                                            <small>{{ __('admin.dashboard.total_suppliers') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-truck fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $dashboardStats['total_products'] ?? 0 }}</h4>
                                            <small>{{ __('admin.dashboard.total_products') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-shopping-cart fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $dashboardStats['total_users'] ?? 0 }}</h4>
                                            <small>{{ __('admin.dashboard.total_users') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-user fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>€{{ number_format($dashboardStats['monthly_revenue'] ?? 0, 2) }}</h4>
                                            <small>{{ __('admin.dashboard.monthly_revenue') }}</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="la la-euro-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('admin.dashboard.quick_actions') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ backpack_url('invoice/create') }}" class="btn btn-primary btn-block">
                                                <i class="la la-plus"></i> {{ __('admin.dashboard.create_invoice') }}
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ backpack_url('client/create') }}" class="btn btn-success btn-block">
                                                <i class="la la-user-plus"></i> {{ __('admin.dashboard.add_client') }}
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ backpack_url('product/create') }}" class="btn btn-orange btn-block">
                                                <i class="la la-box"></i> {{ __('admin.dashboard.add_product') }}
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ backpack_url('database-dashboard') }}" class="btn btn-info btn-block">
                                                <i class="la la-database"></i> {{ __('admin.dashboard.database_management') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- User Activity Overview --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">{{ __('admin.user_activity.overview') }}</h5>
                                        <small class="text-muted">{{ __('admin.user_activity.based_on_invoices_last_30_days') }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ backpack_url('user') }}" class="btn btn-sm btn-outline-primary">
                                            {{ __('admin.user_activity.manage_users') }}
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if(isset($userActivityStats['total_users']) && $userActivityStats['total_users'] > 0)
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $userActivityStats['active_users'] ?? 0 }}</h4>
                                                    <small>{{ __('admin.user_activity.active_users') }}</small>
                                                    <div class="mt-2">
                                                        <span class="badge badge-light">
                                                            {{ $userActivityStats['activity_rate'] ?? 0 }}% {{ __('admin.user_activity.activity_rate') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $userActivityStats['high_activity_users'] ?? 0 }}</h4>
                                                    <small>{{ __('admin.user_activity.high_activity_users') }}</small>
                                                    <div class="mt-2">
                                                        <small>{{ __('admin.user_activity.20_plus_invoices') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-orange text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $userActivityStats['inactive_users'] ?? 0 }}</h4>
                                                    <small>{{ __('admin.user_activity.inactive_users') }}</small>
                                                    <div class="mt-2">
                                                        <small>{{ __('admin.user_activity.no_invoices_30_days') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $userActivityStats['total_users'] ?? 0 }}</h4>
                                                    <small>{{ __('admin.user_activity.total_users') }}</small>
                                                    <div class="mt-2" style="color: #467fd0;">x</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(isset($userActivityStats['most_active_users']) && $userActivityStats['most_active_users']->count() > 0)
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <h6>{{ __('admin.user_activity.most_active_users') }}</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('admin.user_activity.user') }}</th>
                                                            <th>{{ __('admin.user_activity.email') }}</th>
                                                            <th>{{ __('admin.user_activity.invoices_30_days') }}</th>
                                                            <th>{{ __('admin.user_activity.invoices_7_days') }}</th>
                                                            <th>{{ __('admin.user_activity.activity_level') }}</th>
                                                            <th>{{ __('admin.user_activity.last_invoice') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($userActivityStats['most_active_users'] as $userActivity)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $userActivity->user_name }}</strong>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{ $userActivity->user_email }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary">{{ $userActivity->invoices_last_30_days }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info">{{ $userActivity->invoices_last_7_days }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $userActivity->activity_badge_class }}">
                                                                    {{ $userActivity->formatted_activity_level }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if($userActivity->last_invoice_date)
                                                                <small>
                                                                    {{ $userActivity->last_invoice_date->format('M d, Y') }}<br>
                                                                    <span class="text-muted">{{ $userActivity->last_invoice_date->diffForHumans() }}</span>
                                                                </small>
                                                                @else
                                                                <span class="text-muted">{{ __('admin.user_activity.never') }}</span>
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
                                    <div class="text-center text-muted">
                                        <i class="la la-users fa-2x mb-2"></i>
                                        <p>{{ __('admin.user_activity.no_user_activity_data') }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Recent Activity --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('admin.dashboard.recent_activity') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <h4 class="text-primary">{{ $dashboardStats['recent_invoices'] ?? 0 }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.invoices_last_30_days') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <h4 class="text-success">{{ $dashboardStats['recent_clients'] ?? 0 }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.clients_last_30_days') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('admin.dashboard.revenue_overview') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <h4 class="text-success">€{{ number_format($dashboardStats['total_revenue'] ?? 0, 2) }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.total_revenue') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <h4 class="text-info">€{{ number_format($dashboardStats['monthly_revenue'] ?? 0, 2) }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.this_month') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
