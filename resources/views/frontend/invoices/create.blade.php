@extends('layouts.frontend')

@php
    /**
    * Helper for getting field by name
    */
    function getFieldByName($fields, $name) {
        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
    * Generating classes for fields
    */
    function getFieldClasses($fieldName, $supplierFields, $clientFields, $invoiceFields) {
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

    /**
    * Generating common attributes for input fields
    */
    function renderInputAttributes($field, $supplierFields, $userInfo, $suggestedNumber = false) {
        $attributes = [];
        // Field type
        $attributes[] = 'type="' . $field['type'] . '"';

        // Name and ID
        $attributes[] = 'name="' . $field['name'] . '"';
        $attributes[] = 'id="' . $field['name'] . '"';

        // Step for numeric fields
        if($field['name'] === 'payment_amount') {
            $attributes[] = 'step="1"';
        }

        // Required field
        if(isset($field['required']) && $field['required'] === true) {
            $attributes[] = 'required';
        }

        // Placeholder
        if(isset($field['placeholder']) && $field['placeholder'] !== '') {
            $attributes[] = 'placeholder="' . $field['placeholder'] . '"';
        }

        // Value - different sources based on field type
        if(in_array($field['name'], $supplierFields)) {
            $attributes[] = 'value="' . old($field['name'], $userInfo[$field['name'] ?? ''] ?? '') . '"';
        } elseif($field['name'] === 'issue_date') {
            $attributes[] = 'value="' . old($field['name'], now()->format('Y-m-d')) . '"';
        } elseif($field['name'] === 'tax_point_date') {
            $attributes[] = 'value="' . old($field['name'], now()->addDays(7)->format('Y-m-d')) . '"';
        } elseif($field['name'] === 'invoice_vs') {
            $attributes[] = 'value="' . old($field['name'], $suggestedNumber ?? '') . '"';
        } else {
            $attributes[] = 'value="' . old($field['name']) . '"';
        }

        return implode(' ', $attributes);
    }

    /**
    * Function for rendering asterisk for required fields
    */
    function renderRequiredMark($field) {
        if(isset($field['required']) && $field['required'] === true) {
            return '<span class="text-red-500">*</span>';
        }
        return '';
    }

    /**
    * Function to determine column span in grid layout
    */
    function getColumnSpan($fieldName) {
        if(in_array($fieldName, ['payment_amount', 'account_number'])) {
            return 'md:col-span-4';
        } elseif(in_array($fieldName, ['bank_code', 'bank_name'])) {
            return 'md:col-span-3';
        } elseif(in_array($fieldName, ['city', 'zip', 'client_city', 'client_zip'])) {
            return 'md:col-span-2';
        } else {
            return 'md:col-span-1';
        }
    }

    $invoiceFields = ['invoice_vs', 'invoice_ks', 'invoice_ss', 'issue_date', 'tax_point_date', 'payment_method_id',
        'due_in', 'payment_amount', 'payment_currency', 'payment_status'];
    $supplierFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country', 'ico', 'dic', 'account_number',
        'bank_code', 'bank_name', 'iban', 'swift'];
    $clientFields = ['client_name', 'client_email', 'client_phone', 'client_street', 'client_city', 'client_zip',
        'client_country', 'client_ico', 'client_dic'];
    $fieldDescription = getFieldByName($fields, 'invoice_text');

    // Fields to determine start and end of sections
    $sectionStartFields = ['invoice_ks', 'issue_date', 'payment_method_id', 'payment_amount', 'supplier_id', 'email',
        'city', 'ico', 'account_number', 'iban', 'client_id', 'client_email', 'client_city', 'client_ico'];
    $sectionEndFields = ['invoice_ss', 'due_in', 'payment_currency', 'supplier_id', 'phone', 'country', 'dic', 'bank_name',
        'swift', 'client_id', 'client_phone', 'client_country', 'client_dic'];

    // Fields for special layout division
    $specialLayoutFields = ['tax_point_date', 'payment_status_id', 'swift'];

    $formAction = $userLoggedIn ? route('frontend.invoice.store') : route('frontend.invoice.store.guest');
@endphp

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('invoices.titles.create') }}</h1>
    @if($userLoggedIn)

    <div class="space-x-2">
        <x-back-button />
            
        <a href="@localizedRoute('frontend.invoices')"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> {{ __('invoices.actions.back_to_list') }}
        </a>
    </div>
    @endif
</div>

<form id="invoice-form" method="POST"
    action="{{ $formAction }}"
    data-user-logged-in="{{ $userLoggedIn ? 'true' : 'false' }}"
    data-supplier-required="{{ __('invoices.validation.supplier_required') }}"
    data-client-required="{{ __('invoices.validation.client_required') }}"
    data-amount-required="{{ __('invoices.validation.amount_required') }}"
    data-amount-numeric="{{ __('invoices.validation.amount_numeric') }}"
    data-processing-text="{{ __('invoices.actions.processing') }}"
    data-error-text="{{ __('invoices.messages.create_error') }}"
>
    @csrf

    <!-- Section 1: Invoice Details -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.invoice_details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    @foreach($fields as $field)
                        @if($field['name'] !== 'invoice_text')
                            @php
                                $fieldClasses = getFieldClasses($field['name'], $supplierFields, $clientFields, $invoiceFields);
                                $columnSpan = getColumnSpan($field['name']);
                            @endphp

                            @if(in_array($field['name'], $sectionStartFields))
                                <div class="grid grid-cols-1 
                                    @if($field['name'] === 'payment_amount' || $field['name'] === 'supplier_id' || $field['name'] === 'client_id' || $field['name'] === 'city' || $field['name'] === 'client_city')md:grid-cols-5 
                                    @elseif($field['name'] === 'account_number')md:grid-cols-10 
                                    @else md:grid-cols-2 
                                    @endif gap-6 mb-5">
                            @endif

                            @if($field['name'] === 'invoice_vs')
                                <!-- Invoice number -->
                                <div class="mb-5">
                                    <label for="invoice_vs" class="block text-base font-medium text-gray-500 mb-2">
                                        {{ $field['label'] }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-10">
                                        <div class="col-span-9">
                                            <input {!! renderInputAttributes($field, $supplierFields, $userInfo ?? [],
                                                $suggestedNumber) !!}
                                                class="form-input block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                        </div>
                                        <div class="col-span-1">
                                            <button type="button" id="generate-invoice-number"
                                                class="ml-2 py-2 px-3 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $field['hint'] }}
                                        </p>
                                    @endif
                                    @error($field['name'])
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            @elseif($field['name'] === 'supplier_id')
                                @if($userLoggedIn)
                                    @php
                                        // clientInfo data if exists
                                        $value = old($field['name'], $clientInfo[$field['name']] ?? '');
                                    @endphp
                                    <!-- Supplier select -->
                                    <div class="md:col-span-4">
                                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                            id="{{ $field['name'] }}" valueField="id"
                                            :selected="old($field['name'], $clientInfo['supplier_id'] ?? '')" required="true"
                                            :options="$suppliers" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] supplier-select"
                                            labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                                    </div>

                                    <div class="md:col-span-1">
                                        @if(isset($field['label']) && $field['label'] !== '')
                                            <label class="invisible block text-base font-medium text-gray-500 mb-2">
                                                {{ __('suppliers.actions.edit_short') }}
                                            </label>
                                        @endif
                                        @php
                                            $supplierId = $userInfo['supplier_id'];
                                        @endphp
                                        <a href="@localizedRoute('frontend.supplier.edit', $supplierId)" id="edit-supplier-link"
                                            class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 hover:text-white bg-yellow-300 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ empty($invoice->supplier_id) ? 'opacity-50 pointer-events-none' : '' }} @localizedRoute('frontend.supplier.edit', $supplierId)">
                                            {{ __('suppliers.actions.edit_short') }}
                                        </a>
                                    </div>
                                @endif

                            @elseif($field['name'] === 'client_id')
                                @if($userLoggedIn)
                                    @php
                                        // clientInfo data if exists
                                        $value = old($field['name'], $clientInfo[$field['name']] ?? '');
                                        // editLink Route
                                        if(isset($userInfo['client_id']) && $userInfo['client_id'] !== '') {
                                            $editLinkRoute = route('frontend.client.edit', ['id' => $userInfo['client_id'], 'lang' =>
                                            app()->getLocale()]);
                                        } else {
                                            $editLinkRoute = '#';
                                        }
                                    @endphp
                                    <!-- Client select -->
                                    <div class="md:col-span-4">
                                        <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                            id="{{ $field['name'] }}" valueField="id"
                                            :selected="old($field['name'], $clientInfo['client_id'] ?? '')" required="true"
                                            :options="$clients" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] client-select"
                                            labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                                    </div>
                                    <div class="md:col-span-1">
                                        @if(isset($field['label']) && $field['label'] !== '')
                                            <label class="invisible block text-base font-medium text-gray-500 mb-2">
                                                {{ __('clients.actions.edit_short') }}
                                            </label>
                                        @endif
                                        <a href="{{ $editLinkRoute }}" id="edit-client-link"
                                            class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 hover:text-white bg-blue-300 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ empty($invoice->client_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                            {{ __('clients.actions.edit_short') }}
                                        </a>
                                    </div>
                                @endif

                            @elseif($field['name'] === 'payment_method_id')
                                <!-- Payment method -->
                                <div class="md:col-span-1">
                                    @php
                                    foreach($paymentMethods as $key => $method) {
                                        $paymentMethods[$key] = __('payment_methods.' . $method);
                                    }
                                    $marginBottomClass = !isset($field['hint']) || $field['hint'] === '' ? 'mb-6' : '';
                                    @endphp
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" :selected="old($field['name'], '7')"
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$paymentMethods" hint="{{ $field['hint'] }}"
                                        class="bg-blue-50 {{ $marginBottomClass }}" labelClass="" />
                                </div>

                            @elseif($field['name'] === 'due_in')
                                <!-- Invoice due date -->
                                @php
                                    $dueInOptions = [
                                        7 => '7 ' . __('invoices.units.days'),
                                        14 => '14 ' . __('invoices.units.days'),
                                        21 => '21 ' . __('invoices.units.days'),
                                        30 => '30 ' . __('invoices.units.days')
                                    ];
                                @endphp
                                <div class="md:col-span-1">
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" :selected="old($field['name'], '7')"
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$dueInOptions" hint="{{ $field['hint'] }}" class="bg-blue-50" labelClass="" />
                                </div>

                            @elseif($field['name'] === 'payment_currency')
                                <div class="md:col-span-1">
                                    <x-currency-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" :selected="old($field['name'], $invoice->currency ?? 'CZK')"
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        hint="{{ $field['hint'] }}" class="bg-red-50" />
                                </div>

                            @elseif($field['name'] === 'payment_status_id')
                                <div class="md:col-span-1 mb-5">
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" valueField="id" :selected="old($field['name'], '2')"
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$statuses"
                                        hint="{{ $field['hint'] }}" class="bg-blue-50" labelClass="" />
                                </div>

                            @elseif($field['name'] === 'country')
                                <div class="md:col-span-1 mb-5">
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" valueField="id"
                                        :selected="old($field['name'], $userInfo['country'] ?? 'CZ')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$field['options']" hint="{{ $field['hint'] }}"
                                        labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}"
                                        class="{{ in_array($field['name'], $supplierFields) ? 'bg-[#FDFDFC] supplier-field country-select' : '' }}" />
                                </div>

                            @elseif($field['name'] === 'client_country')
                                <div class="md:col-span-1 mb-5">
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}" valueField="id"
                                        :selected="old($field['name'], $userInfo['client_country'] ?? 'CZ')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$field['options']" hint="{{ $field['hint'] }}"
                                        labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}"
                                        class="{{ in_array($field['name'], $clientFields) ? 'bg-[#FDFDFC] client-field country-select' : '' }}" />
                                </div>

                            @elseif($field['name'] === 'bank_code')
                                <div class="md:col-span-3">
                                    <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        :selected="old($field['name'], $invoice->bank_code ?? $defaultSupplier['bank_code'] ?? '')"
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$banks"
                                        placeholder="{{ __('suppliers.placeholders.bank_code') }}"
                                        hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] supplier-field" labelClass="" />
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
                                @endphp
                                <div class="{{ $marginClass }} {{ $columnSpan }}" id="{{ $containerId }}">
                                    <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2">
                                        {{ $field['label'] }}
                                        @if(isset($field['required']) && $field['required'] === true && $field['name'] !==
                                        'name' && $field['name'] !== 'client_name')
                                            <span class="text-red-500">*</span>
                                        @elseif($field['name'] === 'name')
                                            <span id="supplier-name-required" class="text-red-500">*</span>
                                        @elseif($field['name'] === 'client_name')
                                            <span class="text-red-500" id="client-name-required">*</span>
                                        @endif
                                    </label>
                                    <input {!! renderInputAttributes($field, $supplierFields, $userInfo ?? []) !!}
                                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 {{ $fieldClasses }}">
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                        <p class="mt-1 text-sm text-gray-500">
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

                            @if($field['name'] === 'tax_point_date')
                                </div>
                            </div>
                            <div>

                            @elseif($field['name'] === 'payment_status_id')
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-yellow-100 overflow-hidden shadow-sm rounded-lg mb-8 border border-yellow-200 supplier-fields">
                                <div class="p-6">
                                    <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.seller_details') }}</h2>
                                    <div>

                            @elseif($field['name'] === 'swift')
                                </div>
                            </div>
                        </div>
                        <div class="bg-blue-50 overflow-hidden shadow-sm rounded-lg mb-8 border border-blue-200 seller-fields">
                            <div class="p-6">
                                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.client_details') }}</h2>
                                <div>
                            @endif

                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Invoice Description -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.other_info') }}</h2>

            <!-- Hidden filed for products data -->
            <input type="hidden" name="invoice-products" id="invoice-products" value="" />

            <div>
                <label for="invoice-products" class="block text-base font-medium text-gray-500 mb-2">
                    {{ __('invoices.fields.invoice_items') }}
                </label>
                <div id="invoice-items-container">
                    <!-- Invoice items header -->
                    <div class="grid grid-cols-12 gap-4 mb-2">
                        <div class="col-span-4 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_name') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_quantity') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_unit') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_price') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_currency') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_tax') }}</div>
                        <div class="col-span-2 text-sm font-medium text-gray-600">{{
                            __('invoices.placeholders.item_price_complete') }}</div>
                        <div class="col-span-1 text-sm font-medium text-gray-600">{{ __('invoices.placeholders.actions')
                            }}</div>
                    </div>
                    <!-- Template for invoice item -->
                    <div class="invoice-item-template hidden">
                        <div class="invoice-item grid grid-cols-12 gap-4 mb-3">
                            <div class="col-span-4 flex">
                                <input type="text"
                                    class="item-name form-input w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50"
                                    placeholder="{{ __('invoices.placeholders.item_name') }}">
                                <button type="button" title="{{ __('invoices.placeholders.select_product') }}" class="select-product ml-1 px-2 py-1 border border-blue-300 rounded-md cursor-pointer text-white hover:text-white bg-emerald-500 hover:bg-emerald-600">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="col-span-1">
                                <input type="number"
                                    class="item-quantity form-input w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50"
                                    placeholder="{{ __('invoices.placeholders.item_quantity') }}" step="0.5" min="0"
                                    value="1">
                            </div>
                            <div class="col-span-1">
                                <select selected="pieces"
                                    class="item-unit form-select w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                    @foreach($itemUnits as $key => $unit)
                                    @if ($key !== 'pieces')
                                        <option value="{{ $key }}" selected>{{ $unit }}</option>
                                    @else
                                        <option value="{{ $key }}">{{ $unit }}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1">
                                <input type="number"
                                    class="item-price form-input w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50"
                                    placeholder="{{ __('invoices.placeholders.item_price') }}" step="0.01" min="0"
                                    value="0">
                            </div>
                            <div class="col-span-1">
                                <select
                                    class="item-currency form-select w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                    <option value="CZK">CZK</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <select
                                    class="item-tax form-select w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                    <option value="0">0%</option>
                                    @foreach($taxRates as $key => $rate)
                                    <option value="{{ $rate }}">{{ $rate }}%</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="text"
                                    class="item-price-complete form-input w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-gray-100"
                                    placeholder="{{ __('invoices.placeholders.item_price_complete') }}" readonly>
                            </div>
                            <div class="col-span-1 flex items-center space-x-2">
                                <button type="button" class="duplicate-item text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button type="button" class="remove-item text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="invoice-items-list">
                        <!-- Items will be dynamically added here -->
                    </div>

                    <!-- Invoice items total sum -->
                    <div class="grid grid-cols-12 mt-5 mb-4 pt-3">
                        <div
                            class="col-span-9 text-lg font-medium text-gray-600 text-right bg-gray-50 p-2 border-t border-gray-200">
                            {{ __('invoices.fields.total') }}:</div>
                        <div class="col-span-2 font-bold text-lg bg-gray-50 p-2 border-t border-gray-200"
                            id="invoice-items-total">0.00</div>
                        <div class="col-span-1"></div>
                    </div>
                </div>

                <button type="button" id="add-invoice-item"
                    class="mt-2 inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-blue-300 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>{{ __('invoices.actions.add_item') }}
                </button>

                <!-- Description field -->
                <div class="mt-6">
                    <label for="invoice_text" class="block text-base font-medium text-gray-500 mb-2">
                        {{ __('invoices.fields.invoice_note') }}
                    </label>
                    <textarea name="invoice_text" id="invoice_text" rows="3"
                        class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">{{ old('invoice_text', $invoice->invoice_text ?? '') }}</textarea>

                    @if(isset($fieldDescription['hint']) && $fieldDescription['hint'] !== '')
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $fieldDescription['hint'] }}
                        </p>
                    @endif
                    @error('invoice_text')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @error('invoice_text')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="flex @if($userLoggedIn)justify-between @else justify-end @endif">
        @if($userLoggedIn)
            <a href="@localizedRoute('frontend.invoices')"
                class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('invoices.actions.cancel') }}
            </a>
        @endif
        <button type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>
            {{ __('invoices.actions.create') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

<!-- Modal for non-logged in users -->
<div id="guest-invoice-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Dialog -->
        <div
            class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
            <div class="flex justify-between items-start">
                <h3 class="text-xl font-medium leading-6 text-emerald-600">
                    {{ __('invoices.messages.thank_you') }}
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500 close-modal">
                    <span class="sr-only">{{ __('common.actions.close') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-3">
                <p class="text-sm text-gray-500" id="modal-message">
                    {{ __('invoices.messages.invoice_created_guest') }}
                </p>

                <p class="mt-2 text-sm font-bold" id="invoice-number-container">
                    {{ __('invoices.fields.invoice_vs') }}: <span id="invoice-number"></span>
                </p>

                <div class="mt-6">
                    <a href="#" id="download-invoice-btn"
                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('invoices.actions.download_pdf') }}
                    </a>
                </div>

                <div class="mt-4 text-sm text-gray-500">
                    {{ __('invoices.messages.download_reminder') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Selection Modal -->
<div id="product-selection-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Dialog -->
        <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-medium leading-6 text-gray-900">
                    {{ __('products.titles.select_product') }}
                </h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('common.actions.close') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="product-list-content">
                @livewire('product-list-select')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Debug script for modals
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, setting up Livewire event monitors');
    
    // Listen for all Livewire events
    document.addEventListener('livewire:initialized', function() {
        console.log('Livewire initialized successfully');
        
        document.addEventListener('product-selected', function(e) {
            console.log('Event handler: product-selected detected with data:', e.detail);
            
            // Manual test - try to update first row if event is detected
            const testUpdate = function(data) {
                const firstRow = document.querySelector('.invoice-item');
                if (!firstRow) {
                    console.error('No invoice item rows found for test update');
                    return;
                }
                
                const nameField = firstRow.querySelector('.item-name');
                if (nameField && data.productData) {
                    console.log('Test update: Setting product name to:', data.productData.name);
                    nameField.value = data.productData.name;
                }
            };
            
            // Try manual update as a test
            testUpdate(e.detail);
        });
    });
});
</script>
<script>
    // Make bank options available to the bank-fields.js script
    window.bankOptions = {{ Js::from($banksData) }};
</script>
@vite('resources/js/bank-fields.js')
@vite('resources/js/ares-lookup.js')
@vite('resources/js/invoice-form.js')
@endpush
