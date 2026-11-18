@use('App\Domain\User\Enums\Permission')
{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{
        trans('backpack::base.dashboard') }}</a></li>

{{-- 1. User Management --}}
@holds(
    Permission::USER_VIEW,
    Permission::ROLE_VIEW,
    Permission::PERMISSION_VIEW,
    Permission::ROLE_LIMIT_VIEW,
    Permission::SUBSCRIPTION_VIEW,
    Permission::SUBSCRIPTION_PLAN_VIEW,
    Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW
)
<x-backpack::menu-dropdown title="{{ __('admin.user_management.title') }}" icon="la la-users text-primary">
    @holds(Permission::USER_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.users') }}" icon="la la-user"
        :link="backpack_url('user')" />
    @endholds
    @holds(Permission::ROLE_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.roles') }}" icon="la la-group"
        :link="backpack_url('role')" />
    @endholds
    @holds(Permission::PERMISSION_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.permissions') }}" icon="la la-key"
        :link="backpack_url('permission')" />
    @endholds
    @holds(Permission::ROLE_LIMIT_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('role_limits.entity.plural') }}" icon="la la-sliders-h"
        :link="backpack_url('role-limit')" />
    @endholds
    @holds(Permission::SUBSCRIPTION_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.entity.plural') }}" icon="la la-handshake"
        :link="backpack_url('subscription')" />
    @endholds
    @holds(Permission::SUBSCRIPTION_PLAN_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.plans.entity.plural') }}" icon="la la-id-card"
        :link="backpack_url('subscription-plan')" />
    @endholds
    @holds(Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('subscriptions.features.entity.plural') }}"
        icon="la la-check-square" :link="backpack_url('subscription-plan-feature')" />
    @endholds
</x-backpack::menu-dropdown>
@endholds

{{-- 2. Business Operations --}}
@holds(Permission::INVOICE_VIEW, Permission::BUSINESS_VIEW, Permission::CONTACT_VIEW)
<x-backpack::menu-dropdown title="{{ __('admin.business.title') }}" icon="la la-briefcase text-success">
    @holds(Permission::INVOICE_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.invoicing') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('invoices.entity.plural') }}" icon="la la-file-invoice"
        :link="backpack_url('invoice')" />
    @endholds

    @holds(Permission::BUSINESS_VIEW, Permission::CONTACT_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.partners') }}" />
    @endholds

    @holds(Permission::BUSINESS_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('businesses.entity.plural') }}" icon="la la-building"
        :link="backpack_url('business')" />
    @endholds

    @holds(Permission::CONTACT_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('contacts.entity.plural') }}" icon="la la-address-book"
        :link="backpack_url('contact')" />
    @endholds
</x-backpack::menu-dropdown>
@endholds

{{-- 3. Financial Management --}}
@holds(Permission::EXPENSE_VIEW, Permission::TAX_VIEW, Permission::BANK_VIEW, Permission::PAYMENT_METHOD_VIEW)
<x-backpack::menu-dropdown title="{{ __('admin.finance.title') }}" icon="la la-coins text-warning">
    @holds(Permission::EXPENSE_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.expenses') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('expenses.entity.plural') }}" icon="la la-receipt"
        :link="backpack_url('expense')" />
    <x-backpack::menu-dropdown-item title="{{ __('expenses.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('expense-category')" />
    @endholds

    @holds(Permission::TAX_VIEW, Permission::BANK_VIEW, Permission::PAYMENT_METHOD_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.settings') }}" />
    @endholds

    @holds(Permission::TAX_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('taxes.entity.plural') }}" icon="la la-percentage"
        :link="backpack_url('tax')" />
    @endholds

    @holds(Permission::BANK_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('banks.entity.plural') }}" icon="la la-bank"
        :link="backpack_url('bank')" />
    @endholds

    @holds(Permission::PAYMENT_METHOD_VIEW)
    <x-backpack::menu-dropdown-item title="{{ __('payment_methods.entity.plural') }}"
        icon="la la-money-check-alt" :link="backpack_url('payment-method')" />
    @endholds
</x-backpack::menu-dropdown>
@endholds

{{-- 4. Inventory Management --}}
@holds(Permission::PRODUCT_VIEW)
<x-backpack::menu-dropdown title="{{ __('admin.inventory.title') }}" icon="la la-boxes text-info">
    <x-backpack::menu-dropdown-item title="{{ __('products.entity.plural') }}" icon="la la-box"
        :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="{{ __('products.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('product-category')" />
</x-backpack::menu-dropdown>
@endholds

{{-- 5. System Administration --}}
@holds(Permission::CRON_TASK_VIEW, Permission::STATUS_VIEW)
<x-backpack::menu-dropdown title="{{ __('admin.system_config.title') }}" icon="la la-cogs text-secondary">
    @holds(Permission::CRON_TASK_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.automation') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('cron_tasks.entity.plural') }}" icon="la la-calendar"
        :link="backpack_url('cron-task')" />
    @endholds

    @holds(Permission::STATUS_VIEW)
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.statuses') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('statuses.categories.entity.plural') }}" icon="la la-tags"
        :link="backpack_url('status-category')" />
    <x-backpack::menu-dropdown-item title="{{ __('statuses.entity.plural') }}" icon="la la-flag"
        :link="backpack_url('status')" />
    @endholds
</x-backpack::menu-dropdown>
@endholds

{{-- 6. Database Management --}}
@holds(Permission::SYSTEM_CONFIGURE)
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
@endholds

{{-- 7. Content Management --}}
@holds(Permission::PAGE_VIEW)
<x-backpack::menu-dropdown title="{{ __('admin.content.title') }}" icon="la la-paperclip  text-success">
    <x-backpack::menu-dropdown-item title="{{ __('pages.categories.entity.plural') }}" icon="la la-tags" :link="backpack_url('page-category')" />
    <x-backpack::menu-dropdown-item title="{{ __('pages.entity.plural') }}" icon="la la-paragraph" :link="backpack_url('page')" />
</x-backpack::menu-dropdown>
@endholds

{{-- 8. System Configuration --}}
@holds(Permission::SYSTEM_CONFIGURE)
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
@endholds
