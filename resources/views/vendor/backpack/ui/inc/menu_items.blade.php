{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{
        trans('backpack::base.dashboard') }}</a></li>

{{-- 1. User Management --}}
@if(backpack_user()->hasPermissionTo('can_create_edit_user', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.user_management.title') }}" icon="la la-users text-primary">
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.users') }}" icon="la la-user"
        :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.roles') }}" icon="la la-group"
        :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.user_management.permissions') }}" icon="la la-key"
        :link="backpack_url('permission')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.subscriptions.entity_plural') }}" icon="la la-handshake"
        :link="backpack_url('subscription')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.subscription_plans.entity_plural') }}" icon="la la-id-card"
        :link="backpack_url('subscription-plan')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.subscription_plan_features.entity_plural') }}"
        icon="la la-check-square" :link="backpack_url('subscription-plan-feature')" />
</x-backpack::menu-dropdown>
@endif

{{-- 2. Business Operations --}}
@if(backpack_user()->hasPermissionTo('can_create_edit_invoice', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_client', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_supplier', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.business.title') }}" icon="la la-briefcase text-success">
    @if(backpack_user()->hasPermissionTo('can_create_edit_invoice', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.invoicing') }}" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.invoices.invoices') }}" icon="la la-file-invoice"
        :link="backpack_url('invoice')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_client', 'backpack') ||
    backpack_user()->hasPermissionTo('can_create_edit_supplier', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.business.partners') }}" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_client', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.clients.clients') }}" icon="la la-handshake"
        :link="backpack_url('client')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_supplier', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.suppliers.suppliers') }}" icon="la la-truck"
        :link="backpack_url('supplier')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 3. Financial Management --}}
@if(backpack_user()->hasPermissionTo('can_create_edit_expense', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_tax', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_bank', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_payment_method', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.finance.title') }}" icon="la la-coins text-warning">
    @if(backpack_user()->hasPermissionTo('can_create_edit_expense', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.expenses') }}" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.expenses.expenses') }}" icon="la la-receipt"
        :link="backpack_url('expense')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.expenses.expense_categories') }}" icon="la la-tags"
        :link="backpack_url('expense-category')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_tax', 'backpack') ||
    backpack_user()->hasPermissionTo('can_create_edit_bank', 'backpack') ||
    backpack_user()->hasPermissionTo('can_create_edit_payment_method', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.finance.settings') }}" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_tax', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.taxes.taxes') }}" icon="la la-percentage"
        :link="backpack_url('tax')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_bank', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.banks.banks') }}" icon="la la-bank"
        :link="backpack_url('bank')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_payment_method', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.payment_methods.payment_methods') }}"
        icon="la la-money-check-alt" :link="backpack_url('payment-method')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 4. Inventory Management --}}
@if(backpack_user()->hasPermissionTo('can_create_edit_product', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.inventory.title') }}" icon="la la-boxes text-info">
    <x-backpack::menu-dropdown-item title="{{ trans('admin.products.products') }}" icon="la la-box"
        :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.products.product_categories') }}" icon="la la-tags"
        :link="backpack_url('product-category')" />
</x-backpack::menu-dropdown>
@endif

{{-- 5. System Administration --}}
@if(backpack_user()->hasPermissionTo('can_configure_system', 'backpack') &&
(backpack_user()->hasPermissionTo('can_create_edit_command', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_cron_task', 'backpack') ||
backpack_user()->hasPermissionTo('can_create_edit_status', 'backpack')))
<x-backpack::menu-dropdown title="{{ __('admin.system_config.title') }}" icon="la la-cogs text-secondary">
    @if(backpack_user()->hasPermissionTo('can_create_edit_command', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.automation') }}" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.artisan_commands.commands') }}" icon="la la-hammer"
        :link="backpack_url('artisan-command')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.artisan_commands.categories') }}" icon="la la-tags"
        :link="backpack_url('artisan-command-category')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_cron_task', 'backpack'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.cron_tasks.cron_tasks') }}" icon="la la-calendar"
        :link="backpack_url('cron-task')" />
    @endif

    @if(backpack_user()->hasPermissionTo('can_create_edit_status', 'backpack'))
    <x-backpack::menu-dropdown-header title="{{ __('admin.system_admin.statuses') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.system_admin.status_categories') }}" icon="la la-tags"
        :link="backpack_url('status-category')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.statuses.statuses') }}" icon="la la-flag"
        :link="backpack_url('status')" />
    @endif
</x-backpack::menu-dropdown>
@endif

{{-- 6. Database Management --}}
@if(backpack_user()->hasPermissionTo('can_configure_system', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.database.database_management') }}" icon="la la-database text-danger">
    <x-backpack::menu-dropdown-item title="{{ __('admin.database.dashboard') }}" icon="la la-tachometer-alt"
        :link="backpack_url('database-dashboard')" />
    <x-backpack::menu-dropdown-header title="{{ __('admin.database.monitoring') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.database.maintenance_logs') }}" icon="la la-list"
        :link="backpack_url('database-maintenance-log')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.database.performance_metrics') }}" icon="la la-chart-line"
        :link="backpack_url('performance-metric')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.database.optimization_logs') }}" icon="la la-cogs"
        :link="backpack_url('mysql-optimization-log')" />
    <x-backpack::menu-dropdown-header title="{{ __('admin.database.configuration') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.database.archive_policies') }}" icon="la la-archive"
        :link="backpack_url('archive-policy')" />
</x-backpack::menu-dropdown>
@endif

{{-- 7. Content Management --}}
@if(backpack_user()->hasPermissionTo('can_create_edit_page', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.pages.content_management') }}" icon="la la-paperclip  text-success">
    <x-backpack::menu-dropdown-item title="Page categories" icon="la la-tags" :link="backpack_url('page-category')" />
    <x-backpack::menu-dropdown-item title="Pages" icon="la la-paragraph" :link="backpack_url('page')" />
</x-backpack::menu-dropdown>
@endif

{{-- 8. Entity Limits Management (UELS) --}}
@if(backpack_user()->hasPermissionTo('can_configure_system', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.uels.title') }}" icon="la la-shield-alt text-purple">
    <x-backpack::menu-dropdown-item title="{{ __('admin.uels.dashboard') }}" icon="la la-tachometer-alt"
        :link="backpack_url('uels-dashboard')" />

    <x-backpack::menu-dropdown-header title="{{ __('admin.uels.configuration') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.uels.entity_limits') }}" icon="la la-cog"
        :link="backpack_url('entity-limit')" />

    <x-backpack::menu-dropdown-header title="{{ __('admin.uels.monitoring') }}" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.uels.usage_tracking') }}" icon="la la-chart-bar"
        :link="backpack_url('entity-usage')" />
</x-backpack::menu-dropdown>
@endif

{{-- 9. System Configuration --}}
@if(backpack_user()->hasPermissionTo('can_configure_system', 'backpack'))
<x-backpack::menu-dropdown title="{{ __('admin.cache.title') }}" icon="la la-cogs text-danger">
    <x-backpack::menu-dropdown-item title="{{ __('admin.cache.clear_cache') }}" icon="la la-trash"
        :link="route('admin.cache.clear')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.cache.clear_config') }}" icon="la la-cog"
        :link="route('admin.cache.clear-config')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.cache.clear_views') }}" icon="la la-eye"
        :link="route('admin.cache.clear-views')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.cache.clear_routes') }}" icon="la la-route"
        :link="route('admin.cache.clear-routes')" />
    <x-backpack::menu-dropdown-item title="{{ __('admin.cache.clear_all') }}" icon="la la-broom"
        :link="route('admin.cache.clear-all')" />
</x-backpack::menu-dropdown>
@endif
