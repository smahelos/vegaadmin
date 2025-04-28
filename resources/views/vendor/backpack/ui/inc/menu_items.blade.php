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

@if(backpack_user()->can('can_create_edit_invoice'))
<x-backpack::menu-item title="Invoices" icon="la la-question" :link="backpack_url('invoice')" />
@endif
@if(backpack_user()->can('can_create_edit_client'))
<x-backpack::menu-item title="Clients" icon="la la-question" :link="backpack_url('client')" />
@endif
@if(backpack_user()->can('can_create_edit_payment_method'))
<x-backpack::menu-item title="Payment methods" icon="la la-question" :link="backpack_url('payment-method')" />
@endif

<x-backpack::menu-item title="Statuses" icon="la la-question" :link="backpack_url('status')" />
<x-backpack::menu-item title="Suppliers" icon="la la-question" :link="backpack_url('supplier')" />