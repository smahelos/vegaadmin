@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3"><span
            class="text-base text-gray-900 dark:text-white font-medium">{{ __('suppliers.titles.supplier') }}</span> {{
        $supplier->name }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-3 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.suppliers', ['locale' => app()->getLocale()]) }}"
            title="{{ __('suppliers.actions.back_to_list') }}" aria-label="{{ __('suppliers.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>
        <a href="{{ route('frontend.supplier.edit', ['locale' => app()->getLocale(), 'id' => $supplier->id]) }}"
            title="{{ __('suppliers.actions.edit') }}" aria-label="{{ __('suppliers.actions.edit') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-pink-300 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-800 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white text-sm font-medium transition-colors">
            <i class="fas fa-pencil-alt"></i>
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

<!-- Main supplier information -->
<div class="">
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left column: Basic information -->
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4 pb-2 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{
                        __('suppliers.sections.basic_info') }}</h2>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{
                        __('suppliers.tags.supplier') }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap">
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.name') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->name }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.email') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->email }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.phone') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->phone ??
                                    __('general.empty.not_specified') }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.shortcut')
                                    }}</div>
                                <div class="font-medium dark:text-white">{{ $supplier->shortcut ??
                                    __('general.empty.not_specified') }}</div>
                            </div>
                        </div>
                        <div class="w-full grid grid-cols-1 md:grid-cols-2">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.created_at') }}</div>
                                    <div class="font-medium dark:text-white">{{
                                        \Carbon\Carbon::parse($supplier->created_at)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                                        }}</div>
                                </div>
                            </div>
                            @if($supplier->supplier_logo)
                            <div class="w-full md:w-3/4 mb-4 col-span-1 row-span-3">
                                <div class="border-l-4 border-red-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.supplier_logo') }}</div>
                                    <img src="{{ Storage::disk('public')->url($supplier->supplier_logo) }}"
                                        alt="{{ __('suppliers.fields.supplier_logo') }}" class="mt-1">
                                </div>
                            </div>
                            @endif
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.is_default') }}</div>
                                    <div class="font-medium">
                                        <span
                                            class="font-semibold @if($supplier->is_default > 0)text-green-600 @else text-red-600 @endif">
                                            @if($supplier->is_default > 0)
                                            {{ __('general.placeholders.yes') }}
                                            @else
                                            {{ __('general.placeholders.no') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($supplier->description))
                    <div class="bg-white dark:bg-gray-700 p-3 rounded-md shadow-sm">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('suppliers.fields.description')
                            }}</div>
                        <div class="text-sm whitespace-pre-line dark:text-white">{{ $supplier->description }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right column: Billing information -->
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4 pb-2 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{
                        __('suppliers.sections.billing_info') }}</h2>
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{
                        __('suppliers.tags.billing') }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap">
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.street')
                                    }}</div>
                                <div class="font-medium dark:text-white">{{ $supplier->street }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.city') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->city }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-red-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.zip') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->zip }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-red-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.country')
                                    }}</div>
                                <div class="font-medium dark:text-white">{{ $supplier->country }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-purple-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.ico') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->ico ??
                                    __('general.empty.not_specified') }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-purple-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.dic') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->dic ??
                                    __('general.empty.not_specified') }}</div>
                            </div>
                        </div>
                        @if($supplier->account_number)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.account_number') }}</div>
                                <div class="font-medium dark:text-white">{{ $supplier->account_number }}</div>
                            </div>
                        </div>
                        @endif
                        @if($supplier->bank_name)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-purple-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.bank_name') }}</div>
                                <div class="font-medium dark:text-white">{{ $supplier->bank_name }}</div>
                            </div>
                        </div>
                        @endif
                        @if($supplier->iban)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-blue-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.iban') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->iban }}</div>
                            </div>
                        </div>
                        @endif
                        @if($supplier->swift)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-red-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.swift') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $supplier->swift }}</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Display delivery address if different from billing -->
                    {{-- @if($supplier->has_delivery_address)
                    <div class="bg-white p-3 rounded-md shadow-sm">
                        <div class="text-sm font-medium mb-2">{{ __('suppliers.sections.delivery_address') }}</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.delivery_street') }}</div>
                                <div class="text-sm dark:text-white">{{ $supplier->delivery_street }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.delivery_city') }}</div>
                                <div class="text-sm dark:text-white">{{ $supplier->delivery_city }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.delivery_zip') }}</div>
                                <div class="text-sm dark:text-white">{{ $supplier->delivery_zip }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.delivery_country') }}</div>
                                <div class="text-sm dark:text-white">{{ $supplier->delivery_country }}</div>
                            </div>
                        </div>
                    </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
</div>

<div
    class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
    <div class="">
        <div class="border-b border-gray-200 dark:border-gray-700 flex justify-between items-center mb-4 pb-2">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{
                __('suppliers.sections.supplier_invoices') }}</h2>
            <a href="{{ route('frontend.invoice.create', ['supplier_id' => $supplier->id, 'locale' => app()->getLocale()]) }}"
                class="px-4 py-2 bg-indigo-500 hover:bg-[#490BF4] rounded-sm text-white hover:text-white text-sm font-semibold transition-colors">
                <i class="fas fa-plus mr-2"></i> {{ __('invoices.actions.create') }}
            </a>
        </div>

        @livewire('invoice.invoice-list-by-supplier', ['supplierId' => $supplier->id])
    </div>
</div>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth
@endsection

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
