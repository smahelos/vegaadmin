<div>
    @if(isset($invoices) && $invoices->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('invoice_vs')">
                            {{ __('invoices.fields.invoice_vs') }}
                            @if($orderBy === 'invoice_vs')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('client_id')">
                            {{ __('invoices.fields.client_id') }}
                            @if($orderBy === 'client_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_amount')">
                            {{ __('invoices.fields.payment_amount') }}
                            @if($orderBy === 'payment_amount')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('due_date')">
                            {{ __('invoices.fields.due_date') }}
                            @if($orderBy === 'due_date')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_method_id')">
                            {{ __('invoices.fields.payment_method_id') }}
                            @if($orderBy === 'payment_method_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_status_id')">
                            {{ __('invoices.fields.status') }}
                            @if($orderBy === 'payment_status_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{
                        __('invoices.fields.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($invoices as $invoice)
                <tr>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->invoice_vs }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($invoice->client)
                        <a href="@localizedRoute('frontend.client.show', $invoice->client->id)"
                            class="text-indigo-600 hover:text-indigo-900">
                            {{ $invoice->client->name }}
                        </a>
                        @else
                        <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm
                        @if($invoice->due_date && $invoice->due_date->isPast() && (!$invoice->paymentStatus || $invoice->payment_status_slug !== 'paid'))
                             text-red-600 font-medium bg-red-100
                        @else
                             text-gray-500
                        @endif">
                        @if($invoice->due_date)
                        {{ $invoice->due_date->format('d.m.Y') }}
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($invoice->paymentMethod)
                        {{ $invoice->paymentMethod->translated_name }}
                        @else
                        <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                        @endif
                    </td>
                    <td
                        class="px-4 py-4 whitespace-nowrap text-sm bg-{{ $invoice->status_color_class }}-100 text-{{ $invoice->status_color_class }}-800">
                        @if($invoice->paymentStatus)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ $invoice->payment_status_name }}
                        </span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="@localizedRoute('frontend.invoice.show', $invoice->id)"
                            title="{{ __('invoices.actions.show') }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="@localizedRoute('frontend.invoice.edit', $invoice->id)"
                            title="{{ __('invoices.actions.edit') }}"
                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a href="@localizedRoute('frontend.invoice.download', $invoice->id)"
                            title="{{ __('invoices.actions.download') }}"
                            class="text-fuchsia-600 hover:text-fuchsia-900">
                            <i class="fas fa-download"></i>
                        </a>

                        <!-- Button to mark invoice as paid -->
                        @if($invoice->payment_status_slug != 'paid')
                        <form method="POST" action="@localizedRoute('frontend.invoice.mark-as-paid', $invoice->id)"
                            class="inline">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 pl-2"
                                title="{{ __('invoices.actions.mark_as_paid') }}">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </form>
                        @else
                        <span class="text-gray-400 cursor-not-allowed pl-2">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        <x-pagination :paginator="$invoices" />
    </div>
    @endif
    @else
    <div class="text-center py-10">
        <div class="text-gray-400 mb-3">
            <i class="fas fa-file-invoice fa-3x"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('invoices.empty.title') }}</h3>
        <p class="text-gray-500 mb-6">{{ __('invoices.empty.message') }}</p>
        <a href="@localizedRoute('frontend.invoice.create')"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fas fa-plus mr-2"></i> {{ __('invoices.actions.create') }}
        </a>
    </div>
    @endif
</div>
