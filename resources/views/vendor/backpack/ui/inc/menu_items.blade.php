{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

@if(backpack_user()->can('can_create_edit_user'))
<x-backpack::menu-dropdown title="Add-ons" icon="la la-puzzle-piece">
    <x-backpack::menu-dropdown-header title="Authentication" />
    <x-backpack::menu-dropdown-item title="Users" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>
@endif

@if(backpack_user()->can('can_configure_system'))
<x-backpack::menu-dropdown title="{{ trans('admin.system.settings') }}" icon="la la-tools">
    @if(backpack_user()->can('can_create_edit_tax'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.taxes.taxes') }}" icon="la la-percentage" :link="backpack_url('tax')" />
    @endif
    @if(backpack_user()->can('can_create_edit_bank'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.banks.banks') }}" icon="la la-bank" :link="backpack_url('bank')" />
    @endif
    @if(backpack_user()->can('can_create_edit_payment_method'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.payment_methods.payment_methods') }}" icon="la la-money-check-alt" :link="backpack_url('payment-method')" />
    @endif
    @if(backpack_user()->can('can_create_edit_cron_tasks'))
    <x-backpack::menu-dropdown-item title="{{ trans('admin.cron_tasks.cron_tasks') }}" icon="la la-calendar" :link="backpack_url('cron-task')" />
    @endif
</x-backpack::menu-dropdown>
@endif

@if(backpack_user()->can('can_create_edit_invoice'))
<x-backpack::menu-dropdown title="{{ trans('admin.invoices.invoices') }}" icon="la la-file-invoice">
    <x-backpack::menu-dropdown-item title="{{ trans('admin.invoices.invoices') }}" icon="la la-file-invoice" :link="backpack_url('invoice')" />
    @if(backpack_user()->can('can_create_edit_client'))
        <x-backpack::menu-dropdown-item title="{{ trans('admin.clients.clients') }}" icon="la la-handshake" :link="backpack_url('client')" />
    @endif
    @if(backpack_user()->can('can_create_edit_supplier'))
        <x-backpack::menu-dropdown-item title="{{ trans('admin.suppliers.suppliers') }}" icon="la la-handshake" :link="backpack_url('artisan-command-category')" />
    @endif
</x-backpack::menu-dropdown>
@endif

@if(backpack_user()->can('can_create_edit_commands'))
<x-backpack::menu-dropdown title="{{ trans('admin.artisan_commands.commands') }}" icon="la la-hammer">
    <x-backpack::menu-dropdown-item title="{{ trans('admin.artisan_commands.commands') }}" icon="la la-hammer" :link="backpack_url('artisan-command')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.artisan_commands.categories') }}" icon="la la-list" :link="backpack_url('artisan-command-category')" />
</x-backpack::menu-dropdown>
@endif

@if(backpack_user()->can('can_create_edit_products'))
<x-backpack::menu-dropdown title="{{ trans('admin.products.products') }}" icon="la la-box">
    <x-backpack::menu-dropdown-item title="{{ trans('admin.products.products') }}" icon="la la-box" :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="{{ trans('admin.products.product_categories') }}" icon="la la-list" :link="backpack_url('product-category')" />
</x-backpack::menu-dropdown>
@endif
