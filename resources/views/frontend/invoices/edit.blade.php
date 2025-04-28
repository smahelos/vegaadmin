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
function renderInputAttributes($field, $invoice, $supplierFields, $clientFields) {
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
    
    // Hodnota - pro edit formulář používáme data z invoice objektu
    if($invoice) {
        $attributes[] = 'value="' . old($field['name'], $invoice->{$field['name']} ?? '') . '"';
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

$invoiceFields = ['invoice_vs', 'invoice_ks', 'invoice_ss', 'issue_date', 'tax_point_date', 'payment_method_id', 'due_in', 'payment_amount', 'payment_currency', 'payment_status_id'];
$supplierFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country', 'ico', 'dic', 'account_number', 'bank_code', 'bank_name', 'iban', 'swift'];
$clientFields = ['client_name', 'client_email', 'client_phone', 'client_street', 'client_city', 'client_zip', 'client_country', 'client_ico', 'client_dic'];
$fieldDescription = getFieldByName($fields, 'invoice_text');

// Pole pro určení začátku a konce sekcí
$sectionStartFields = ['invoice_ks', 'issue_date', 'payment_method_id', 'payment_amount', 'supplier_id', 'email', 'city', 'ico', 'account_number', 'iban', 'client_id', 'client_email', 'client_city', 'client_ico'];
$sectionEndFields = ['invoice_ss', 'due_in', 'payment_currency', 'supplier_id', 'phone', 'country', 'dic', 'bank_name', 'swift', 'client_id', 'client_phone', 'client_country', 'client_dic'];

// Pole pro speciální rozdělení layoutu
$specialLayoutFields = ['tax_point_date', 'payment_status_id', 'swift'];
@endphp

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('invoices.titles.edit') }}</h1>
    <a href="@localizedRoute('frontend.invoices')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> {{ __('invoices.actions.back_to_list') }}
    </a>
</div>

<form method="POST" action="{{ route('frontend.invoice.update', $invoice->id) }}">
    @csrf
    @method('PUT')
    
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
                                    <input type="text" 
                                        name="invoice_vs" 
                                        id="invoice_vs" 
                                        required 
                                        value="{{ old('invoice_vs', $invoice->invoice_vs) }}" 
                                        class="form-input block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $field['hint'] }}
                                        </p>
                                    @endif
                                    @error('invoice_vs')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            @elseif($field['name'] === 'supplier_id')
                                <!-- Výběr dodavatele -->
                                <div class="md:col-span-4">
                                    <x-select 
                                        name="{{ $field['name'] }}"
                                        label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        valueField="id"
                                        :selected="old($field['name'], $invoice->supplier_id)" 
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
                                    <a href="@localizedRoute('frontend.supplier.edit', ['id' => $invoice->supplier_id])"
                                        id="edit-supplier-link"
                                        class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 hover:text-white bg-yellow-300 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ empty($invoice->supplier_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                        {{ __('suppliers.actions.edit_short') }}
                                    </a>
                                </div>

                            @elseif($field['name'] === 'client_id')
                                <!-- Výběr klienta -->
                                <div class="md:col-span-4">
                                    <x-select 
                                        name="{{ $field['name'] }}"
                                        label="{{ $field['label'] }}"
                                        id="{{ $field['name'] }}"
                                        valueField="id"
                                        :selected="old($field['name'], $invoice->client_id)" 
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
                                    <a href="@localizedRoute('frontend.client.edit', ['id' => $invoice->client_id ?? 1])"
                                        id="edit-client-link"
                                        class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 hover:text-white bg-blue-300 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ empty($invoice->client_id) ? 'opacity-50 pointer-events-none' : '' }}">
                                        {{ __('clients.actions.edit_short') }}
                                    </a>
                                </div>
                            
                            @elseif($field['name'] === 'payment_method_id')
                                <!-- Způsob platby -->
                                <div class="md:col-span-1">
                                @php
                                    if(isset($paymentMethods)) {
                                        foreach($paymentMethods as $key => $method) {
                                            $paymentMethods[$key] = __('payment_methods.' . $method);
                                        }
                                    }
                                    $marginBottomClass = !isset($field['hint']) || $field['hint'] === '' ? 'mb-6' : '';
                                @endphp
                                <x-select 
                                    name="{{ $field['name'] }}"
                                    label="{{ $field['label'] }}"
                                    id="{{ $field['name'] }}"
                                    :selected="old($field['name'], $invoice->payment_method_id)" 
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
                                    :selected="old($field['name'], $invoice->due_in)" 
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
                                        :selected="old($field['name'], $invoice->payment_currency)" 
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
                                        :selected="old($field['name'], $invoice->payment_status_id)" 
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
                                        :selected="old($field['name'], $invoice->{$field['name']})" 
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
                                        :selected="old($field['name'], $invoice->bank_code)" 
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
                                            <span id="supplier-name-required" class="text-red-500 {{ $invoice->supplier_id ? 'hidden' : '' }}">*</span>
                                        @elseif($field['name'] === 'client_name')
                                            <span class="text-red-500" id="client-name-required" class="{{ $invoice->client_id ? 'hidden' : '' }}">*</span>
                                        @endif
                                    </label>
                                    <input type="{{ $field['type'] }}" 
                                        name="{{ $field['name'] }}" 
                                        id="{{ $field['name'] }}" 
                                        @if($field['name'] === 'issue_date' || $field['name'] === 'tax_point_date')
                                            value="{{ old($field['name'], $invoice->{$field['name']}->format('Y-m-d')) }}" 
                                        @else
                                        value="{{ old($field['name'], $invoice->{$field['name']}) }}"
                                        @endif
                                        @if(isset($field['required']) && $field['required'] === true)
                                            required
                                        @endif
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
                                        <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('invoices.sections.seller_details') }}</h2>
                                        <div>

                            @elseif($field['name'] === 'swift')
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 overflow-hidden shadow-sm rounded-lg mb-8 border border-blue-200">
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
                    class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">{{ old('invoice_text', $invoice->invoice_text) }}</textarea>
                
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
    
    <div class="flex justify-between">
        <a href="@localizedRoute('frontend.invoices')" class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('invoices.actions.cancel') }}
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>
            {{ __('invoices.actions.update') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Klienti
    const clientSelect = document.getElementById('client_id');
    const clientNameField = document.getElementById('client_name');
    const clientFields = document.querySelectorAll('.client-field');
    const clientNameRequired = document.getElementById('client-name-required');
    const editClientLink = document.getElementById('edit-client-link');
    
    // Dodavatelé
    const supplierSelect = document.getElementById('supplier_id');
    const supplierNameField = document.getElementById('name');
    const supplierFields = document.querySelectorAll('.supplier-field');
    const supplierNameRequired = document.getElementById('supplier-name-required');
    const editSupplierLink = document.getElementById('edit-supplier-link');

    // Funkce pro přepínání stavu polí (společná implementace pro klienta i dodavatele)
    function toggleFields(select, fields, required, editLink, type) {
        const selectedId = select.value;
        console.log(`selected${type}Id: ${selectedId}`);
        
        if (selectedId) {
            // Nastavení polí jako readonly
            fields.forEach(field => {
                field.readOnly = true;
                field.classList.add('bg-gray-200', 'text-gray-500');
            });

            // Aktivace odkazu na editaci
            editLink.readOnly = false;
            editLink.classList.remove('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            // Sestavení URL pro editaci
            const baseUrl = editLink.getAttribute('href').split('/').slice(0, -2).join('/') + '/';
            editLink.href = baseUrl + selectedId + '/edit';

            // Skrytí required hvězdičky
            required.classList.add('hidden');
        } else {
            // Vrácení polí do editovatelného stavu
            fields.forEach(field => {
                field.readOnly = false;
                field.classList.remove('bg-gray-200', 'text-gray-500');
            });
            
            // Deaktivace odkazu na editaci
            editLink.readOnly = true;
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
    
    // Inicializace formuláře
    toggleClientFields();
    toggleSupplierFields();
    
    // Sledování změn výběru
    clientSelect.addEventListener('change', toggleClientFields);
    supplierSelect.addEventListener('change', toggleSupplierFields);

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
        
        // Validace klienta
        const selectedClientId = clientSelect.value;
        const clientNameValue = clientNameField.value.trim();
        if (!validateRequired(selectedClientId || clientNameValue, '{{ __("invoices.validation.client_required") }}')) {
            return;
        }
        
        // Validace dodavatele
        const selectedSupplierId = supplierSelect.value;
        const supplierNameValue = supplierNameField.value.trim();
        if (!validateRequired(selectedSupplierId || supplierNameValue, '{{ __("invoices.validation.supplier_required") }}')) {
            return;
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
});
</script>
@endpush