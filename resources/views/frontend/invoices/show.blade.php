@extends('layouts.frontend')

@section('content')
    <!-- Top navigation bar with buttons -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl text-amber-600">{{ __('invoices.titles.invoice_number', ['number' => $invoice->invoice_vs]) }}</h1>
        <div class="flex space-x-4">
            <x-back-button />
            
            <a href="@localizedRoute('frontend.invoices')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> {{ __('invoices.actions.back_to_list') }}
            </a>

            <!-- Button for marking as paid - show only if not paid -->
            @if($invoice->payment_status_slug != 'paid')
                <form method="POST" action="@localizedRoute('frontend.invoice.mark-as-paid', $invoice->id)">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="px-4 py-2 bg-emerald-200 hover:bg-emerald-500 rounded-md text-gray-700 hover:text-white text-sm font-medium transition-colors">
                        <i class="fas fa-check-circle mr-2"></i> {{ __('invoices.actions.mark_as_paid') }}
                    </button>
                </form>
            @endif

            <a href="@localizedRoute('frontend.invoice.edit', $invoice->id)" class="px-4 py-2 bg-green-200 hover:bg-emerald-500 rounded-md text-gray-700 hover:text-white text-sm font-medium transition-colors">
                <i class="fas fa-pencil-alt"></i> {{ __('invoices.actions.edit') }}
            </a>
            <button 
                id="previewPdfBtn" 
                type="button" 
                class="px-4 py-2 bg-blue-300 hover:bg-cyan-600 rounded-md text-gray-700 hover:text-white text-sm font-medium cursor-pointer transition-colors"
            >
                <i class="fas fa-eye mr-2"></i> {{ __('invoices.actions.preview_pdf') }}
            </button>
            <a 
                href="@localizedRoute('frontend.invoice.download', $invoice->id)" 
                class="px-4 py-2 bg-red-200 hover:bg-red-400 rounded-md text-sm text-gray-700 hover:text-white font-medium transition-colors"
            >
                <i class="fas fa-download mr-2"></i> {{ __('invoices.actions.download_pdf') }}
            </a>
        </div>
    </div>

    <!-- Main invoice information -->
    <div class="mb-6">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left column: Invoice information -->
                
                <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                    <div class="mb-4 border-b border-gray-200 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('invoices.sections.invoice_info') }}</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $invoice->payment_status_name }}</span>
                    </div>
                
                    <div class="space-y-4">
                        <div class="flex border-b border-gray-100 pb-3">
                            <div class="w-1/3 text-sm font-medium text-gray-600">{{ __('invoices.fields.invoice_vs_long') }}</div>
                            <div class="w-2/3 text-sm font-bold">{{ $invoice->invoice_vs }}</div>
                        </div>
                        
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-blue-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.issue_date') }}</div>
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($invoice->issue_date)->format(App\Helpers\DateHelper::format()) }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.due_date') }}</div>
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($invoice->issue_date)->addDays((int)$invoice->due_in)->format(App\Helpers\DateHelper::format()) }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-gray-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.payment_method') }}</div>
                                    <div class="font-medium">
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
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.payment_amount') }}</div>
                                    <div class="font-medium">{{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency }}</div>
                                </div>
                            </div>
                            @if($invoice->account_number)
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('suppliers.fields.account_number') }}</div>
                                    <div class="font-medium">{{ $invoice->account_number }}</div>
                                </div>
                            </div>
                            @endif
                            @if($invoice->bank_name)
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-purple-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('suppliers.fields.bank_name') }}</div>
                                    <div class="font-medium">{{ $invoice->bank_name }}</div>
                                </div>
                            </div>
                            @endif
                            @if($invoice->iban)
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-blue-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('suppliers.fields.iban') }}</div>
                                    <div class="font-medium">{{ $invoice->iban }}</div>
                                </div>
                            </div>
                            @endif
                            @if($invoice->swift)
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-300 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('suppliers.fields.swift') }}</div>
                                    <div class="font-medium">{{ $invoice->swift }}</div>
                                </div>
                            </div>
                            @endif
                            <div class="w-full mb-4">
                                <div class="border-l-4 border-{{ $invoice->status_color_class }}-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('invoices.fields.payment_method') }}</div>
                                    <div class="font-medium">
                                        @if($invoice->paymentMethod)
                                            {{ $invoice->payment_status_name }}
                                        @else
                                            <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($invoice->invoice_ks || $invoice->invoice_ss)
                        <div class="bg-gray-50 p-3 rounded-md">
                            <div class="flex flex-wrap text-sm">
                                @if($invoice->invoice_ks)
                                <div class="w-1/2">
                                    <span class="text-gray-600">{{ __('invoices.fields.invoice_ks') }}:</span> 
                                    <span class="font-medium">{{ $invoice->invoice_ks }}</span>
                                </div>
                                @endif
                                @if($invoice->invoice_ss)
                                <div class="w-1/2">
                                    <span class="text-gray-600">{{ __('invoices.fields.invoice_ss') }}:</span> 
                                    <span class="font-medium">{{ $invoice->invoice_ss }}</span>
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
                    <div class="mb-6 overflow-hidden shadow-md bg-blue-50 rounded-lg border border-blue-200 p-6">
                        <div class="mb-4 border-b border-blue-200 pb-2 flex justify-between items-center">
                            <h2 class="text-xl text-gray-800">{{ __('invoices.sections.supplier') }}</h2>
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ __('invoices.tags.issuer') }}</span>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex flex-wrap">
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-green-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.name') }}</div>
                                        <div class="font-medium">{{ $invoice->name }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-green-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.street') }}</div>
                                        <div class="font-medium">{{ $invoice->street }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-orange-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.city') }}</div>
                                        <div class="font-medium">{{ $invoice->city }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-orange-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.zip') }} {{ __('general.joins.and') }} {{ __('invoices.fields.country') }}</div>
                                        <div class="font-medium">{{ $invoice->zip }}, {{ $invoice->country }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.ico') }}</div>
                                        <div class="font-medium">{{ $invoice->ico }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('invoices.fields.dic') }}</div>
                                        <div class="font-medium">{{ $invoice->dic }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client section -->
                    <div class="overflow-hidden shadow-md bg-green-50 rounded-lg border border-green-200 p-6">
                        <div class="mb-4 border-b border-green-200 pb-2 flex justify-between items-center">
                            <h2 class="text-xl text-gray-800">{{ __('invoices.sections.client') }}</h2>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{ __('invoices.tags.recipient') }}</span>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex flex-wrap">
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-orange-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.name') }}</div>
                                        <div class="font-medium">{{ $invoice->client->name }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-orange-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.street') }}</div>
                                        <div class="font-medium">{{ $invoice->client->street }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-red-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.city') }}</div>
                                        <div class="font-medium">{{ $invoice->client->city }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-red-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.zip') }} {{ __('general.joins.and') }} {{ __('clients.fields.country') }}</div>
                                        <div class="font-medium">{{ $invoice->client->zip }}, {{ $invoice->client->country }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-purple-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.ico') }}</div>
                                        <div class="font-medium">{{ $invoice->client->ico }}</div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <div class="border-l-4 border-purple-500 pl-3 py-1">
                                        <div class="text-xs text-gray-500">{{ __('clients.fields.dic') }}</div>
                                        <div class="font-medium">{{ $invoice->client->dic }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section for invoice items and note (only if exists) -->
            @if(!empty($invoice->invoice_text))
            <div>
                <div class="bg-red-100 overflow-hidden shadow-md rounded-lg p-6">
                    <div class="mb-4 border-b border-white pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('invoices.sections.invoice_text') }}</h2>
                    </div>
                
                    <div class="space-y-4">
                        <div class="mt-4 space-y-4">
                            <div class="">
                                
                                @if($invoice->invoiceProductsData && count($invoice->invoiceProductsData) > 0)
                                <!-- Invoice items -->
                                <h3 class="text-base font-medium text-gray-900 mb-4 ml-4">{{ __('invoices.fields.invoice_items') }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_name') }}
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_quantity') }}
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_unit') }}
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_price') }}
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_tax') }}
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('invoices.placeholders.item_price_complete') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($invoice->invoiceProductsData as $item)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900">
                                                        {{ $item['name'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                        {{ $item['quantity'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                        {{ __('invoices.units.' . ($item['unit'] ?? '')) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                        @if(isset($item['price']) && $item['price'] > 0)
                                                            {{ number_format($item['price'], 2, ',', ' ') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                        @if(isset($item['tax_rate']) && $item['tax_rate'] > 0)
                                                            {{ $item['tax_rate'] }}%
                                                        @else
                                                            0%
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-right text-gray-900 font-medium">
                                                        @if(isset($item['total_price']) && $item['total_price'])
                                                            {{ $item['total_price'] }}
                                                        @elseif(isset($item['price']) && isset($item['quantity']))
                                                            @php
                                                                $tax = isset($item['tax_rate']) ? floatval($item['tax_rate']) : 0;
                                                                $totalWithTax = floatval($item['price']) * floatval($item['quantity']) * (1 + ($tax / 100));
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
                                            <tr class="bg-gray-100 font-medium">
                                                <td class="px-4 py-3 text-right" colspan="5">
                                                    {{ __('invoices.fields.total') }}:
                                                </td>
                                                <td class="px-4 py-3 text-right text-gray-900 font-bold">
                                                    {{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

                                @if($invoice->invoice_text)
                                    <!-- Invoice note -->
                                    <h3 class="text-base font-medium text-gray-900 mb-2 mt-6 ml-4">{{ __('invoices.fields.invoice_note') }}</h3>
                                    <div class="bg-gray-50 rounded-md p-4">
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $invoice->invoice_text }}</p>
                                    </div>
                                @elseif(!$invoice->invoice_text)
                                    <!-- Display original content if JSON parsing fails -->
                                    <h3 class="text-base font-medium text-gray-900 mb-2">{{ __('invoices.sections.internal_note') }}</h3>
                                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $invoice->invoice_text }}</p>
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
                <h3 class="text-lg font-medium text-gray-900">{{ __('invoices.modal.preview_title', ['number' => $invoice->invoice_vs]) }}</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('invoices.actions.close') }}</span>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-2 h-[calc(100vh-200px)] relative">
                <div id="loading-indicator" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10">
                    <svg class="animate-spin h-10 w-10 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-indigo-600 font-medium">{{ __('invoices.modal.loading_pdf') }}</span>
                </div>
                <iframe id="pdfIframe" class="w-full h-full border-0" src="about:blank"></iframe>
            </div>
            <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" id="closeModalBtn" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('invoices.actions.close') }}
                </button>
                <a href="@localizedRoute('frontend.invoice.download', $invoice->id)" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    {{ __('invoices.actions.download_pdf') }}
                </a>
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
            const currentLang = '{{ app()->getLocale() }}';
            // Set iframe source with preview=true parameter
            pdfIframe.src = "{{ route('frontend.invoice.download', $invoice->id) }}?preview=true&lang=" + currentLang;
            
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
            console.log('URL:', "{{ route('frontend.invoice.download', $invoice->id) }}?preview=true&lang=" + currentLang);
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
@endpush
