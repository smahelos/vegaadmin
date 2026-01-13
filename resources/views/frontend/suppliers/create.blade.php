@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('suppliers.titles.create') }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-2 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.suppliers', ['locale' => app()->getLocale()]) }}"
            title="{{ __('suppliers.actions.back_to_list') }}" aria-label="{{ __('suppliers.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="supplier" limit="{{ $limitsData['limit'] }}"
        entity-name="{{ __('uels.entities.supplier') }}" :show-modal="true" />
</div>
@endauth

<form action="{{ route('frontend.supplier.store', ['locale' => app()->getLocale()]) }}" method="POST"
    enctype="multipart/form-data">
    @csrf

    <!-- Section 1: Supplier basic information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('suppliers.sections.basic_info') }}</h2>

                <!-- Left column -->
                @php
                $leftColumnFields = ['name', 'shortcut', 'email', 'phone', 'note'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'name' || $field['name'] === 'email')
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    @endif
                    @if (in_array($field['name'], $leftColumnFields))
                    <div
                        class="mb-5 @if($field['name'] === 'name')md:col-span-4 @elseif($field['name'] === 'shortcut')md:col-span-2 @else md:col-span-3 @endif ">
                        <label for="{{ $field['name'] }}"
                            class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                            {{ $field['label'] }}
                            @if(isset($field['required']) && $field['required'])
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            @if(isset($field['required']) && $field['required']) required @endif>
                        @if(isset($field['hint']) && $field['hint'] !== '')
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                        @endif
                        @error($field['name'])
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                    @if ($field['name'] === 'shortcut' || $field['name'] === 'phone')
                </div>
                @endif

                <!-- Default description -->
                @if ($field['name'] === 'description')
                <div class="mb-5">
                    <label for="{{ $field['name'] }}"
                        class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                        {{ $field['label'] }}
                        @if(isset($field['required']) && $field['required'])
                        <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <textarea name="{{ $field['name'] }}" id="{{ $field['name'] }}" rows="4"
                        class="form-textarea mt-1 block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                        @if(isset($field['required']) && $field['required']) required @endif></textarea>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                    @error($field['name'])
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Supplier logo -->
                @if ($field['name'] === 'supplier_logo')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-1 @if(!$errors->has('supplier_logo'))mb-5 @endif">
                        <label for="supplier_logo"
                            class="flex flex-col items-center justify-center w-full h-37 border-2 border-gray-300 border-dashed rounded-sm cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition-colors duration-200">
                            <div class="flex flex-col items-center justify-center p-3">
                                <p class="mb-2 text-lg font-semibold text-[#490BF4] dark:text-gray-200">{{
                                    __('invoices.labels.upload_logo') }}</p>
                                <p class="mt-1 text-sm text-gray-500 wrap-break-word">
                                    {{ __('suppliers.hints.supplier_logo') }}
                                </p>
                            </div>
                            <input id="supplier_logo" name="supplier_logo" type="file" class="hidden image-input" />
                        </label>
                    </div>
                    <div class="md:col-span-1 @if(!$errors->has('supplier_logo'))mb-5 @endif">
                        <div id="image-preview-container" class="flex justify-between items-center">
                            <img id="current-image-preview"
                                src="{{ Storage::disk('public')->url('suppliers/logos/no_logo.png') }}"
                                alt="{{ __('suppliers.messages.no_image') }}"
                                class="max-h-37 object-cover m-auto rounded-sm">
                        </div>
                    </div>
                    @error('supplier_logo')
                    <p class="text-sm text-red-600 mb-7">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Default supplier -->
                @if ($field['name'] === 'is_default')
                <div class="mb-5">
                    <label for="is_default" class="flex items-center">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" id="is_default" value="1"
                            class="border-2 border-blue-100 w-4 h-4 text-blue-600 bg-gray-100 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-base font-medium text-gray-900 dark:text-white">{{
                            __('suppliers.fields.is_default') }}</span>
                    </label>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Section 2: Supplier billing information -->
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('suppliers.sections.billing_info') }}</h2>

                <!-- Right column -->
                @php
                $leftColumnFields = ['name', 'shortcut', 'email', 'phone', 'supplier_logo', 'description',
                'is_default'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'city' || $field['name'] === 'ico' || $field['name'] === 'account_number' ||
                $field['name'] === 'iban')
                <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
                    @endif
                    <div
                        class="@if($field['name'] === 'city')md:col-span-4 @elseif($field['name'] === 'account_number')md:col-span-4 @elseif($field['name'] === 'zip' || $field['name'] === 'bank_code' || $field['name'] === 'bank_name')md:col-span-3 @elseif($field['name'] === 'country')md:col-span-3 @else md:col-span-5 @endif ">
                        @if ($field['name'] === 'country')
                        <!-- Country -->
                        <x-country-select name="country" :selected="old('country', $supplierInfo['country'] ?? 'CZ')"
                            required="true" label="{{ __('suppliers.fields.country') }}" />

                        @elseif ($field['name'] === 'bank_code')
                        <div class="md:col-span-3">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" :selected="old($field['name'], $supplier->bank_code ?? '')"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$banks"
                                hint="{{ $field['hint'] }}" class="bg-[#FDFDFC] supplier-field" labelClass="" />
                        </div>

                        @elseif (!in_array($field['name'], $leftColumnFields))
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}"
                                class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                @if(isset($field['placeholder']) && $field['placeholder'] !=='' )
                                placeholder="{{ $field['placeholder'] }}" @endif
                                class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                    </div>
                    @if ($field['name'] === 'country' || $field['name'] === 'dic' || $field['name'] === 'bank_name' ||
                    $field['name'] === 'swift')
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Form submission button -->
    <div class="flex justify-between mb-20 md:mb-10">
        <a href="{{ route('frontend.suppliers', ['locale' => app()->getLocale()]) }}"
            class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            {{ __('suppliers.actions.cancel') }}
        </a>
        <button type="submit"
            class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
            <i class="fas fa-save mr-2"></i>{{ __('suppliers.actions.create') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth
@endsection

@push('scripts')
<script>
    // Make bank options available to the bank-fields.js script
    window.bankOptions = {{ Js::from($banksData) }};
</script>
@vite('resources/js/bank-fields.js')
@vite('resources/js/ares-lookup.js')
@vite('resources/js/image-preview.js')
@endpush

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
