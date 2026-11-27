@use('App\Application\Invoice\Commands\CopyInvoice')
@use('App\Application\Invoice\Commands\DeleteInvoice')
@use('App\Application\Invoice\Commands\IssueInvoice')
@use('App\Application\Invoice\Commands\LinkInvoiceContact')
@use('App\Application\Invoice\Commands\SaveInvoiceCustomerToContacts')
@use('App\Application\Invoice\Commands\SetInvoiceStatus')
@use('App\Application\Invoice\Commands\SetInvoiceTemplate')
@use('App\Application\Party\Commands\CreateContact')
@use('App\Domain\Invoice\Enums\InvoiceStatus')
@extends('layouts.frontend')

@section('content')
<!-- Top navigation bar with buttons -->
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">
        @if($invoice->issue)
        {{ __('invoices.titles.invoice_number', ['number' => $invoice->issue->number->value]) }}
        @else
        {{ __('invoices.titles.draft') }}
        @endif
    </h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-4 md:flex md:grid-cols-none md:space-x-0">
        <x-back-button />

        <a href="{{ route('frontend.invoices', ['locale' => App::getLocale()]) }}"
            title="{{ __('invoices.actions.back_to_list') }}" aria-label="{{ __('invoices.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>

        @if($invoice->issue)
        @allowed(SetInvoiceStatus::class)
        <form method="POST" class="col-span-1 md:col-span-none inline-block"
            action="{{ route('frontend.invoices.set-status', ['locale' => App::getLocale(), 'invoice' => $invoice->id->value, 'status' => $invoice->status === InvoiceStatus::PAID ? InvoiceStatus::PENDING->value : InvoiceStatus::PAID->value]) }}">
            @csrf
            @method('PUT')
            @if($invoice->status === InvoiceStatus::PAID)
            <button type="submit" title="{{ __('invoices.actions.mark_as_unpaid') }}"
                aria-label="{{ __('invoices.actions.mark_as_unpaid') }}"
                class="px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-undo"></i>
            </button>
            @else
            <button type="submit" title="{{ __('invoices.actions.mark_as_paid') }}"
                aria-label="{{ __('invoices.actions.mark_as_paid') }}"
                class="px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-emerald-200 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-check-circle"></i>
            </button>
            @endif
        </form>
        @endallowed

        @allowed(CopyInvoice::class)
        <form method="POST" class="col-span-1 md:col-span-none inline-block"
            action="{{ route('frontend.invoices.copy', ['locale' => App::getLocale(), 'invoice' => $invoice->id->value]) }}">
            @csrf
            <button type="submit" title="{{ __('invoices.actions.copy') }}" aria-label="{{ __('invoices.actions.copy') }}"
                class="px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-sky-200 dark:bg-sky-500 hover:bg-sky-500 dark:hover:bg-sky-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-copy"></i>
            </button>
        </form>
        @endallowed
        @else
        @allowed(IssueInvoice::class)
        <button type="button" title="{{ __('invoices.actions.issue') }}" aria-label="{{ __('invoices.actions.issue') }}"
            x-data x-on:click="$dispatch('open-issue-dialog', { id: {{ $invoice->id->value }} })"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-emerald-200 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
            <i class="fas fa-stamp"></i>
        </button>
        @endallowed

        @allowed(SetInvoiceTemplate::class)
        <!-- Template Settings Button -->
        <form id="invoice-form" method="POST" action=""
            data-action="{{ route('frontend.invoice.set-template', ['locale' => App::getLocale(), 'id' => $invoice->id->value, 'template' => $invoice->template->value]) }}">
            <button type="button"
                title="{{ __('invoices.labels.template_settings') }}: {{ __('invoices.templates.' . $invoice->template->value) }}"
                aria-label="{{ __('invoices.labels.template_settings') }}: {{ __('invoices.templates.' . $invoice->template->value) }}"
                class="template-settings-btn px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-yellow-200 dark:bg-yellow-500 hover:bg-yellow-500 dark:hover:bg-yellow-700 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-cog"></i>
            </button>
            <input type="hidden" name="current_template" value="{{ $invoice->template->value }}">
        </form>
        @endallowed

        <a href="{{ route('frontend.invoice.edit', ['locale' => App::getLocale(), 'id' => $invoice->id->value]) }}"
            title="{{ __('invoices.actions.edit') }}" aria-label="{{ __('invoices.actions.edit') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-pink-300 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-800 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white text-sm font-medium transition-colors">
            <i class="fas fa-pencil-alt"></i>
        </a>

        @allowed(DeleteInvoice::class)
        <form method="POST" class="col-span-1 md:col-span-none inline-block"
            action="{{ route('frontend.invoices.destroy', ['locale' => App::getLocale(), 'invoice' => $invoice->id->value]) }}"
            onsubmit="return confirm(@js(__('invoices.messages.confirm_delete')))">
            @csrf
            @method('DELETE')
            <button type="submit" title="{{ __('invoices.actions.delete') }}" aria-label="{{ __('invoices.actions.delete') }}"
                class="px-4 py-2 md:col-span-1 md:ml-4 inline-block bg-red-300 dark:bg-red-700 hover:bg-red-500 dark:hover:bg-red-800 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
                <i class="fas fa-trash-alt"></i>
            </button>
        </form>
        @endallowed
        @endif
        <button id="previewPdfBtn" type="button" title="{{ __('invoices.actions.preview_pdf') }}"
            aria-label="{{ __('invoices.actions.preview_pdf') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 bg-indigo-500 hover:bg-[#490BF4] rounded-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 text-sm font-medium cursor-pointer transition-colors">
            <i class="fas fa-eye"></i>
        </button>
        <a href="{{ route('frontend.invoice.download', ['locale' => App::getLocale(), 'id' => $invoice->id->value]) }}"
            title="{{ __('invoices.actions.download_pdf') }}" aria-label="{{ __('invoices.actions.download_pdf') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-red-200 dark:bg-red-500 hover:bg-red-400 dark:hover:bg-red-600 rounded-sm text-sm text-gray-700 dark:text-gray-200 hover:text-white dark:hover:text-gray-200 font-medium transition-colors">
            <i class="fas fa-download"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

@if($invoice->issue)
<div class="mb-6 rounded-sm border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200" role="note">
    {{ __('invoices.messages.issued_note') }}
</div>
@endif

<!-- Main invoice information -->
<div class="mb-6">
    <div class="space-y-6">
        <x-detail.card :title="__('invoices.sections.invoice_info')" :badge="$invoice->issue ? __('invoices.statuses.' . $invoice->status->value) : __('invoices.statuses.draft')">
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="flex-1 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    @if($invoice->issue)
                    <x-detail.item accent="border-indigo-500" :label="__('invoices.fields.invoice_vs_long')">{{ $invoice->issue->number->value }}</x-detail.item>
                    <x-detail.item accent="border-blue-500" :label="__('invoices.fields.issue_date')">
                        {{ $presenter->date($invoice->issue->date, App::getLocale()) }}
                    </x-detail.item>
                    <x-detail.item accent="border-blue-300" :label="__('invoices.fields.tax_point_date')">
                        {{ $presenter->date($invoice->issue->taxPointDate, App::getLocale()) }}
                    </x-detail.item>
                    <x-detail.item accent="border-red-500" :label="__('invoices.fields.due_date')">
                        {{ $presenter->dueDate($invoice->issue, $invoice->dueIn, App::getLocale()) }}
                    </x-detail.item>
                    @else
                    <x-detail.item accent="border-red-500" :label="__('invoices.fields.due_in')">
                        {{ __('invoices.messages.due_after_issue', ['days' => $invoice->dueIn]) }}
                    </x-detail.item>
                    @endif
                    <x-detail.item accent="border-gray-300" :label="__('invoices.fields.payment_method')">@if($detail->paymentMethod){{ __('payment_methods.methods.' . $detail->paymentMethod->value) }}@endif</x-detail.item>
                    <x-detail.item accent="border-green-500" :label="__('invoices.fields.payment_amount')">
                        {{ $presenter->money($invoice->totalAmount, App::getLocale()) }}
                    </x-detail.item>
                    <x-detail.item :accent="'border-' . $statusColor . '-500'" :label="__('invoices.status.invoice_status')">
                        @if($invoice->issue)
                        {{ __('invoices.statuses.' . $invoice->status->value) }}
                        @else
                        {{ __('invoices.statuses.draft') }}
                        @endif
                    </x-detail.item>

                    <x-detail.item accent="border-gray-300" :label="__('invoices.fields.invoice_ks')">{{ $invoice->constantSymbol }}</x-detail.item>
                    <x-detail.item accent="border-gray-300" :label="__('invoices.fields.invoice_ss')">{{ $invoice->specificSymbol }}</x-detail.item>
                    @if($parties->account)
                    <x-detail.item accent="border-orange-300" :label="__('parties.bank_accounts.fields.number')">
                        @if($parties->account->account->number)
                        {{ $parties->account->account->number }}/{{ $parties->account->account->bankCode }}
                        @endif
                    </x-detail.item>
                    <x-detail.item accent="border-purple-300" :label="__('parties.bank_accounts.fields.bank_name')">{{ $parties->account->bankName }}</x-detail.item>
                    <x-detail.item accent="border-blue-300" :label="__('parties.bank_accounts.fields.iban')">{{ $parties->account->account->formattedIban() }}</x-detail.item>
                    <x-detail.item accent="border-red-300" :label="__('parties.bank_accounts.fields.swift')">{{ $parties->account->account->swift }}</x-detail.item>
                    @endif
                </div>
                @if($parties->supplierLogo)
                <x-detail.item class="lg:w-48 lg:shrink-0" accent="border-red-300" :label="__('parties.fields.supplier_logo')">
                    <img src="{{ asset('storage/' . ltrim($parties->supplierLogo, '/')) }}"
                        alt="{{ __('parties.fields.supplier_logo') }}" class="mt-1 max-h-24">
                </x-detail.item>
                @endif
            </div>
        </x-detail.card>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-detail.card :title="__('invoices.sections.supplier')" :badge="__('invoices.tags.issuer')">
                <x-party.document-profile :party="$parties->supplier" :countries="$countries" />
            </x-detail.card>

            <x-detail.card :title="__('invoices.sections.customer')" :badge="__('invoices.tags.recipient')" badge-tone="green">
                <x-party.document-profile :party="$parties->customer" :countries="$countries" />

                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-900 dark:text-white">
                    @if($detail->contact)
                    <a href="{{ route('frontend.contacts.show', ['locale' => App::getLocale(), 'contact' => $detail->contact->id->value]) }}"
                        class="font-semibold underline">{{ __('invoices.actions.open_contact') }}</a>
                    @if($detail->contact->isArchived())
                    <span class="text-gray-500 dark:text-gray-400">({{ __('parties.statuses.archived') }})</span>
                    @endif
                    @elseif($detail->contactToLink)
                    @allowed(LinkInvoiceContact::class)
                    <form method="POST" action="{{ route('frontend.invoices.link-contact', ['locale' => App::getLocale(), 'invoice' => $invoice->id->value]) }}">
                        @csrf
                        <input type="hidden" name="contact_id" value="{{ $detail->contactToLink->id->value }}">
                        <button type="submit" class="font-semibold underline cursor-pointer">
                            {{ __('invoices.actions.link_contact', ['name' => $detail->contactToLink->name]) }}
                        </button>
                    </form>
                    @endallowed
                    @elseif(!$invoice->issue)
                    @allowed(CreateContact::class)
                    @allowed(SaveInvoiceCustomerToContacts::class)
                    <form method="POST" action="{{ route('frontend.invoices.save-to-contacts', ['locale' => App::getLocale(), 'invoice' => $invoice->id->value]) }}">
                        @csrf
                        <button type="submit" class="font-semibold underline cursor-pointer">{{ __('invoices.actions.save_customer_to_contacts') }}</button>
                    </form>
                    @endallowed
                    @endallowed
                    @endif
                </div>
            </x-detail.card>
        </div>

        <!-- Section for invoice items and note (only if exists) -->
        @if($invoice->text || $invoice->items)
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

                            @if($invoice->items)
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
                                            @if($detail->vatPayer)
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.placeholders.item_tax') }}
                                            </th>
                                            @endif
                                            <th scope="col"
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('invoices.fields.item_base') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-600">
                                        @foreach($invoice->items as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                {{ $item->name }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ $presenter->number($item->quantity, App::getLocale()) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ __('invoices.units.' . $item->unit->value) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ $presenter->money($item->price, App::getLocale()) }}
                                            </td>
                                            @if($detail->vatPayer)
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">
                                                {{ $presenter->number($item->taxRate, App::getLocale()) }}%
                                            </td>
                                            @endif
                                            <td
                                                class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white font-medium">
                                                {{ $presenter->money($item->base(), App::getLocale()) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        @if($detail->vatPayer)
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <td class="px-4 py-2 text-right dark:text-white" colspan="5">
                                                {{ __('invoices.labels.total_without_tax') }}:
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                                {{ $presenter->money($invoice->totals->subtotal, App::getLocale()) }}
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <td class="px-4 py-2 text-right dark:text-white" colspan="5">
                                                {{ __('invoices.labels.total_tax') }}:
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                                {{ $presenter->money($invoice->totals->tax, App::getLocale()) }}
                                            </td>
                                        </tr>
                                        @endif
                                        <tr class="bg-gray-100 dark:bg-gray-700 font-medium">
                                            <td class="px-4 py-3 text-right dark:text-white" colspan="{{ $detail->vatPayer ? 5 : 4 }}">
                                                {{ __('invoices.fields.total') }}:
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-bold">
                                                {{ $presenter->money($invoice->totalAmount, App::getLocale()) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @endif

                            @if($invoice->text)
                            <!-- Invoice note -->
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 mt-6 ml-4">{{
                                __('invoices.fields.invoice_note') }}</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-sm p-4">
                                <p class="text-sm text-gray-700 dark:text-gray-400 whitespace-pre-line">{{
                                    $invoice->text }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>
</div>

@if(!$invoice->issue)
@allowed(IssueInvoice::class)
@livewire('invoice.issue-invoice-dialog', ['openFor' => $openIssueDialog ? $invoice->id->value : null])
@endallowed
@endif

<!-- Modal window for PDF preview -->
<div id="pdfPreviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">@if($invoice->issue)
                    {{ __('invoices.modal.preview_title', ['number' => $invoice->issue->number->value]) }}
                    @else
                    {{ __('invoices.titles.draft') }}
                    @endif</h3>
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
                <a href="{{ route('frontend.invoice.download', ['locale' => App::getLocale(), 'id' => $invoice->id->value]) }}"
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
                    action="{{ route('frontend.invoice.set-template', ['locale' => App::getLocale(), 'id' => $invoice->id->value, 'template' => $invoice->template->value]) }}"
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
            const currentLang = '{{ App::getLocale() }}';
            // Set iframe source with preview=true parameter
            pdfIframe.src = "{{ route('frontend.invoice.download', ['locale' => App::getLocale(), 'id' => $invoice->id->value]) }}?preview=true";

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
            console.log('URL:', "{{ route('frontend.invoice.download', ['locale' => App::getLocale(), 'id' => $invoice->id->value]) }}?preview=true");
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
    window.templateAction = "{{ route('frontend.invoice.set-template', ['locale' => App::getLocale(), 'id' => $invoice->id->value, 'template' => $invoice->template->value]) }}";
    window.csfrToken = "{{ csrf_token() }}";
</script>
@vite('resources/js/template-selector-updating.js')

@endpush
