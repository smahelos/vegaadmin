{{-- This file is used for menu items by any Backpack v6 theme --}}
@php($user = backpack_user())
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{
        trans('backpack::base.dashboard') }}</a></li>

{{-- 1. User Management --}}
@if($user->hasPermissionTo('can_view_user', 'backpack') ||
$user->hasPermissionTo('can_view_role', 'backpack') ||
$user->hasPermissionTo('can_view_permission', 'backpack') ||
$user->hasPermissionTo('can_view_role_limit', 'backpack') ||
$user->hasPermissionTo('can_view_subscription', 'backpack') ||
$user->hasPermissionTo('can_view_subscription_plan', 'backpack') ||
$user->hasPermissionTo('can_view_subscription_plan_feature', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.user_management.title') }}" icon="la la-users text-primary">
    @if($user->hasPermissionTo('can_view_user', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.users') }}" icon="la la-user"
        :link="backpack_url('user')" />
    @endif
    @if($user->hasPermissionTo('can_view_role', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.roles') }}" icon="la la-group"
        :link="backpack_url('role')" />
    @endif
    @if($user->hasPermissionTo('can_view_permission', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.permissions') }}" icon="la la-key"
        :link="backpack_url('permission')" />
    @endif
    @if($user->hasPermissionTo('can_view_role_limit', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('role_limits.entity.plural') }}" icon="la la-sliders-h"
        :link="backpack_url('role-limit')" />
    @endif
    @if($user->hasPermissionTo('can_view_subscription', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.entity.plural') }}" icon="la la-handshake"
        :link="backpack_url('subscription')" />
    @endif
    @if($user->hasPermissionTo('can_view_subscription_plan', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.plans.entity.plural') }}" icon="la la-id-card"
        :link="backpack_url('subscription-plan')" />
    @endif
    @if($user->hasPermissionTo('can_view_subscription_plan_feature', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.features.entity.plural') }}"
        icon="la la-check-square" :link="backpack_url('subscription-plan-feature')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 2. Business Operations --}}
@if($user->hasPermissionTo('can_view_invoice', 'backpack') ||
$user->hasPermissionTo('can_view_business', 'backpack') ||
$user->hasPermissionTo('can_view_contact', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.business.title') }}" icon="la la-briefcase text-success">
    @if($user->hasPermissionTo('can_view_invoice', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.invoicing') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('invoices.entity.plural') }}" icon="la la-file-invoice"
        :link="backpack_url('invoice')" />
    @endif

    @if($user->hasPermissionTo('can_view_business', 'backpack') ||
    $user->hasPermissionTo('can_view_contact', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.partners') }}" />
    @endif

    @if($user->hasPermissionTo('can_view_business', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('businesses.entity.plural') }}" icon="la la-building"
        :link="backpack_url('business')" />
    @endif

    @if($user->hasPermissionTo('can_view_contact', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('contacts.entity.plural') }}" icon="la la-address-book"
        :link="backpack_url('contact')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 3. Financial Management --}}
@if($user->hasPermissionTo('can_view_expense', 'backpack') ||
$user->hasPermissionTo('can_view_tax', 'backpack') ||
$user->hasPermissionTo('can_view_bank', 'backpack') ||
$user->hasPermissionTo('can_view_payment_method', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.finance.title') }}" icon="la la-coins text-warning">
    @if($user->hasPermissionTo('can_view_expense', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.expenses') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('expenses.entity.plural') }}" icon="la la-receipt"
        :link="backpack_url('expense')" />
    <x-backpack::menu-dropdown-item title="{{ __('expenses.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('expense-category')" />
    @endif

    @if($user->hasPermissionTo('can_view_tax', 'backpack') ||
    $user->hasPermissionTo('can_view_bank', 'backpack') ||
    $user->hasPermissionTo('can_view_payment_method', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.settings') }}" />
    @endif

    @if($user->hasPermissionTo('can_view_tax', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('taxes.entity.plural') }}" icon="la la-percentage"
        :link="backpack_url('tax')" />
    @endif

    @if($user->hasPermissionTo('can_view_bank', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('banks.entity.plural') }}" icon="la la-bank"
        :link="backpack_url('bank')" />
    @endif

    @if($user->hasPermissionTo('can_view_payment_method', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ __('payment_methods.entity.plural') }}"
        icon="la la-money-check-alt" :link="backpack_url('payment-method')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 4. Inventory Management --}}
@if($user->hasPermissionTo('can_view_product', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.inventory.title') }}" icon="la la-boxes text-info">
    <x-backpack::menu-dropdown-item title="{{ __('products.entity.plural') }}" icon="la la-box"
        :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="{{ __('products.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('product-category')" />
</x-backpack::menu-dropdown>
@endif

{{-- 5. System Administration --}}
@if($user->hasPermissionTo('can_view_cron_task', 'backpack') ||
$user->hasPermissionTo('can_view_status', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.system_config.title') }}" icon="la la-cogs text-secondary">
    @if($user->hasPermissionTo('can_view_cron_task', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.automation') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('cron_tasks.entity.plural') }}" icon="la la-calendar"
        :link="backpack_url('cron-task')" />
    @endif

    @if($user->hasPermissionTo('can_view_status', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.statuses') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('statuses.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('status-category')" />
    <x-backpack::menu-dropdown-item title="{{ __('statuses.entity.plural') }}" icon="la la-flag"
        :link="backpack_url('status')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 6. Database Management --}}
@if($user->hasPermissionTo('can_configure_system', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin/database.menu.title') }}" icon="la la-database text-danger">
    <x-backpack::menu-dropdown-item title="{{ __('admin/database.dashboard.title') }}" icon="la la-tachometer-alt"
        :link="backpack_url('database-dashboard')" />
    <x-backpack::menu-dropdown-header title="{{ __('admin/database.menu.monitoring') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin/database.maintenance_logs.entity.plural') }}" icon="la la-list"
        :link="backpack_url('database-maintenance-log')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin/database.performance_metrics.entity.plural') }}" icon="la la-chart-line"
        :link="backpack_url('performance-metric')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin/database.optimization_logs.entity.plural') }}" icon="la la-cogs"
        :link="backpack_url('mysql-optimization-log')" />
    <x-backpack::menu-dropdown-header title="{{ __('admin/database.menu.configuration') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin/database.archive_policies.entity.plural') }}" icon="la la-archive"
        :link="backpack_url('archive-policy')" />
</x-backpack::menu-dropdown>
@endif

{{-- 7. Content Management --}}
@if($user->hasPermissionTo('can_view_page', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.content.title') }}" icon="la la-paperclip  text-success">
    <x-backpack::menu-dropdown-item title="{{ __('pages.categories.entity.plural') }}" icon="la la-tags" :link="backpack_url('page-category')" />
    <x-backpack::menu-dropdown-item title="{{ __('pages.entity.plural') }}" icon="la la-paragraph" :link="backpack_url('page')" />
</x-backpack::menu-dropdown>
@endif

{{-- 8. System Configuration --}}
@if($user->hasPermissionTo('can_configure_system', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.cache.title') }}" icon="la la-cogs text-danger">
    @include('admin.inc.menu_post_item', [
        'title' => __('admin.cache.clear_cache'),
        'icon' => 'la la-trash',
        'action' => route('admin.cache.clear'),
    ])
    @include('admin.inc.menu_post_item', [
        'title' => __('admin.cache.clear_config'),
        'icon' => 'la la-cog',
        'action' => route('admin.cache.clear-config'),
    ])
    @include('admin.inc.menu_post_item', [
        'title' => __('admin.cache.clear_views'),
        'icon' => 'la la-eye',
        'action' => route('admin.cache.clear-views'),
    ])
    @include('admin.inc.menu_post_item', [
        'title' => __('admin.cache.clear_routes'),
        'icon' => 'la la-route',
        'action' => route('admin.cache.clear-routes'),
    ])
    @include('admin.inc.menu_post_item', [
        'title' => __('admin.cache.clear_all'),
        'icon' => 'la la-broom',
        'action' => route('admin.cache.clear-all'),
    ])
</x-backpack::menu-dropdown>
@endif
