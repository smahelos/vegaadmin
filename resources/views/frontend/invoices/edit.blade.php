@extends('layouts.frontend')

@php
/**
* Helper function for getting field by name
*/
if (!function_exists('getEditFieldByName')) {
function getEditFieldByName($fields, $name) {
foreach ($fields as $field) {
if ($field['name'] === $name) {
return $field;
}
}
return null;
}
}

/**
* Generating classes for fields
*/
if (!function_exists('getEditFieldClasses')) {
function getEditFieldClasses($fieldName, $supplierFields, $clientFields, $invoiceFields) {
$classes = [];
if(in_array($fieldName, $invoiceFields)) {
$classes[] = 'bg-blue-50';
}

if(in_array($fieldName, $supplierFields)) {
$classes[] = 'bg-[#FDFDFC] supplier-field';
}

if(in_array($fieldName, $clientFields)) {
$classes[] = 'bg-[#FDFDFC] client-field';
}

return implode(' ', $classes);
}
}

$invoiceFields = ['invoice_vs', 'invoice_ks', 'invoice_ss', 'issue_date', 'tax_point_date', 'payment_method_id',
'due_in', 'payment_amount', 'payment_currency', 'payment_status_id'];
$supplierFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country', 'ico', 'dic', 'account_number',
'bank_code', 'bank_name', 'iban', 'swift'];
$clientFields = ['client_name', 'client_email', 'client_phone', 'client_street', 'client_city', 'client_zip',
'client_country', 'client_ico', 'client_dic'];
$fieldDescription = getEditFieldByName($fields, 'invoice_text');

// Fields for the start and end of the section
$sectionStartFields = ['invoice_vs', 'invoice_ks', 'payment_method_id', 'supplier_id', 'email',
'city', 'ico', 'account_number', 'iban', 'client_id', 'client_email', 'client_city', 'client_ico'];
$sectionEndFields = ['payment_status_id', 'tax_point_date', 'payment_currency', 'supplier_id', 'phone', 'country',
'dic', 'bank_name',
'swift', 'client_id', 'client_phone', 'client_country', 'client_dic'];

// Fields that require special layout
$specialLayoutFields = ['tax_point_date', 'payment_status_id', 'swift'];
@endphp

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">{{
        __('invoices.titles.edit') }}</h1>
    @if($userLoggedIn)

    <div class="flex-none space-x-2 space-y-2 grid grid-cols-2 md:flex md:grid-cols-none md:space-x-0">
        <x-back-button />

        <a href="{{ route('frontend.invoices', ['locale' => app()->getLocale()]) }}"
            title="{{ __('invoices.actions.back_to_list') }}" aria-label="{{ __('invoices.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>
        <span class="hidden"></span>
    </div>
    @endif
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="invoice" limit="{{ $limitsData['limit'] }}"
        entity-name="{{ __('uels.entities.invoice') }}" :show-modal="true" />
</div>
@endauth

<form id="invoice-form" method="POST"
    action="{{ route('frontend.invoice.update', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
    enctype="multipart/form-data" data-user-logged-in="{{ $userLoggedIn ? 'true' : 'false' }}" data-is-editing="true"
    data-supplier-required="{{ __('invoices.validation.supplier_required') }}"
    data-client-required="{{ __('invoices.validation.client_required') }}"
    data-amount-required="{{ __('invoices.validation.amount_required') }}"
    data-amount-numeric="{{ __('invoices.validation.amount_numeric') }}"
    data-processing-text="{{ __('invoices.actions.processing') }}"
    data-error-text="{{ __('invoices.messages.create_error') }}">
    @csrf
    @method('PUT')

    <!-- Section 1: Invoice Details -->
    <div
        class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                __('invoices.sections.invoice_details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-5 md:col-span-4 gap-6">
                @foreach($fields as $field)
                @if($field['name'] !== 'invoice_text' && $field['name'] !== 'template')
                @php
                $fieldClasses = getEditFieldClasses($field['name'], $supplierFields, $clientFields, $invoiceFields);
                @endphp

                @if(in_array($field['name'], $sectionStartFields))
                <div class="grid grid-cols-1
                            @if(
                                $field['name'] === 'payment_amount' ||
                                $field['name'] === 'supplier_id' ||
                                $field['name'] === 'client_id')md:grid-cols-5
                            @elseif(
                                $field['name'] === 'account_number' ||
                                $field['name'] === 'city' ||
                                $field['name'] === 'client_city')md:grid-cols-10
                            @else md:grid-cols-4
                            @endif gap-6 mb-5">
                    @endif

                    @if($field['name'] === 'invoice_logo')
                    <!-- Invoice logo -->
                    <div class="md:col-span-1">
                        <label for="invoice_logo"
                            class="flex flex-col items-center justify-center w-full h-40 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500  transition-colors duration-200 mb-7">
                            <div class="flex flex-col items-center justify-center p-3">
                                <p class="mb-2 text-lg font-semibold text-[#490BF4] dark:text-gray-200">{{
                                    __('invoices.labels.upload_logo') }}</p>
                                <p class="mt-1 text-sm text-gray-500 wrap-break-word">
                                    {{ __('invoices.hints.invoice_logo') }}
                                </p>
                            </div>
                            <input id="invoice_logo" name="invoice_logo" type="file" class="hidden image-input" />
                        </label>
                        <div class="">
                            <div id="image-preview-container">
                                @if ($invoice->invoice_logo)
                                <img id="current-image-preview"
                                    src="{{ Storage::disk('public')->url($invoice->invoice_logo) }}"
                                    alt="{{ $supplier->name }}" class="max-h-40 object-cover rounded-sm">
                                @else
                                <p id="no-image-message" class="text-gray-500">{{ __('invoices.messages.no_image') }}
                                </p>
                                <img id="current-image-preview"
                                    src="{{ Storage::disk('public')->url('suppliers/logos/no_logo.png') }}"
                                    alt="{{ $supplier->name }}" class="max-h-40 object-cover rounded-sm hidden">
                                @endif
                            </div>
                        </div>

                        @error('invoice_logo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <!-- Start 4 col parent grid -->
                    <div class="md:col-span-4 grid grid-cols-1">

                        @elseif($field['name'] === 'invoice_vs')
                        <!-- Invoice number -->
                        <div class="md:col-span-3 mb-5">
                            <label for="invoice_vs"
                                class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                                {{ $field['label'] }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="invoice_vs" id="invoice_vs" required
                                value="{{ old('invoice_vs', $invoice->invoice_vs) }}"
                                class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $fieldClasses }}">
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $field['hint'] }}
                            </p>
                            @endif
                            @error('invoice_vs')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @elseif($field['name'] === 'payment_status_id')
                        <div class="md:col-span-1 mb-5">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $invoice->payment_status_id)"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$statuses"
                                hint="{{ $field['hint'] }}" class="bg-blue-50" labelClass="" />
                        </div>

                        @elseif($field['name'] === 'supplier_id')
                        <!-- Supplier select -->
                        <div class="md:col-span-4">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $invoice->supplier_id)" required="true"
                                :options="$suppliers" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] supplier-select"
                                labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                        </div>
                        <div class="md:col-span-1">
                            @if(isset($field['label']) && $field['label'] !== '')
                            <label class="invisible block text-base font-medium text-gray-500 dark:text-gray-400 mb-2">
                                {{ __('suppliers.actions.edit_short') }}
                            </label>
                            @endif
                            <a href="{{ route('frontend.supplier.edit', ['id' => $invoice->supplier_id, 'locale' => app()->getLocale()]) }}"
                                id="edit-supplier-link"
                                class="inline-flex justify-center py-2 px-5 shadow-xl shadow-indigo-600/30 text-md font-semibold rounded-sm text-white bg-[#490BF4] hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 {{ empty($invoice->supplier_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                {{ __('suppliers.actions.edit_short') }}
                            </a>
                        </div>

                        @elseif($field['name'] === 'client_id')
                        <!-- Client select -->
                        <div class="md:col-span-4">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $invoice->client_id)" required="true" :options="$clients"
                                hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] client-select" labelClass=""
                                allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                        </div>
                        <div class="md:col-span-1">
                            @if(isset($field['label']) && $field['label'] !== '')
                            <label class="invisible block text-base font-medium text-gray-500 dark:text-gray-400 mb-2">
                                {{ __('clients.actions.edit_short') }}
                            </label>
                            @endif
                            <a href="{{ route('frontend.client.edit', ['id' => $invoice->client_id ?? 1, 'locale' => app()->getLocale()]) }}"
                                id="edit-client-link"
                                class="inline-flex justify-center py-2 px-5 shadow-xl shadow-indigo-600/30 text-md font-semibold rounded-sm text-white bg-[#490BF4] hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 {{ empty($invoice->client_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                {{ __('clients.actions.edit_short') }}
                            </a>
                        </div>

                        @elseif($field['name'] === 'payment_method_id')
                        <!-- Payment method -->
                        <div class="md:col-span-1">
                            @php
                            if(isset($paymentMethods)) {
                            foreach($paymentMethods as $key => $method) {
                            $paymentMethods[$key] = __('payment_methods.' . $method);
                            }
                            }
                            $marginBottomClass = !isset($field['hint']) || $field['hint'] === '' ? 'mb-6' : '';
                            @endphp
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" :selected="old($field['name'], $invoice->payment_method_id)"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                :options="$paymentMethods" hint="{{ $field['hint'] }}"
                                class="bg-blue-50 {{ $marginBottomClass }}" labelClass="" />
                        </div>

                        @elseif($field['name'] === 'payment_currency')
                        <div class="md:col-span-1">
                            <x-currency-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" :selected="old($field['name'], $invoice->payment_currency)"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                hint="{{ $field['hint'] }}" class="bg-red-50" />
                        </div>
                    </div> <!-- Close 4 col parent grid -->

                    @elseif($field['name'] === 'due_in')
                    <!-- Invoice due in -->
                    @php
                    $dueInOptions = [
                    7 => '7 ' . __('invoices.units.days'),
                    14 => '14 ' . __('invoices.units.days'),
                    21 => '21 ' . __('invoices.units.days'),
                    30 => '30 ' . __('invoices.units.days')
                    ];
                    @endphp
                    <div class="md:col-span-1">
                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}" id="{{ $field['name'] }}"
                            :selected="old($field['name'], $invoice->due_in)"
                            required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$dueInOptions"
                            hint="{{ $field['hint'] }}" class="bg-blue-50" labelClass="" />
                    </div>

                    @elseif($field['name'] === 'country')
                    <div class="md:col-span-3 mb-5">
                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}" id="{{ $field['name'] }}"
                            valueField="id" :selected="old($field['name'], $userInfo['country'] ?? 'CZ')"
                            required="{{ isset($field['required']) ? $field['required'] : ''}}"
                            :options="$field['options']" hint="{{ $field['hint'] }}" labelClass="" allowsNull="true"
                            placeholder="{{ $field['placeholder'] }}"
                            class="{{ in_array($field['name'], $supplierFields) ? 'bg-[#FDFDFC] supplier-field country-select' : '' }}" />
                    </div>

                    @elseif($field['name'] === 'client_country')
                    <div class="md:col-span-3 mb-5">
                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}" id="{{ $field['name'] }}"
                            valueField="id" :selected="old($field['name'], $userInfo['client_country'] ?? 'CZ')"
                            required="{{ isset($field['required']) ? $field['required'] : ''}}"
                            :options="$field['options']" hint="{{ $field['hint'] }}" labelClass="" allowsNull="true"
                            placeholder="{{ $field['placeholder'] }}"
                            class="{{ in_array($field['name'], $clientFields) ? 'bg-[#FDFDFC] client-field country-select' : '' }}" />
                    </div>

                    @elseif($field['name'] === 'bank_code')
                    <div class="md:col-span-3">
                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}" id="{{ $field['name'] }}"
                            :selected="old($field['name'], $invoice->bank_code ?? $defaultSupplier['bank_code'] ?? '')"
                            required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$banks"
                            placeholder="{{ __('suppliers.placeholders.bank_code') }}" hint="{{ $field['hint'] }}"
                            class="bg-[#FDFDFC] supplier-field" labelClass="" />
                    </div>

                    @elseif($field['name'] === 'invoice_ks')
                    <div class="md:col-span-1">
                        <label for="{{ $field['name'] }}"
                            class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] }}
                            @if(isset($field['required']) && $field['required'] === true && $field['name'] !== 'name' &&
                            $field['name'] !== 'client_name')
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input data-help="{{ old($field['name'], $invoice->{$field['name']}) }}"
                            name="{{ $field['name'] }}" id="{{ $field['name'] }}" @if($field['name']==='issue_date' ||
                            $field['name']==='tax_point_date' )
                            value="{{ old($field['name'], $invoice->{$field['name']}->format('Y-m-d')) }}" @else
                            value="{{ old($field['name'], $invoice->{$field['name']}) }}" @endif
                            @if(isset($field['required']) && $field['required']===true) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $fieldClasses }}">
                        @if(isset($field['hint']) && $field['hint'] !== '')
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $field['hint'] }}
                        </p>
                        @endif
                        @error($field['name'])
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @else
                    <!-- Other fields -->
                    @php
                    $containerId = '';
                    if ($field['name'] === 'name') {
                    $containerId = 'supplier_name_container';
                    } elseif ($field['name'] === 'client_name') {
                    $containerId = 'client_name_container';
                    }
                    $marginClass = in_array($field['name'], ['name', 'street', 'client_name', 'client_street']) ?
                    'mb-5' : '';

                    $columnSpan = '';
                    if(in_array($field['name'], ['account_number', 'city', 'client_city'])) {
                    $columnSpan = 'md:col-span-4';
                    } elseif(in_array($field['name'], ['bank_code', 'bank_name', 'zip', 'client_zip'])) {
                    $columnSpan = 'md:col-span-3';
                    } elseif(in_array($field['name'], ['ico', 'dic', 'email', 'phone', 'iban', 'swift', 'client_ico',
                    'client_dic', 'client_email', 'client_phone'])) {
                    $columnSpan = 'md:col-span-2';
                    } else {
                    $columnSpan = 'md:col-span-1';
                    }
                    @endphp
                    <div class="{{ $marginClass }} {{ $columnSpan }}" id="{{ $containerId }}">
                        <label for="{{ $field['name'] }}"
                            class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] }}
                            @if(isset($field['required']) && $field['required'] === true && $field['name'] !== 'name' &&
                            $field['name'] !== 'client_name')
                            <span class="text-red-500">*</span>
                            @elseif($field['name'] === 'name')
                            <span id="supplier-name-required"
                                class="text-red-500 {{ $invoice->supplier_id ? 'hidden' : '' }}">*</span>
                            @elseif($field['name'] === 'client_name')
                            <span class="text-red-500" id="client-name-required"
                                class="{{ $invoice->client_id ? 'hidden' : '' }}">*</span>
                            @endif
                        </label>
                        <input type="{{ $field['type'] }}"
                            data-help="{{ old($field['name'], $invoice->{$field['name']}) }}"
                            name="{{ $field['name'] }}" id="{{ $field['name'] }}" @if($field['name']==='issue_date' ||
                            $field['name']==='tax_point_date' )
                            value="{{ old($field['name'], $invoice->{$field['name']}->format('Y-m-d')) }}" @else
                            value="{{ old($field['name'], $invoice->{$field['name']}) }}" @endif
                            @if(isset($field['required']) && $field['required']===true) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $fieldClasses }}">
                        @if(isset($field['hint']) && $field['hint'] !== '')
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $field['hint'] }}
                        </p>
                        @endif
                        @error($field['name'])
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    @if(in_array($field['name'], $sectionEndFields))
                </div>
                @endif

                @if($field['name'] === 'payment_currency')
            </div> <!-- close 5 col grid -->
        </div> <!-- close p-6 -->
    </div> <!-- close bg-white -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('invoices.sections.seller_details') }}</h2>
                <div>

                    @elseif($field['name'] === 'swift')
                </div>
            </div>
        </div>
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('invoices.sections.client_details') }}</h2>
                <div>

                    @elseif($field['name'] === 'client_country')
                </div>
                @endif

                @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Section 4: Invoice description -->
    <div
        class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('invoices.sections.other_info')
                }}</h2>

            <!-- Hidden filed for products data -->
            <input type="hidden" name="invoice-products" id="invoice-products" />

            <div>
                <label for="invoice-products" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('invoices.fields.invoice_items') }}
                </label>
                <div id="invoice-items-container">
                    <!-- Head of items table -->
                    <div class="grid-cols-4 md:grid-cols-12 lg:grid-cols-24 gap-4 mb-2 hidden lg:grid">
                        <div class="col-span-4 md:col-span-6 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_name') }}</div>
                        <div class="col-span-4 md:col-span-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_quantity') }}</div>
                        <div class="col-span-4 md:col-span-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_unit') }}</div>
                        <div class="col-span-4 md:col-span-3 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_price') }}</div>
                        <div class="col-span-4 md:col-span-3 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_currency') }}</div>
                        <div class="col-span-4 md:col-span-3 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_tax') }}</div>
                        <div class="col-span-4 md:col-span-3 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.item_price_complete') }}</div>
                        <div class="col-span-4 md:col-span-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.placeholders.actions')
                            }}</div>
                    </div>
                    <!-- Template for invoice item -->
                    <div class="invoice-item-template hidden">
                        <div class="invoice-item grid grid-cols-4 md:grid-cols-12 lg:grid-cols-24 gap-4 mb-3">
                            <div class="col-span-4 md:col-span-6">
                                <label
                                    class="block text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_name') }}</label>
                                <div class="grid grid-cols-4 md:grid-cols-6">
                                    <input type="text"
                                        class="item-name @if($userLoggedIn)col-span-3 md:col-span-5 @else col-span-4 md:col-span-6 @endif form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                        placeholder="{{ __('invoices.placeholders.item_name') }}">
                                    <button type="button" title="{{ __('invoices.placeholders.select_product') }}"
                                        class="select-product ml-1 px-2 py-1 border border-blue-300 dark:border-gray-600 rounded-sm cursor-pointer text-white dark:text-gray-200 bg-emerald-500 dark:bg-emerald-600 hover:bg-emerald-600 dark:hover:bg-emerald-700 transition-colors duration-200">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-span-2 md:col-span-2">
                                <label
                                    class="block col-span-2 md:col-span-2 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_quantity') }}</label>
                                <input type="number"
                                    class="item-quantity form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                    placeholder="{{ __('invoices.placeholders.item_quantity') }}" step="0.5" min="0"
                                    value="1">
                            </div>
                            <div class="col-span-2 md:col-span-2">
                                <label
                                    class="block col-span-2 md:col-span-2 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_unit') }}</label>
                                <select
                                    class="item-unit form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                                    @foreach($itemUnits as $key => $unit)
                                    <option value="{{ $key }}">{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2 md:col-span-2 lg:col-span-3">
                                <label
                                    class="block col-span-2 md:col-span-3 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_price') }}</label>
                                <input type="number"
                                    class="item-price form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                    placeholder="{{ __('invoices.placeholders.item_price') }}" step="0.01" min="0"
                                    value="0">
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <label
                                    class="block col-span-2 md:col-span-3 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_currency') }}</label>
                                <x-currency-select name="item-currency" label="" id="item-currency"
                                    class="item-currency" />
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <label
                                    class="block col-span-2 md:col-span-3 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_tax') }}</label>
                                <select
                                    class="item-tax form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                                    <option value="0">0%</option>
                                    @foreach($taxRates as $key => $rate)
                                    <option value="{{ $rate }}">{{ $rate }}%</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2 md:col-span-4 lg:col-span-3">
                                <label
                                    class="block col-span-2 md:col-span-3 text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">{{
                                    __('invoices.placeholders.item_price_complete') }}</label>
                                <input type="text"
                                    class="item-price-complete form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                    placeholder="{{ __('invoices.placeholders.item_price_complete') }}" readonly>
                            </div>
                            <div class="col-span-4 md:col-span-2">
                                <label
                                    class="block text-base font-medium text-gray-600 dark:text-gray-400 mb-2 lg:hidden">&nbsp;</label>
                                <div class="flex items-center space-x-2">
                                    <button type="button" title="{{ __('invoices.labels.duplicate_item') }}"
                                        class="w-1/2 inline-flex justify-center py-3 px-3 text-md font-semibold rounded-sm text-white dark:text-gray-200 bg-green-600 dark:bg-green-700 hover:bg-green-400 dark:hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer duplicate-item transition-colors duration-200">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button"
                                        class="w-1/2 inline-flex justify-center py-3 px-3 text-md font-semibold rounded-sm text-white dark:text-gray-200 bg-red-700 hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer remove-item transition-colors duration-200">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="invoice-items-list">
                        <!-- Place to generate items dynamicaly -->
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" id="add-invoice-item"
                        class="mt-2 py-2 px-5 shadow-sm dark:shadow-none shadow-indigo-600/30 text-md font-semibold rounded-sm text-white hover:text-white bg-[#490BF4] hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
                        <i class="fas fa-plus mr-2 py-1"></i>{{ __('invoices.actions.add_item') }}
                    </button>
                </div>

                <!-- Add field for message -->
                <div class="mt-6">
                    <label for="invoice_text" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.fields.invoice_note') }}
                    </label>
                    <textarea name="invoice_text" id="invoice_text" rows="3"
                        class="form-textarea mt-1 block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">{{ old('invoice_text', $invoice->invoice_text) }}</textarea>
                    @if(isset($fieldDescription['hint']) && $fieldDescription['hint'] !== '')
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $fieldDescription['hint'] }}
                    </p>
                    @endif
                    @error('invoice_text')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    <div class="flex justify-end mb-10">
        <!-- Invoice items total sum -->
        <div class="grid grid-cols-12 mt-5 mb-4 pt-3">
            <div class="col-span-10 text-3xl font-medium text-gray-900 dark:text-white text-left pr-2 pt-2">
                {{ __('invoices.labels.total_without_tax') }}:</div>
            <div class="col-span-2 font-bold text-3xl text-right pl-2 pt-2 dark:text-white"
                id="invoice-items-total-without-tax">
                0.00</div>
            <div class="col-span-10 text-3xl font-medium text-gray-900 dark:text-white text-left pr-2 pt-2">
                {{ __('invoices.labels.total_tax') }}:</div>
            <div class="col-span-2 font-bold text-3xl text-right pl-2 pt-2 dark:text-white"
                id="invoice-items-total-tax">
                0.00</div>
            <div
                class="col-span-10 text-3xl font-medium text-gray-900 dark:text-white text-left pr-2 pt-2 mt-2 border-t-3 border-[#490BF4] dark:border-gray-700">
                {{ __('invoices.fields.total') }}:</div>
            <div class="col-span-2 font-bold text-3xl text-right pl-2 pt-2 mt-2 border-t-3 border-[#490BF4] dark:border-gray-700 dark:text-white"
                id="invoice-items-total">
                0.00</div>
        </div>
    </div>

    <div class="flex justify-between mc-20 md:mb-10">
        <div class="flex space-x-4">
            <a href="{{ route('frontend.invoices', ['locale' => app()->getLocale()]) }}"
                class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                {{ __('invoices.actions.cancel') }}
            </a>

            <!-- Template Settings Button -->
            <button type="button"
                class="template-settings-btn inline-flex items-center px-6 py-6 border border-gray-300 dark:border-gray-600 shadow-xl shadow-gray-400/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-600 dark:hover:text-white bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 cursor-pointer">
                <i class="fas fa-cog mr-2"></i>
                <span class="mr-2">{{ __('invoices.labels.template_settings') }}:</span>
                <span class="template-name">{{ __('invoices.templates.' . ($invoice->template ?? 'default')) }}</span>
            </button>
        </div>

        <div class="flex">
            <button type="submit"
                class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
                <i class="fas fa-save mr-2"></i>
                {{ __('invoices.actions.update') }}
            </button>
        </div>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
    <input type="hidden" name="tax_amount" value="0">
    <input type="hidden" name="payment_without_tax_amount" value="0">
    <input type="hidden" name="template" value="{{ $invoice->template ?? 'default' }}">
</form>

<!-- Product Selection Modal -->
<div id="product-selection-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

        <!-- Dialog -->
        <div
            class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-medium leading-6 text-gray-900 dark:text-white">
                    {{ __('products.titles.select_product') }}
                </h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('common.actions.close') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="product-list-content">
                @livewire('product.product-list-select')
            </div>
        </div>
    </div>
</div>

<!-- Template Selector Modal -->
<div id="template-selector-modal" class="fixed inset-0 bg-black/50 hidden z-50" style="display: none;">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{
                    __('invoices.labels.template_settings') }}</h3>
                <button type="button"
                    class="template-modal-close text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('invoices.labels.select_template') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Default Template -->
                <div class="template-option border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors"
                    data-template="default">
                    <div class="p-4">
                        <div
                            class="template-preview bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 h-32 flex items-center justify-center">
                            <img src="/assets/images/templates/default-preview.svg"
                                alt="{{ __('invoices.templates.default') }} template preview"
                                class="max-w-full max-h-full object-contain"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="text-sm">{{ __('invoices.templates.default') }}</span>
                            </div>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-center">{{
                            __('invoices.templates.default') }}</h4>
                        <div class="mt-2 flex justify-center">
                            <div
                                class="template-radio w-4 h-4 border-2 border-gray-300 dark:border-gray-500 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-blue-600 rounded-full hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modern Template -->
                <div class="template-option border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors"
                    data-template="modern">
                    <div class="p-4">
                        <div
                            class="template-preview bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 h-32 flex items-center justify-center">
                            <img src="/assets/images/templates/modern-preview.svg"
                                alt="{{ __('invoices.templates.modern') }} template preview"
                                class="max-w-full max-h-full object-contain"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="text-sm">{{ __('invoices.templates.modern') }}</span>
                            </div>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-center">{{
                            __('invoices.templates.modern') }}</h4>
                        <div class="mt-2 flex justify-center">
                            <div
                                class="template-radio w-4 h-4 border-2 border-gray-300 dark:border-gray-500 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-blue-600 rounded-full hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimal Template -->
                <div class="template-option border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors"
                    data-template="minimal">
                    <div class="p-4">
                        <div
                            class="template-preview bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 h-32 flex items-center justify-center">
                            <img src="/assets/images/templates/minimal-preview.svg"
                                alt="{{ __('invoices.templates.minimal') }} template preview"
                                class="max-w-full max-h-full object-contain"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="text-sm">{{ __('invoices.templates.minimal') }}</span>
                            </div>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-center">{{
                            __('invoices.templates.minimal') }}</h4>
                        <div class="mt-2 flex justify-center">
                            <div
                                class="template-radio w-4 h-4 border-2 border-gray-300 dark:border-gray-500 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-blue-600 rounded-full hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <button type="button"
                    class="template-modal-close px-4 py-2 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ __('general.actions.cancel') }}
                </button>
                <button type="button" id="confirm-template-selection"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    disabled>
                    {{ __('general.actions.confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth
@endsection

@push('scripts')
<script>
    // Make bank options available to the bank-fields.js script
    window.bankOptions = {{ Js::from($banksData) }};

    // Provide existing invoice data to the invoice-form.js script
    window.existingInvoiceData = @json($invoice->invoiceProductsData ?? []);
</script>
@vite('resources/js/bank-fields.js')
@vite('resources/js/ares-lookup.js')
@vite('resources/js/invoice-form.js')
@vite('resources/js/image-preview.js')
@vite('resources/js/template-selector.js')

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
@endpush
