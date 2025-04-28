@extends('layouts.frontend')

@php
/**
 * Pomůcka pro získání pole podle názvu
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
 * Generování tříd pro pole
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
 * Generování společných atributů pro input pole
 */
function renderInputAttributes($field, $supplierFields, $userInfo, $suggestedNumber = false) {
    $attributes = [];
    
    // Typ pole
    $attributes[] = 'type="' . $field['type'] . '"';
    
    // Název a ID
    $attributes[] = 'name="' . $field['name'] . '"'; 
    $attributes[] = 'id="' . $field['name'] . '"';
    
    // Step pro číselná pole
    if($field['name'] === 'payment_amount') {
        $attributes[] = 'step="1"';
    }
    
    // Povinnost vyplnění
    if(isset($field['required']) && $field['required'] === true) {
        $attributes[] = 'required';
    }
    
    // Placeholder
    if(isset($field['placeholder']) && $field['placeholder'] !== '') {
        $attributes[] = 'placeholder="' . $field['placeholder'] . '"';
    }
    
    // Hodnota - různé zdroje podle typu pole
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
 * Funkce pro vykreslení hvězdičky u povinných polí
 */
function renderRequiredMark($field) {
    if(isset($field['required']) && $field['required'] === true) {
        return '<span class="text-red-500">*</span>';
    }
    return '';
}

/**
 * Funkce pro určení velikosti sloupců v grid layoutu
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

$invoiceFields = ['invoice_vs', 'invoice_ks', 'invoice_ss', 'issue_date', 'tax_point_date', 'payment_method_id', 'due_in', 'payment_amount', 'payment_currency', 'payment_status'];
$supplierFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country', 'ico', 'dic', 'account_number', 'bank_code', 'bank_name', 'iban', 'swift'];
$clientFields = ['client_name', 'client_email', 'client_phone', 'client_street', 'client_city', 'client_zip', 'client_country', 'client_ico', 'client_dic'];
$fieldDescription = getFieldByName($fields, 'invoice_text');

// Pole pro určení začátku a konce sekcí
$sectionStartFields = ['invoice_ks', 'issue_date', 'payment_method_id', 'payment_amount', 'supplier_id', 'email', 'city', 'ico', 'account_number', 'iban', 'client_id', 'client_email', 'client_city', 'client_ico'];
$sectionEndFields = ['invoice_ss', 'due_in', 'payment_currency', 'supplier_id', 'phone', 'country', 'dic', 'bank_name', 'swift', 'client_id', 'client_phone', 'client_country', 'client_dic'];

// Pole pro speciální rozdělení layoutu
$specialLayoutFields = ['tax_point_date', 'payment_status_id', 'swift'];

$formAction = $userLoggedIn ? route('frontend.invoice.store') : route('frontend.invoice.store.guest');
@endphp

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('invoices.titles.create') }}</h1>
    @if($userLoggedIn)
    <a href="@localizedRoute('frontend.invoices')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> {{ __('invoices.actions.back_to_list') }}
    </a>
    @endif
</div>

<form method="POST" action="{{ $formAction }}">
    @csrf
    
    <!-- Sekce 1: Údaje o faktuře -->
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
                                <!-- Číslo faktury -->
                                <div class="mb-5">
                                    <label for="invoice_vs" class="block text-base font-medium text-gray-500 mb-2">
                                        {{ $field['label'] }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-10">
                                        <div class="col-span-9">
                                            <input {!! renderInputAttributes($field, $supplierFields, $userInfo ?? [], $suggestedNumber) !!}
                                                class="form-input block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                        </div>
                                        <div class="col-span-1">
                                            <button type="button" 
                                                    id="generate-invoice-number" 
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
                                    <!-- Výběr dodavatele -->
                                    <div class="md:col-span-4">
                                        <x-select 
                                            name="{{ $field['name'] }}"
                                            label="{{ $field['label'] }}"
                                            id="{{ $field['name'] }}"
                                            valueField="id"
                                            :selected="old($field['name'], $clientInfo['supplier_id'] ?? '')" 
                                            required="true"
                                            :options="$suppliers" 
                                            hint="{{ $field['hint'] }}"
                                            class="bg-[#FDFDFC] supplier-select" 
                                            labelClass="" 
                                            allowsNull="true"
                                            placeholder="{{ $field['placeholder'] }}"
                                            />
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
                                            <a href="@localizedRoute('frontend.supplier.edit', $supplierId)"
                                                id="edit-supplier-link"
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
                                            $editLinkRoute = route('frontend.client.edit', ['id' => $userInfo['client_id'], 'lang' => app()->getLocale()]);
                                        } else {
                                            $editLinkRoute = '#';
                                        }
                                    @endphp
                                    <!-- Výběr klienta -->
                                    <div class="md:col-span-4">
                                        <x-select 
                                            name="{{ $field['name'] }}"
                                            label="{{ $field['label'] }}"
                                            id="{{ $field['name'] }}"
                                            valueField="id"
                                            :selected="old($field['name'], $clientInfo['client_id'] ?? '')" 
                                            required="true"
                                            :options="$clients" 
                                            hint="{{ $field['hint'] }}"
                                            class="bg-[#FDFDFC] client-select" 
                                            labelClass="" 
                                            allowsNull="true"
                                            placeholder="{{ $field['placeholder'] }}"
                                            />
                                    </div>
                                        <div class="md:col-span-1">
                                            @if(isset($field['label']) && $field['label'] !== '')
                                                <label class="invisible block text-base font-medium text-gray-500 mb-2">
                                                {{ __('clients.actions.edit_short') }}
                                                </label>
                                            @endif
                                            <a href="{{ $editLinkRoute }}"
                                                id="edit-client-link"
                                                class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 hover:text-white bg-blue-300 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ empty($invoice->client_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                                {{ __('clients.actions.edit_short') }}
                                            </a>
                                        </div>
                                @endif
                            
                            @elseif($field['name'] === 'payment_method_id')
                                <!-- Způsob platby -->
                                <div class="md:col-span-1">
                                @php
                                    $paymentMethods = $paymentMethods->toArray();
                                    foreach($paymentMethods as $key => $method) {
                                        $paymentMethods[$key] = __('payment_methods.' . $method);
                                    }
                                    $marginBottomClass = !isset($field['hint']) || $field['hint'] === '' ? 'mb-6' : '';
                                @endphp
                                <x-select 
                                    name="{{ $field['name'] }}"
                                    label="{{ $field['label'] }}"
                                    id="{{ $field['name'] }}"
                                    :selected="old($field['name'], '7')" 
                                    required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                    :options="$paymentMethods" 
                                    hint="{{ $field['hint'] }}"
                                    class="bg-blue-50 {{ $marginBottomClass }}" 
                                    labelClass="" 
                                    />
                                </div>

                            @elseif($field['name'] === 'due_in')
                                <!-- Splatnost faktury -->
                                @php
                                $dueInOptions = [
                                    7 => '7 ' . __('invoices.units.days'),
                                    14 => '14 ' . __('invoices.units.days'),
                                    21 => '21 ' . __('invoices.units.days'),
                                    30 => '30 ' . __('invoices.units.days')
                                ];
                                @endphp
                                <div class="md:col-span-1">
                                <x-select 
                                    name="{{ $field['name'] }}"
                                    label="{{ $field['label'] }}"
                                    id="{{ $field['name'] }}"
                                    :selected="old($field['name'], '7')" 
                                    required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                    :options="$dueInOptions" 
                                    hint="{{ $field['hint'] }}"
                                    class="bg-blue-50" 
                                    labelClass="" 
                                    />
                                </div>

                            @elseif($field['name'] === 'payment_currency')
                                <div class="md:col-span-1">
                                    <x-currency-select 
                                        name="{{ $field['name'] }}"
                                        label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        :selected="old($field['name'], $invoice->currency ?? 'CZK')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        hint="{{ $field['hint'] }}"
                                        class="bg-red-50"
                                        />
                                </div>

                            @elseif($field['name'] === 'payment_status_id')
                                <div class="md:col-span-1 mb-5">
                                    <x-select 
                                        name="{{ $field['name'] }}"
                                        label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        valueField="id"
                                        :selected="old($field['name'], '2')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$statuses" 
                                        hint="{{ $field['hint'] }}"
                                        class="bg-blue-50" 
                                        labelClass="" 
                                        />
                                </div>

                            @elseif($field['name'] === 'country' || $field['name'] === 'client_country')
                                <div class="md:col-span-1 mb-5">
                                    <x-country-select 
                                        name="{{ $field['name'] }}"
                                        :selected="old($field['name'], $userInfo['country'] ?? 'CZ')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        label="{{ $field['label'] }}"
                                        class="{{ in_array($field['name'], $supplierFields) ? 'bg-[#FDFDFC] supplier-field' : '' }} {{ in_array($field['name'], $clientFields) ? 'bg-[#FDFDFC] client-field' : '' }}"
                                    />
                                </div>

                            @elseif($field['name'] === 'bank_code')
                                <div class="md:col-span-3">
                                    <x-select 
                                        name="{{ $field['name'] }}"
                                        label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        :selected="old($field['name'], $invoice->bank_code ?? '')" 
                                        required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                        :options="$banks" 
                                        hint="{{ $field['hint'] }}"
                                        class="bg-[#FDFDFC] supplier-field" 
                                        labelClass="" 
                                        />
                                </div>

                            @else
                                <!-- Ostatní pole -->
                                @php
                                    $containerId = '';
                                    if ($field['name'] === 'name') {
                                        $containerId = 'supplier_name_container';
                                    } elseif ($field['name'] === 'client_name') {
                                        $containerId = 'client_name_container';
                                    }
                                    $marginClass = in_array($field['name'], ['name', 'street', 'client_name', 'client_street']) ? 'mb-5' : '';
                                @endphp
                                <div class="{{ $marginClass }} {{ $columnSpan }}" id="{{ $containerId }}">
                                    <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2">
                                        {{ $field['label'] }} 
                                        @if(isset($field['required']) && $field['required'] === true && $field['name'] !== 'name' && $field['name'] !== 'client_name')
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
                                <div class="bg-yellow-100 overflow-hidden shadow-sm rounded-lg mb-8 border border-yellow-200">
                                    <div class="p-6">
                                        <h2 class="text-2xl font-medium text-gray-900 mb-4">Údaje o vystaviteli</h2>
                                        <div>

                            @elseif($field['name'] === 'swift')
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 overflow-hidden shadow-sm rounded-lg mb-8 border border-blue-200">
                                <div class="p-6">
                                    <h2 class="text-2xl font-medium text-gray-900 mb-4">Údaje o zákazníkovi</h2>
                                    <div>
                            @endif
                        @endif            
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sekce 4: Popis faktury -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.other_info') }}</h2>
            
            <div>
                <label for="invoice_text" class="block text-base font-medium text-gray-500 mb-2">
                {{ __('invoices.fields.invoice_text') }}
                </label>
                <textarea 
                    name="invoice_text" 
                    id="invoice_text" 
                    rows="5" 
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
        </div>
    </div>
    
    <div class="flex @if($userLoggedIn)justify-between @else justify-end @endif">
        @if($userLoggedIn)
        <a href="@localizedRoute('frontend.invoices')" class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        {{ __('invoices.actions.cancel') }}
        </a>
        @endif
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>
            {{ __('invoices.actions.create') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

<!-- Modal pro nepřihlášeného uživatele -->
<div id="guest-invoice-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Dialog -->
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
            <div class="flex justify-between items-start">
                <h3 class="text-xl font-medium leading-6 text-emerald-600">
                    {{ __('invoices.messages.thank_you') }}
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500 close-modal">
                    <span class="sr-only">{{ __('common.actions.close') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                    <a href="#" id="download-invoice-btn" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Získání ID uživatele z Blade
    const userLoggedIn = JSON.parse("{{ json_encode($userLoggedIn) }}");
    console.log(JSON.stringify('userLoggedIn: ' + userLoggedIn));

    // Funkcionalita pro generování nového čísla faktury
    const generateInvoiceNumberBtn = document.getElementById('generate-invoice-number');
    if (generateInvoiceNumberBtn) {
        generateInvoiceNumberBtn.addEventListener('click', function() {
            // Získání aktuálního roku
            const currentYear = new Date().getFullYear();
            
            // Získání aktuálního času (použijeme pro zajištění unikátnosti)
            const timestamp = new Date().getTime().toString().slice(-5);
            
            // Vytvoření nového čísla faktury ve formátu YYYY + 4 číslice
            const newInvoiceNumber = currentYear.toString() + timestamp.padStart(4, '0').slice(-4);
            
            // Nastavení hodnoty do pole
            const invoiceVsInput = document.getElementById('invoice_vs');
            if (invoiceVsInput) {
                invoiceVsInput.value = newInvoiceNumber;
            }
        });
    }

    // Základní prvky pro validaci formuláře (pro všechny uživatele)
    const supplierNameField = document.getElementById('name');
    const supplierNameRequired = document.getElementById('supplier-name-required');
    const clientNameField = document.getElementById('client_name');
    const clientNameRequired = document.getElementById('client-name-required');
    
    // Deklarace proměnných s výchozími hodnotami (pro všechny uživatele)
    let supplierSelect = null;
    let clientSelect = null;
    let supplierFields = [];
    let clientFields = [];
    let editSupplierLink = null;
    let editClientLink = null;
    let selectedSupplierId = null;
    let selectedClientId = null;
    
    // Inicializace prvků pro přihlášeného uživatele
    if (userLoggedIn) {
        // Dodavatelé
        supplierSelect = document.getElementById('supplier_id');
        supplierFields = document.querySelectorAll('.supplier-field');
        editSupplierLink = document.getElementById('edit-supplier-link');

        // Klienti
        clientSelect = document.getElementById('client_id');
        clientFields = document.querySelectorAll('.client-field');
        editClientLink = document.getElementById('edit-client-link');
    }

    // Funkce pro přepínání stavu polí (společná implementace pro klienta i dodavatele)
    function toggleFields(select, fields, required, editLink, type) {
        // Kontrola, zda existují potřebné prvky
        if (!select || !fields.length || !required || !editLink) {
            console.warn(`Cannot toggle ${type} fields - some elements are missing`);
            return;
        }

        const selectedId = select.value;
        console.log(`selected${type}Id: ${selectedId}`);

        if (selectedId) {
            // Nastavení polí jako readonly
            fields.forEach(field => {
                field.readOnly = true;
                field.classList.add('bg-gray-200', 'text-gray-500');
            });

            // Aktivace odkazu na editaci
            editLink.classList.remove('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            // Zjištění aktuálního jazyka - JE POTŘEBA POUŽÍT ESCAPOVANÉ HODNOTY
            // Původní hodnota: const currentLang = "{{ app()->getLocale() }}";
            const currentLang = "{{ app()->getLocale() }}"; // Blade už tuto hodnotu vyhodnotí při renderování
            
            console.log('currentLang: ' + currentLang);
            
            // Vytvoření URL podle typu (klient nebo dodavatel) PŘÍMO S DOSAZENÝM JAZYKEM
            let baseUrl;
            if (type === 'Supplier') {
                baseUrl = "{{ route('frontend.supplier.edit', ['id' => ':id', 'lang' => app()->getLocale()]) }}";
            } else {
                baseUrl = "{{ route('frontend.client.edit', ['id' => ':id', 'lang' => app()->getLocale()]) }}";
            }
            
            // Nahrazení pouze ID placeholderu (jazyk už je správně dosazen)
            const newUrl = baseUrl.replace(':id', selectedId);
            console.log('newUrl: ' + newUrl);
                    
            // Nastavení nové URL
            editLink.href = newUrl;

            // Skrytí required hvězdičky
            required.classList.add('hidden');
        } else {
            // Vrácení polí do editovatelného stavu
            fields.forEach(field => {
                field.readOnly = false;
                field.classList.remove('bg-gray-200', 'text-gray-500');
            });
            
            // Deaktivace odkazu na editaci
            editLink.classList.add('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            editLink.href = '#';

            // Zobrazení required hvězdičky
            required.classList.remove('hidden');
        }
    }

    // Funkce pro přepínání polí klienta
    function toggleClientFields() {
        toggleFields(clientSelect, clientFields, clientNameRequired, editClientLink, 'Client');
    }

    // Funkce pro přepínání polí dodavatele
    function toggleSupplierFields() {
        toggleFields(supplierSelect, supplierFields, supplierNameRequired, editSupplierLink, 'Supplier');
    }
    
    // Nastavení výchozích hodnot a posluchačů událostí pouze pro přihlášeného uživatele
    if (userLoggedIn) {
        // Nastavení výchozího dodavatele pokud existuje
        const defaultSupplierId = "{{ $userInfo['supplier_id'] ?? '' }}";
        if (defaultSupplierId && supplierSelect) {
            supplierSelect.value = defaultSupplierId;
            // Vyvolat událost change pro aktualizaci rozhraní
            supplierSelect.dispatchEvent(new Event('change'));
        }

        // Nastavení výchozího klienta pokud existuje
        const defaultClientId = "{{ $userInfo['client_id'] ?? '' }}";
        if (defaultClientId && clientSelect) {
            clientSelect.value = defaultClientId;
            // Vyvolat událost change pro aktualizaci rozhraní
            clientSelect.dispatchEvent(new Event('change'));
        }
        
        // Inicializace formuláře pro přihlášeného uživatele
        if (clientSelect) {
            // Inicializace + přidání posluchače změny
            if (clientSelect.value) {
                toggleClientFields();
            }
            clientSelect.addEventListener('change', toggleClientFields);
        }
        
        if (supplierSelect) {
            // Inicializace + přidání posluchače změny
            if (supplierSelect.value) {
                toggleSupplierFields();
            }
            supplierSelect.addEventListener('change', toggleSupplierFields);
        }
    }

    // Validace formuláře
    document.querySelector('form').addEventListener('submit', function(e) {
        // Společná funkce pro validaci
        const validateRequired = (value, message) => {
            if (!value) {
                e.preventDefault();
                alert(message);
                return false;
            }
            return true;
        };
        
        // Přiřazení ID pouze v případě přihlášeného uživatele
        if (userLoggedIn) {
            selectedSupplierId = supplierSelect ? supplierSelect.value : null;
            selectedClientId = clientSelect ? clientSelect.value : null;
        }
        
        // Validace dodavatele
        const supplierNameValue = supplierNameField.value.trim();
        if (selectedSupplierId && supplierNameValue) {
            if (!validateRequired(selectedSupplierId || supplierNameValue, '{{ __("invoices.validation.supplier_required") }}')) {
                return;
            }
        } else {
            if (!validateRequired(supplierNameValue, '{{ __("invoices.validation.supplier_required") }}')) {
                return;
            }
        }
        
        // Validace klienta
        const clientNameValue = clientNameField.value.trim();
        if (selectedClientId && clientNameValue) {
            if (!validateRequired(selectedClientId || clientNameValue, '{{ __("invoices.validation.client_required") }}')) {
                return;
            }
        } else {
            if (!validateRequired(clientNameValue, '{{ __("invoices.validation.client_required") }}')) {
                return;
            }
        }
        
        // Validace částky
        const paymentAmount = document.getElementById('payment_amount').value.trim();
        if (!validateRequired(paymentAmount, '{{ __("invoices.validation.amount_required") }}')) {
            return;
        } else if (isNaN(paymentAmount)) {
            e.preventDefault();
            alert('{{ __("invoices.validation.amount_numeric") }}');
        }
    });

    // Automatické vyplnění názvu banky podle vybraného kódu
    const bankCodeSelect = document.getElementById('bank_code');
    const bankNameInput = document.getElementById('bank_name');
    const bankOptions = @json($banks);

    if (bankCodeSelect && bankNameInput) {
        const updateBankName = () => {
            const selectedCode = bankCodeSelect.value;
            if (selectedCode && bankOptions[selectedCode]) {
                // Extrahujeme název banky z textu (odstraníme kód v závorce)
                bankNameInput.value = bankOptions[selectedCode].replace(/\s+\(\d+\)$/, '');
            } else {
                bankNameInput.value = '';
            }
        };
        
        bankCodeSelect.addEventListener('change', updateBankName);
        
        // Pokud je již kód banky vybrán při načtení stránky, vyplníme název
        if (bankCodeSelect.value) {
            updateBankName();
        }
    }
    
    // Formátování IBAN a SWIFT
    const formatInputToUpperCase = (element) => {
        if (element) {
            element.addEventListener('input', function() {
                this.value = this.value.replace(/\s+/g, '').toUpperCase();
            });
        }
    };
    
    formatInputToUpperCase(document.getElementById('iban'));
    formatInputToUpperCase(document.getElementById('swift'));

    // Část pro nepřihlášené uživatele
    if (!userLoggedIn) {
        const form = document.querySelector('form');
        const modal = document.getElementById('guest-invoice-modal');
        const closeModalBtn = modal.querySelector('.close-modal');
        const downloadBtn = document.getElementById('download-invoice-btn');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Odeslat AJAX request
            const formData = new FormData(form);
            
            // Zobrazit indikátor načítání
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> {{ __("invoices.actions.processing") }}';
            
            fetch('{{ route("frontend.invoice.store.guest") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Nastavit hodnoty v modálu
                    document.getElementById('invoice-number').textContent = data.invoice_number;
                    document.getElementById('download-invoice-btn').href = data.download_url;
                    
                    // Uložit token do localStorage pro případné opětovné stažení
                    localStorage.setItem('lastInvoiceToken', data.token);
                    localStorage.setItem('lastInvoiceNumber', data.invoice_number);
                    localStorage.setItem('lastInvoiceDownloadUrl', data.download_url);
                    
                    // Zobrazit modál
                    modal.classList.remove('hidden');
                } else {
                    // Zobrazit chybovou hlášku
                    alert(data.message || '{{ __("invoices.messages.create_error") }}');
                    
                    // Povolit tlačítko pro odeslání
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> {{ __("invoices.actions.create") }}';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("invoices.messages.create_error") }}');
                
                // Povolit tlačítko pro odeslání
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> {{ __("invoices.actions.create") }}';
            });
        });
        
        // Po zavření modálu obnovit stránku a resetovat formulář
        closeModalBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
            // Obnovit stránku pro vytvoření nové faktury
            window.location.reload();
        });
    }
});
</script>
@endpush