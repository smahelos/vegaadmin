<div>
    @if(isset($invoices) && $invoices->count() > 0)
        <ul class="divide-y divide-gray-200">
            @foreach($invoices as $invoice)
                <li class="px-4 hover:bg-gray-50 bg-{{ $invoice->status_color_class }}-100">
                    <a href="{{ route('frontend.invoice.show', ['id' => $invoice->id, 'locale' => app()->getLocale()]) }}" class="flex flex-row items-center">
                        <div class="basis-3/24">
                            <p class="text-sm font-medium text-gray-900">{{ $invoice->invoice_vs }}</p>
                        </div>
                        <div class="basis-6/24">
                            <p class="text-xs text-gray-500">
                                {{ $invoice->client ? $invoice->client->name : __('dashboard.status.unknown_client') }} -
                                {{ number_format($invoice->payment_amount, 0, ',', ' ') }} {{ __('general.currency') }}
                            </p>
                        </div>
                        <div class="basis-6/24">
                            <p class="text-xs text-gray-500">
                                @if($invoice->paymentMethod)
                                    {{ $invoice->paymentMethod->translated_name }}
                                @else
                                    <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="basis-6/24">
                            <p class="p-3 text-{{ $invoice->status_color_class }}-800">
                                {{ $invoice->client ? $invoice->payment_status_name : __('dashboard.status.unknown_status') }}
                            </p>
                        </div>
                        <div class="basis-2/24 p-4 text-{{ $invoice->status_color_class }}-800
                                                            ">
                            <p class="text-xs">
                                @if($invoice->due_date)
                                    {{ $invoice->due_date->format('d.m.Y') }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </p>
                        </div>
                        <div class="basis-1/24 text-right">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
            <a href="{{ route('frontend.invoices', ['locale' => app()->getLocale()]) }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('dashboard.actions.view_all_invoices') }} <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    @else
    <div class="p-4 text-center text-gray-500">
        <p>{{ __('dashboard.status.no_invoices') }}</p>
    </div>
    @endif
</div>
