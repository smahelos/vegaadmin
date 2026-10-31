<div>
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <label for="invoice-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('invoices.filters.search') }}</label>
            <input id="invoice-search" type="search" wire:model.live.debounce.300ms="search" class="form-input mt-1 block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
        </div>
        <div>
            <label for="invoice-business" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('businesses.entity.singular') }}</label>
            <select id="invoice-business" wire:model.live="businessId" class="form-select mt-1 block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('invoices.filters.all_businesses') }}</option>
                @foreach($businesses as $business)
                    <option value="{{ $business->id->value }}">{{ $business->name }}{{ $business->archived ? ' (' . __('invoices.filters.archived') . ')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="invoice-contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('contacts.entity.singular') }}</label>
            <select id="invoice-contact" wire:model.live="contactId" class="form-select mt-1 block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('invoices.filters.all_contacts') }}</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id->value }}">{{ $contact->name }}{{ $contact->archived ? ' (' . __('invoices.filters.archived') . ')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="invoice-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('invoices.fields.status') }}</label>
            <div class="mt-1 flex gap-2">
                <select id="invoice-status" wire:model.live="status" class="form-select block min-w-0 w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200">
                    <option value="">{{ __('invoices.filters.all_statuses') }}</option>
                    @foreach($statuses as $invoiceStatus)
                        <option value="{{ $invoiceStatus->value }}">{{ __('statuses.statuses.' . $invoiceStatus->value) }}</option>
                    @endforeach
                </select>
                <x-icon-button icon="fa-filter-circle-xmark" :label="__('invoices.filters.reset')"
                    wire:click="resetFilters" class="shrink-0" />
            </div>
        </div>
    </div>
    @if(isset($invoices) && $invoices->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('invoice_vs')">
                            {{ __('invoices.fields.invoice_vs') }}
                            @if($orderBy === 'invoice_vs')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider hidden lg:table-cell">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('customer_name')">
                            {{ __('invoices.fields.customer') }}
                            @if($orderBy === 'customer_name')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_amount')">
                            {{ __('invoices.fields.payment_amount') }}
                            @if($orderBy === 'payment_amount')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider hidden md:table-cell">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('due_date')">
                            {{ __('invoices.fields.due_date') }}
                            @if($orderBy === 'due_date')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider hidden lg:table-cell">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_method_id')">
                            {{ __('invoices.fields.payment_method_id') }}
                            @if($orderBy === 'payment_method_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_status_id')">
                            {{ __('invoices.fields.status') }}
                            @if($orderBy === 'payment_status_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-green-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-green-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-bold text-green-600 uppercase tracking-wider">{{
                        __('invoices.fields.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($invoices as $invoice)
                <tr>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->number }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                        @if($invoice->contactId?->value)
                        <a href="{{ route('frontend.contacts.show', ['locale' => app()->getLocale(), 'contact' => $invoice->contactId?->value]) }}"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            {{ $invoice->customerName }}
                        </a>
                        @else
                        {{ $invoice->customerName }}
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $presenter->amount($invoice, app()->getLocale()) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm hidden md:table-cell
                        @if($presenter->isOverdue($invoice))
                             text-red-600 font-medium bg-red-100 dark:text-white dark:bg-red-900">
                        @else
                             text-gray-500 dark:text-gray-400">
                        @endif
                        {{ $presenter->dueDate($invoice, app()->getLocale()) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                        @if($invoice->paymentMethod)
                        {{ __('payment_methods.methods.' . $invoice->paymentMethod->value) }}
                        @else
                        <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                        @endif
                    </td>
                    <td
                        class="px-4 py-4 whitespace-nowrap text-sm bg-{{ $invoice->statusColor }}-100 text-{{ $invoice->statusColor }}-800 dark:bg-{{ $invoice->statusColor }}-700 dark:text-{{ $invoice->statusColor }}-200">
                        @if($invoice->status)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ __('statuses.statuses.' . $invoice->status->value) }}
                        </span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('frontend.invoice.show', ['id' => $invoice->id->value, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('invoices.actions.show') }}" class="text-indigo-500 hover:text-[#490BF4] mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('frontend.invoice.edit', ['id' => $invoice->id->value, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('invoices.actions.edit') }}"
                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a href="{{ route('frontend.invoice.download', ['id' => $invoice->id->value, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('invoices.actions.download') }}"
                            class="text-fuchsia-600 hover:text-fuchsia-900">
                            <i class="fas fa-download"></i>
                        </a>

                        <!-- Button to mark invoice as paid -->
                        @if($invoice->status !== \App\Domain\Invoice\Enums\InvoiceStatus::PAID)
                        <form method="POST" action="{{ route('frontend.invoice.mark-as-paid', ['id' => $invoice->id->value, 'locale' => app()->getLocale()]) }}"
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
        {{ $invoices->links() }}
    </div>
    @endif
    @else
    <div class="text-center py-10">
        <div class="text-gray-400 mb-3">
            <i class="fas fa-file-invoice fa-3x"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200 mb-2">{{ __('invoices.titles.empty') }}</h3>
        <p class="text-gray-500 mb-6">{{ __('invoices.titles.empty_message') }}</p>
        <a href="{{ route('frontend.invoice.create', ['locale' => app()->getLocale()]) }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fas fa-plus mr-2"></i> {{ __('invoices.actions.create') }}
        </a>
    </div>
    @endif
</div>
