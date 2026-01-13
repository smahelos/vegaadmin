@extends('layouts.frontend')

@section('content')
<!-- Top navigation bar with buttons -->
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('invoices.titles.invoice_number', ['number' => $invoice['invoice_vs']]) }}
    </h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-4 md:flex md:grid-cols-none md:space-x-0">
        <x-back-button />

        <a href="{{ route('frontend.invoices', ['locale' => app()->getLocale()]) }}"
            title="{{ __('invoices.actions.back_to_list') }}" aria-label="{{ __('invoices.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>

        <!-- Button for marking as paid - show only if not paid -->
        @if($invoice->payment_status_slug != 'paid')
        <form method="POST" class="col-span-1 md:col-span-none inline-block"
            action="{{ route('frontend.invoice.mark-as-paid', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}">
            @csrf
            @method('PUT')
            <button type="submit" title="{{ __('invoices.actions.mark_as_paid') }}"
                aria-label="{{ __('invoices.actions.mark_as_paid') }}"
                class="px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-emerald-200 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-check-circle"></i>
            </button>
        </form>
        @endif

        <!-- Template Settings Button -->
        <form id="invoice-form" method="POST" action=""
            data-action="{{ route('frontend.invoice.set-template', ['locale' => app()->getLocale(), 'id' => $invoice->id, 'template' => $invoice->template]) }}">
            <button type="button"
                title="{{ __('invoices.labels.template_settings') }}: {{ __('invoices.templates.' . ($invoice->template ?? 'default')) }}"
                aria-label="{{ __('invoices.labels.template_settings') }}: {{ __('invoices.templates.' . ($invoice->template ?? 'default')) }}"
                class="template-settings-btn px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-yellow-200 dark:bg-yellow-500 hover:bg-yellow-500 dark:hover:bg-yellow-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-cog"></i>
            </button>
            <input type="hidden" name="current_template" value="{{ $invoice->template ?? 'default' }}">
        </form>

        <a href="{{ route('frontend.invoice.edit', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
            title="{{ __('invoices.actions.edit') }}" aria-label="{{ __('invoices.actions.edit') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-pink-300 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-800 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white text-sm font-medium transition-colors">
            <i class="fas fa-pencil-alt"></i>
        </a>
        <button id="previewPdfBtn" type="button" title="{{ __('invoices.actions.preview_pdf') }}"
            aria-label="{{ __('invoices.actions.preview_pdf') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 bg-indigo-500 hover:bg-[#490BF4] rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
            <i class="fas fa-eye"></i>
        </button>
        <a href="{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
            title="{{ __('invoices.actions.download_pdf') }}" aria-label="{{ __('invoices.actions.download_pdf') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-red-200 dark:bg-red-500 hover:bg-red-400 dark:hover:bg-red-600 rounded-sm text-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 font-medium transition-colors">
            <i class="fas fa-download"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="invoice" limit="{{ $limitsData['limit'] }}"
        entity-name="{{ __('uels.entities.invoice') }}" :show-modal="true" />
</div>
@endauth

<!-- Main invoice information -->
<div class="mb-6">
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left column: Invoice information -->

            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex justify-between items-center">
                    <h2 class="text-xl text-gray-900 dark:text-white font-semibold">{{
                        __('invoices.sections.invoice_info') }}</h2>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{
                        $invoice->payment_status_name }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div class="w-1/3 text-sm font-medium text-gray-600 dark:text-gray-400">{{
                            __('invoices.fields.invoice_vs_long') }}
                        </div>
                        <div class="w-2/3 text-sm text-right font-bold dark:text-white">{{ $invoice['invoice_vs'] }}</div>
                    </div>

                    <div class="flex flex-wrap">
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-blue-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('invoices.fields.issue_date') }}</div>
                                <div class="font-medium dark:text-white">{{
                                    \Carbon\Carbon::parse($invoice->issue_date)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                                    }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-red-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('invoices.fields.due_date')
                                    }}</div>
                                <div class="font-medium dark:text-white">{{
                                    \Carbon\Carbon::parse($invoice->issue_date)->addDays((int)$invoice->due_in)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                                    }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-gray-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('invoices.fields.payment_method') }}</div>
                                <div class="font-medium dark:text-white">
                                    @if($invoice->paymentMethod)
                                    {{ $invoice->paymentMethod->translated_name }}
                                    @else
                                    <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('invoices.fields.payment_amount') }}</div>

                                <div class="font-medium dark:text-white">{{
                                    number_format((float)($paymentAmount->amount ?? ($invoice->payment_amount ?? 0)),
                                    2, ',', ' ') }} {{ $paymentAmount->currency ?? ($invoice->payment_currency ??
                                    'CZK') }}</div>
                            </div>
                        </div>
                        @if($invoice->supplier->account_number)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.account_number') }}</div>
                                <div class="font-medium dark:text-white">{{ $invoice->supplier->account_number }}</div>
                            </div>
                        </div>
                        @endif
                        @if($invoice->supplier->bank_name)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-purple-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('suppliers.fields.bank_name') }}</div>
                                <div class="font-medium dark:text-white">{{ $invoice->supplier->bank_name }}</div>
                            </div>
                        </div>
                        @endif
                        @if($invoice->supplier->iban)
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-blue-300 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.fields.iban') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $invoice->supplier->iban }}</div>
                            </div>
                        </div>
                        @endif
                        @if($invoice->supplier->swift)
                        <div class="w-full grid grid-cols-1 md:grid-cols-2">
                            <div class="w-full md:w-1/2 mb-4 col-span-1">
                                <div class="border-l-4 border-red-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.swift') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->swift }}</div>
                                </div>
                            </div>
                            @endif
                            @if($invoice->supplier->supplier_logo)
                            <div class="w-full md:w-3/4 mb-4 col-span-1 row-span-3">
                                <div class="border-l-4 border-red-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.supplier_logo') }}</div>
                                    <img src="{{ asset('storage/' . ltrim($invoice->supplier->supplier_logo, '/')) }}"
                                        alt="{{ __('suppliers.fields.supplier_logo') }}" class="mt-1">
                                </div>
                            </div>
                            @elseif($invoice->invoice_logo)
                            <div class="w-full md:w-3/4 mb-4 col-span-1 row-span-3">
                                <div class="border-l-4 border-red-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('suppliers.fields.supplier_logo') }}</div>
                                    <img src="{{ asset('storage/' . ltrim($invoice->invoice_logo, '/')) }}"
                                        alt="{{ __('suppliers.fields.supplier_logo') }}" class="mt-1">
                                </div>
                            </div>
                            @endif
                            <div class="w-full md:w-1/2 mb-4 col-span-1">
                                <div class="border-l-4 border-{{ $invoice->status_color_class }}-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                        __('invoices.status.invoice_status') }}</div>
                                    <div class="font-medium dark:text-white">
                                        @if($invoice->payment_status_name)
                                        {{ $invoice->payment_status_name }}
                                        @else
                                        <span class="text-gray-400">{{ __('invoices.placeholders.not_available')
                                            }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($invoice->invoice_ks || $invoice->invoice_ss)
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                        <div class="flex flex-wrap text-sm">
                            @if($invoice->invoice_ks)
                            <div class="w-1/2">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('invoices.fields.invoice_ks')
                                    }}:</span>
                                <span class="font-medium dark:text-white">{{ $invoice->invoice_ks }}</span>
                            </div>
                            @endif
                            @if($invoice->invoice_ss)
                            <div class="w-1/2">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('invoices.fields.invoice_ss')
                                    }}:</span>
                                <span class="font-medium dark:text-white">{{ $invoice->invoice_ss }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right column: Information about issuer and recipient -->
            <div>
                <!-- Supplier section -->
                <div
                    class=" bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                    <div
                        class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-900 dark:text-white font-semibold">{{
                            __('invoices.sections.supplier') }}</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{
                            __('invoices.tags.issuer') }}</span>
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.name') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->name }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.street') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->street }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.city') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->city }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.zip') }} {{
                                        __('general.joins.and') }} {{ __('invoices.fields.country') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->zip }}, {{ $invoice->supplier->country
                                        }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.ico') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->ico }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.dic') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->supplier->dic }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client section -->
                <div
                    class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                    <div
                        class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-900 dark:text-white font-semibold">{{
                            __('invoices.sections.client') }}</h2>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{
                            __('invoices.tags.recipient') }}</span>
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.name') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->name }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.street') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->street }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.city') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->city }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.zip') }} {{
                                        __('general.joins.and') }} {{ __('clients.fields.country') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->zip }}, {{
                                        $invoice->client->country
                                        }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-purple-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.ico') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->ico }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-purple-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.dic') }}</div>
                                    <div class="font-medium dark:text-white">{{ $invoice->client->dic }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section for invoice items and note (only if exists) -->
        @if(!empty($invoice->invoice_text) || ($invoice->invoiceProducts && count($invoice->invoiceProducts) > 0))
        <div>
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/3 rounded-md dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="mb-4 border-b border-white dark:border-gray-700 pb-2 flex justify-between items-center">
                    <h2 class="text-xl text-gray-900 dark:text-white font-semibold">{{
                        __('invoices.sections.invoice_text') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="mt-4 space-y-4">
                        <div class="">

                            @if($invoice->invoiceProducts && count($invoice->invoiceProducts) > 0)
                            <!-- Invoice items -->
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4 ml-4">{{
                                __('invoices.fields.invoice_items') }}</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-4">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_name') }}
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_quantity') }}
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_unit') }}
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_price') }}
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_tax') }}
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_price_complete') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-600">
                                        @foreach($invoice->invoiceProductsData as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                {{ $item['name'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ $item['quantity'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ __('invoices.units.' . ($item['unit'] ?? '')) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                @if(isset($item['price']) && $item['price'] > 0)
                                                {{ number_format($item['price'], 2, ',', ' ') }}
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                @if(isset($item['tax_rate']) && $item['tax_rate'] > 0)
                                                {{ $item['tax_rate'] }}%
                                                @else
                                                0%
                                                @endif
                                            </td>
                                            <td
                                                class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white font-medium">
                                                @if(isset($item['total_price']) && $item['total_price'])
                                                {{ $item['total_price'] }}
                                                @elseif(isset($item['price']) && isset($item['quantity']))
                                                @php
                                                $tax = isset($item['tax_rate']) ? floatval($item['tax_rate']) : 0;
                                                $totalWithTax = floatval($item['price']) * floatval($item['quantity']) *
                                                (1 + ($tax / 100));
                                                echo number_format($totalWithTax, 2, ',', ' ');
                                                @endphp
                                                @else
                                                -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-100 dark:bg-gray-700 font-medium">
                                            <td class="px-4 py-3 text-right dark:text-white" colspan="5">
                                                {{ __('invoices.fields.total') }}:
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-bold">
                                                {{ number_format((float)($paymentAmount->amount ??
                                                ($invoice->payment_amount ?? 0)), 2, ',', ' ') }} {{
                                                $paymentAmount->currency ?? ($invoice->payment_currency ?? 'CZK') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @endif

                            @if($invoice->invoice_text)
                            <!-- Invoice note -->
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 mt-6 ml-4">{{
                                __('invoices.fields.invoice_note') }}</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-sm p-4">
                                <p class="text-sm text-gray-700 dark:text-gray-400 whitespace-pre-line">{{
                                    $invoice->invoice_text }}</p>
                            </div>
                            {{-- @elseif(!$invoice->invoice_text)
                            <!-- Display original content if JSON parsing fails -->
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2">{{
                                __('invoices.sections.internal_note') }}</h3>
                            <p class="text-sm text-gray-700 dark:text-gray-400 whitespace-pre-line">{{
                                $invoice->invoice_text }}</p> --}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>
</div>

<!-- Modal window for PDF preview -->
<div id="pdfPreviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">{{ __('invoices.modal.preview_title', ['number' =>
                    $invoice['invoice_vs']]) }}</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('invoices.actions.close') }}</span>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-2 h-[calc(100vh-200px)] relative">
                <div id="loading-indicator"
                    class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10">
                    <svg class="animate-spin h-10 w-10 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="ml-3 text-indigo-600 font-medium">{{ __('invoices.modal.loading_pdf') }}</span>
                </div>
                <iframe id="pdfIframe" class="w-full h-full border-0" src="about:blank"></iframe>
            </div>
            <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" id="closeModalBtn"
                    class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('invoices.actions.close') }}
                </button>
                <a href="{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
                    class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    {{ __('invoices.actions.download_pdf') }}
                </a>
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
                <form method="POST"
                    action="{{ route('frontend.invoice.set-template', ['locale' => app()->getLocale(), 'id' => $invoice->id, 'template' => $invoice->template]) }}"
                    id="template-selection-form" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="template" value="default">
                    <button type="button" id="confirm-template-selection"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        disabled>
                        {{ __('general.actions.confirm') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth
@endsection

@push('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('pdfPreviewModal');
        const previewBtn = document.getElementById('previewPdfBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const closeModal = document.getElementById('closeModal');
        const pdfIframe = document.getElementById('pdfIframe');
        const loadingIndicator = document.getElementById('loading-indicator');

        // Opening modal window and loading PDF
        previewBtn.addEventListener('click', function() {
            // Get current language
            const currentLang = '{{ app()->getLocale() }}';
            // Set iframe source with preview=true parameter
            pdfIframe.src = "{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}?preview=true";

            // Show loading indicator
            loadingIndicator.classList.remove('hidden');
            pdfIframe.onload = function() {
                console.log('PDF loaded');
                loadingIndicator.classList.add('hidden');
            };

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            // Debug information
            console.log('Modal window opened, loading PDF');
            console.log('URL:', "{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}?preview=true");
        });

        // Closing modal window
        const closeModalFunction = function() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            // Clearing iframe source to reduce memory usage
            setTimeout(() => {
                pdfIframe.src = 'about:blank';
            }, 300);
        };

        // Ensure elements exist before adding event listeners
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModalFunction);
        }
        if (closeModal) {
            closeModal.addEventListener('click', closeModalFunction);
        }

        // Close modal window when clicking outside content
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModalFunction();
            }
        });

        // Close modal window when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModalFunction();
            }
        });
    });
</script>

<script>
    // Provide existing invoice data to the invoice-form.js script
    window.templateAction = "{{ route('frontend.invoice.set-template', ['locale' => app()->getLocale(), 'id' => $invoice->id, 'template' => $invoice->template]) }}";
    window.csfrToken = "{{ csrf_token() }}";
</script>
@vite('resources/js/template-selector-updating.js')

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth

@endpush
