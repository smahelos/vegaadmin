@extends(backpack_view('blank'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('admin.dashboard.overview') }}</h3>
                </div>
                <div class="card-body">

                    @if($dashboard)
                    {{-- Dashboard Statistics Cards --}}
                    <div class="row mb-3">
                        <div class="col-sm-6 col-lg-4 col-xxl mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1">{{ $dashboard->statistics->invoiceCount }}</h4>
                                        <small>{{ __('admin.dashboard.total_invoices') }}</small>
                                    </div>
                                    <i class="la la-file-invoice la-2x flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-4 col-xxl mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1">{{ $dashboard->statistics->contactCount }}</h4>
                                        <small>{{ __('admin.dashboard.total_contacts') }}</small>
                                    </div>
                                    <i class="la la-users la-2x flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-4 col-xxl mb-3">
                            <div class="card bg-orange text-white h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1">{{ $dashboard->statistics->businessCount }}</h4>
                                        <small>{{ __('admin.dashboard.total_businesses') }}</small>
                                    </div>
                                    <i class="la la-truck la-2x flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-4 col-xxl mb-3">
                            <div class="card bg-secondary text-white h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1">{{ $dashboard->statistics->userCount }}</h4>
                                        <small>{{ __('admin.dashboard.total_users') }}</small>
                                    </div>
                                    <i class="la la-user la-2x flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-4 col-xxl mb-3">
                            <div class="card bg-red text-white h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1">{{ $dashboard->statistics->activeSubscriptionCount }}</h4>
                                        <small>{{ __('admin.dashboard.active_subscriptions') }}</small>
                                    </div>
                                    <i class="la la-money-bill-wave-alt la-2x flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

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
                                            <a href="{{ backpack_url('business/create') }}" class="btn btn-success btn-block">
                                                <i class="la la-user-plus"></i> {{ __('admin.dashboard.add_business') }}
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

                    @if($dashboard)
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
                                    @if($dashboard->activity->userCount > 0)
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $dashboard->activity->activeUserCount }}</h4>
                                                    <small>{{ __('admin.user_activity.active_users') }}</small>
                                                    <div class="mt-2">
                                                        <span class="badge text-bg-light">
                                                            {{ $presenter->activityRate($dashboard->activity) }}% {{ __('admin.user_activity.activity_rate') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $dashboard->activity->highActivityUserCount }}</h4>
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
                                                    <h4>{{ $dashboard->activity->inactiveUserCount }}</h4>
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
                                                    <h4>{{ $dashboard->activity->userCount }}</h4>
                                                    <small>{{ __('admin.user_activity.total_users') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($dashboard->activity->mostActive !== [])
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
                                                        @foreach($dashboard->activity->mostActive as $userActivity)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $userActivity->name }}</strong>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{ $userActivity->email }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary">{{ $userActivity->invoicesLast30Days }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info">{{ $userActivity->invoicesLast7Days }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $presenter->levelColour($userActivity->level) }}">
                                                                    {{ __('admin.user_activity.levels.' . $userActivity->level->value) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small>{{ $presenter->lastInvoice($userActivity, $locale) ?? __('admin.user_activity.never') }}</small>
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
                                                <h4 class="text-primary">{{ $dashboard->statistics->recentInvoiceCount }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.invoices_last_30_days') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <h4 class="text-success">{{ $dashboard->statistics->recentContactCount }}</h4>
                                                <small class="text-muted">{{ __('admin.dashboard.contacts_last_30_days') }}</small>
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
                                                @forelse($presenter->revenue($dashboard->statistics->revenue, $locale) as $amount)
                                                <h4 class="text-success">{{ $amount }}</h4>
                                                @empty
                                                <h4 class="text-success">&mdash;</h4>
                                                @endforelse
                                                <small class="text-muted">{{ __('admin.dashboard.total_revenue') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                @forelse($presenter->revenue($dashboard->statistics->monthlyRevenue, $locale) as $amount)
                                                <h4 class="text-info">{{ $amount }}</h4>
                                                @empty
                                                <h4 class="text-info">&mdash;</h4>
                                                @endforelse
                                                <small class="text-muted">{{ __('admin.dashboard.this_month') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
